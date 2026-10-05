import { __, sprintf } from '@wordpress/i18n';
import { Check, ImageIcon, Loader2, RefreshCw, X } from 'lucide-react';
import React, { useId, useState } from 'react';
import { Button } from '../../../components/ui/Button';
import { cn } from '../../../lib/utils';
import { getAiClient } from '../../api/client';
import { toAiApiError } from '../../api/errors';
import { useAiFlow } from '../../store/AiFlowContext';
import { BrandKit, ImageSlot, isImageSlot, Section } from '../../types';
import { fieldClass, outlineButtonClass, primaryButtonClass } from '../fields';
import AutoTextarea from './AutoTextarea';

const ROLE_LABELS: Record<string, string> = {
	hero: __('Hero', 'themegrill-demo-importer'),
	features: __('Features', 'themegrill-demo-importer'),
	content: __('Content', 'themegrill-demo-importer'),
	testimonials: __('Testimonials', 'themegrill-demo-importer'),
	team: __('Team', 'themegrill-demo-importer'),
	faq: __('FAQ', 'themegrill-demo-importer'),
	gallery: __('Gallery', 'themegrill-demo-importer'),
	cta: __('Call to action', 'themegrill-demo-importer'),
	contact: __('Contact', 'themegrill-demo-importer'),
	pricing: __('Pricing', 'themegrill-demo-importer'),
	dynamic: __('Dynamic content', 'themegrill-demo-importer'),
};

const KIND_LABELS: Record<string, string> = {
	paragraph: __('Paragraph', 'themegrill-demo-importer'),
	button: __('Button label', 'themegrill-demo-importer'),
	'list-item': __('List item', 'themegrill-demo-importer'),
	name: __('Name', 'themegrill-demo-importer'),
	role: __('Job title', 'themegrill-demo-importer'),
	question: __('Question', 'themegrill-demo-importer'),
	answer: __('Answer', 'themegrill-demo-importer'),
	caption: __('Caption', 'themegrill-demo-importer'),
	overlay: __('Image label', 'themegrill-demo-importer'),
};

const kindLabel = (kind: string) =>
	/^h[1-6]$/.test(kind)
		? sprintf(
				/* translators: %s: heading level, e.g. H2. */
				__('Heading (%s)', 'themegrill-demo-importer'),
				kind.toUpperCase(),
			)
		: (KIND_LABELS[kind] ?? __('Text', 'themegrill-demo-importer'));

// Text styling per slot kind, so the card reads like the finished section.
const textStyle = (kind: string, brand: BrandKit): { className: string; style: React.CSSProperties } => {
	const heading = { fontFamily: `'${brand.fonts.heading}', sans-serif`, color: brand.palette.text };
	const body = { fontFamily: `'${brand.fonts.body}', sans-serif`, color: brand.palette.text };
	switch (kind) {
		case 'h1':
			return { className: '!text-[30px] !leading-[1.2] !font-bold', style: heading };
		case 'h2':
			return { className: '!text-[24px] !leading-[1.25] !font-bold', style: heading };
		case 'h3':
		case 'h4':
		case 'h5':
		case 'h6':
		case 'question':
		case 'name':
			return { className: '!text-[17px] !leading-snug !font-semibold', style: heading };
		case 'button':
			return {
				className: '!text-[14px] !font-semibold !text-center',
				style: { fontFamily: body.fontFamily, color: '#fff' },
			};
		case 'role':
		case 'caption':
		case 'overlay':
			return { className: '!text-[13px] !leading-snug', style: { ...body, opacity: 0.75 } };
		default:
			return { className: '!text-[15px] !leading-relaxed', style: body };
	}
};

const ImageSwap = ({
	image,
	label,
	onChange,
}: {
	image: ImageSlot;
	label: string;
	onChange: (image: ImageSlot) => void;
}) => {
	const [open, setOpen] = useState(false);

	const choose = (url: string) => {
		// The current image joins the alternatives so the swap can be undone.
		onChange({ ...image, url, alternatives: [image.url, ...image.alternatives.filter((a) => a !== url)] });
		setOpen(false);
	};

	return (
		<div>
			<div className="relative group rounded-md overflow-hidden bg-[#F1F5F9] aspect-[4/3]">
				<img src={image.url} alt={image.alt} className="w-full h-full object-cover" loading="lazy" />
				{image.alternatives.length > 0 && (
					<button
						type="button"
						onClick={() => setOpen((o) => !o)}
						aria-expanded={open}
						className="absolute bottom-2 right-2 flex items-center gap-1 px-2 h-7 whitespace-nowrap rounded-full border-0 bg-white/95 text-[13px] text-[#1F1F1F] shadow cursor-pointer hover:bg-white focus-visible:ring-2 focus-visible:ring-[#5182EF]"
					>
						<ImageIcon size={13} aria-hidden="true" />
						<span className="text-[12px]">{__('Swap', 'themegrill-demo-importer')}</span>
						<span className="sr-only">{label}</span>
					</button>
				)}
			</div>
			{open && (
				<div className="mt-2 grid grid-cols-4 gap-2" role="listbox" aria-label={__('Other images', 'themegrill-demo-importer')}>
					{image.alternatives.map((url) => (
						<button
							key={url}
							type="button"
							role="option"
							aria-selected={false}
							onClick={() => choose(url)}
							className="p-0 border-2 border-solid border-transparent rounded overflow-hidden cursor-pointer hover:border-[#2563EB] focus-visible:border-[#2563EB] focus-visible:outline-none aspect-square bg-[#F1F5F9]"
						>
							<img src={url} alt="" className="w-full h-full object-cover" loading="lazy" />
						</button>
					))}
				</div>
			)}
		</div>
	);
};

type Props = { page: string; section: Section; brand: BrandKit; generationId: string };

const SectionCard = ({ page, section, brand, generationId }: Props) => {
	const { dispatch } = useAiFlow();
	const uid = useId();
	const [rewriting, setRewriting] = useState(false);
	const [instruction, setInstruction] = useState('');
	const [busy, setBusy] = useState(false);
	const [error, setError] = useState('');

	const kinds = section.kinds ?? {};
	const entries = Object.entries(section.slots);
	const texts = entries.filter((e): e is [string, string] => typeof e[1] === 'string');
	const images = entries.filter((e): e is [string, ImageSlot] => isImageSlot(e[1]));
	const buttons = texts.filter(([id]) => kinds[id] === 'button');
	const copy = texts.filter(([id]) => kinds[id] !== 'button');

	const update = (slotId: string, value: string | ImageSlot) =>
		dispatch({ type: 'UPDATE_SLOT', page, sectionId: section.id, slotId, value });

	const rewrite = async () => {
		setBusy(true);
		setError('');
		try {
			const { section: next } = await getAiClient().regenerateSection({
				generationId,
				page,
				sectionId: section.id,
				instruction: instruction.trim() || undefined,
			});
			dispatch({ type: 'REPLACE_SECTION', page, section: next });
			setRewriting(false);
			setInstruction('');
		} catch (e) {
			setError(toAiApiError(e).message);
		} finally {
			setBusy(false);
		}
	};

	return (
		<section
			aria-labelledby={`${uid}-title`}
			className="relative rounded-lg border border-solid border-[#E5E7EB] bg-white overflow-hidden"
		>
			<header className="flex flex-wrap items-center justify-between gap-2 px-4 py-2 border-0 border-b border-solid border-[#F1F5F9] bg-[#FAFBFC]">
				<h3 id={`${uid}-title`} className="m-0 text-[13px] font-medium text-[#6B6B6B] uppercase tracking-wide">
					{ROLE_LABELS[section.type] ?? section.type}
				</h3>
				{texts.length > 0 && !rewriting && (
					<button
						type="button"
						onClick={() => setRewriting(true)}
						className="flex items-center gap-1 bg-transparent border-0 p-1 text-[13px] text-[#2563EB] cursor-pointer hover:underline"
					>
						<RefreshCw size={14} aria-hidden="true" />
						{__('Rewrite', 'themegrill-demo-importer')}
					</button>
				)}
			</header>

			{rewriting && (
				<div className="flex flex-wrap items-center gap-2 px-4 py-3 border-0 border-b border-solid border-[#E8EEFD] bg-[#F5F8FF]">
					<label htmlFor={`${uid}-instruction`} className="sr-only">
						{__('Rewrite instruction', 'themegrill-demo-importer')}
					</label>
					<input
						id={`${uid}-instruction`}
						type="text"
						value={instruction}
						maxLength={200}
						disabled={busy}
						onChange={(e) => setInstruction(e.target.value)}
						onKeyDown={(e) => e.key === 'Enter' && !busy && rewrite()}
						placeholder={__('Optional: e.g. shorter, more playful, mention free delivery', 'themegrill-demo-importer')}
						className={cn(fieldClass, '!h-9 !text-[14px] flex-1 min-w-[200px]')}
					/>
					<Button type="button" onClick={rewrite} disabled={busy} className={cn(primaryButtonClass, 'h-9 px-4 gap-2')}>
						{busy ? <Loader2 size={14} className="animate-spin" /> : <Check size={14} />}
						{busy ? __('Rewriting…', 'themegrill-demo-importer') : __('Rewrite section', 'themegrill-demo-importer')}
					</Button>
					<Button
						type="button"
						variant="outline"
						disabled={busy}
						onClick={() => setRewriting(false)}
						className={cn(outlineButtonClass, 'h-9 w-9 p-0')}
						aria-label={__('Cancel rewrite', 'themegrill-demo-importer')}
					>
						<X size={14} />
					</Button>
					{error && (
						<p role="alert" className="w-full m-0 text-[13px] text-[#DC2626]">
							{error}
						</p>
					)}
				</div>
			)}

			<div
				className={cn('grid gap-6 p-6', images.length && copy.length ? 'md:grid-cols-[1fr_minmax(0,40%)]' : '')}
				style={{ background: brand.palette.background }}
			>
				{(copy.length > 0 || buttons.length > 0) && (
					<div className="flex flex-col gap-3 min-w-0">
						{!!section.fixed?.length && (
							<p className="m-0 text-[12px] text-[#6B6B6B]">
								<span className="font-medium">{__('Kept as designed:', 'themegrill-demo-importer')}</span>{' '}
								{section.fixed.join(' · ')}
							</p>
						)}
						{copy.map(([id, value]) => {
							const { className, style } = textStyle(kinds[id] ?? 'paragraph', brand);
							return (
								<div key={id} className={kinds[id] === 'list-item' ? 'flex gap-2 items-start' : ''}>
									{kinds[id] === 'list-item' && (
										<span aria-hidden="true" className="mt-[10px] w-[6px] h-[6px] rounded-full shrink-0" style={{ background: brand.palette.primary }} />
									)}
									<AutoTextarea
										value={value}
										onChange={(v) => update(id, v)}
										aria-label={kindLabel(kinds[id] ?? '')}
										className={className}
										style={style}
									/>
								</div>
							);
						})}
						{buttons.length > 0 && (
							<div className="flex flex-wrap gap-3 mt-1">
								{buttons.map(([id, value]) => {
									const { className, style } = textStyle('button', brand);
									return (
										<div key={id} className="rounded-full px-4 py-[6px] min-w-[110px]" style={{ background: brand.palette.primary }}>
											<AutoTextarea
												value={value}
												onChange={(v) => update(id, v)}
												aria-label={kindLabel('button')}
												className={cn(className, 'focus:!bg-white/20')}
												style={style}
											/>
										</div>
									);
								})}
							</div>
						)}
					</div>
				)}
				{images.length > 0 && (
					<div className={cn('grid gap-3', images.length > 1 ? 'grid-cols-2' : '')}>
						{images.map(([id, image], i) => (
							<ImageSwap
								key={id}
								image={image}
								onChange={(next) => update(id, next)}
								label={sprintf(
									/* translators: %d: image number in the section. */
									__('Image %d', 'themegrill-demo-importer'),
									i + 1,
								)}
							/>
						))}
					</div>
				)}
			</div>

			{busy && (
				<div className="absolute inset-0 flex items-center justify-center bg-white/60" aria-hidden="true">
					<Loader2 size={24} className="animate-spin text-[#2563EB]" />
				</div>
			)}
		</section>
	);
};

export default SectionCard;
