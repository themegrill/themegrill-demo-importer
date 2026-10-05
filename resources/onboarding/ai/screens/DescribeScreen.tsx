import { __, sprintf } from '@wordpress/i18n';
import { AlertCircle, ArrowLeft, Sparkles } from 'lucide-react';
import React, { useId, useMemo, useRef, useState } from 'react';
import { Button } from '../../components/ui/Button';
import { cn } from '../../lib/utils';
import { useBackToTemplates } from '../components/AiSidebar';
import { fieldClass, invalidClass } from '../components/fields';
import {
	BRAND_NAME_MAX,
	DESCRIPTION_MAX,
	LANGUAGES,
	NICHES,
	PAGES,
	TONES,
} from '../constants';
import { useAiFlow } from '../store/AiFlowContext';
import { AiPageSlug } from '../types';
import { DescribeErrors, validateDescribe } from '../validation';


const Label = ({ htmlFor, children, optional }: { htmlFor?: string; children: React.ReactNode; optional?: boolean }) => (
	<label htmlFor={htmlFor} className="block text-[14px] font-medium text-[#1F1F1F] mb-2">
		{children}
		{optional ? (
			<span className="font-normal text-[#909090]"> {__('(optional)', 'themegrill-demo-importer')}</span>
		) : (
			<span className="text-[#DC2626]" aria-hidden="true"> *</span>
		)}
	</label>
);

const FieldError = ({ id, message }: { id: string; message?: string }) =>
	message ? (
		<p id={id} className="flex items-center gap-1 mt-2 mb-0 text-[13px] text-[#DC2626]">
			<AlertCircle size={14} aria-hidden="true" />
			{message}
		</p>
	) : null;

const DescribeScreen = () => {
	const { state, dispatch } = useAiFlow();
	const { form } = state;
	const backToTemplates = useBackToTemplates();
	const uid = useId();
	const ids = {
		brandName: `${uid}-brand`,
		description: `${uid}-description`,
		niche: `${uid}-niche`,
		language: `${uid}-language`,
	};

	const [touched, setTouched] = useState<Partial<Record<keyof DescribeErrors, boolean>>>({});
	const [submitted, setSubmitted] = useState(false);
	const brandRef = useRef<HTMLInputElement>(null);
	const descriptionRef = useRef<HTMLTextAreaElement>(null);

	const errors = useMemo(() => validateDescribe(form), [form]);
	const visibleError = (field: keyof DescribeErrors) =>
		submitted || touched[field] ? errors[field] : undefined;
	const touch = (field: keyof DescribeErrors) => setTouched((t) => ({ ...t, [field]: true }));

	const togglePage = (page: AiPageSlug) => {
		const pages = form.pages.includes(page)
			? form.pages.filter((p) => p !== page)
			: PAGES.map((p) => p.value).filter((p) => p === page || form.pages.includes(p));
		dispatch({ type: 'UPDATE_FORM', patch: { pages } });
	};

	const handleSubmit = (event: React.FormEvent) => {
		event.preventDefault();
		setSubmitted(true);

		if (errors.brandName) {
			brandRef.current?.focus();
			return;
		}
		if (errors.description) {
			descriptionRef.current?.focus();
			return;
		}
		if (errors.pages) {
			return;
		}

		dispatch({
			type: 'UPDATE_FORM',
			patch: { brandName: form.brandName.trim(), description: form.description.trim() },
		});
		dispatch({ type: 'SET_STAGE', stage: 'generating' });
	};

	const descriptionLength = form.description.trim().length;
	const brandError = visibleError('brandName');
	const descriptionError = visibleError('description');

	return (
		<form onSubmit={handleSubmit} noValidate className="max-w-[720px] mx-auto">
			<p className="text-[13px] font-medium uppercase tracking-wide text-[#2563EB] m-0 mb-2">
				{sprintf(
					/* translators: 1: current step, 2: total steps. */
					__('Step %1$d of %2$d', 'themegrill-demo-importer'),
					1,
					4,
				)}
			</p>
			<h1 className="text-[28px] leading-9 font-semibold text-[#1F1F1F] m-0 p-0">
				{__('Describe your site', 'themegrill-demo-importer')}
			</h1>
			<p className="text-[15px] leading-6 text-[#6B6B6B] mt-2 mb-8">
				{__(
					'Tell us about your business. We’ll pick a design, write the copy and find images. You can review and edit everything before anything is imported.',
					'themegrill-demo-importer',
				)}
			</p>

			<div className="flex flex-col gap-7">
				<div>
					<Label htmlFor={ids.brandName}>{__('Brand / site name', 'themegrill-demo-importer')}</Label>
					<input
						ref={brandRef}
						id={ids.brandName}
						type="text"
						autoComplete="organization"
						maxLength={BRAND_NAME_MAX + 20}
						value={form.brandName}
						placeholder={__('e.g. Sweet Crumbs', 'themegrill-demo-importer')}
						onChange={(e) => dispatch({ type: 'UPDATE_FORM', patch: { brandName: e.target.value } })}
						onBlur={() => touch('brandName')}
						aria-required="true"
						aria-invalid={!!brandError}
						aria-describedby={brandError ? `${ids.brandName}-error` : undefined}
						className={cn(fieldClass, '!h-12', brandError && invalidClass)}
					/>
					<FieldError id={`${ids.brandName}-error`} message={brandError} />
				</div>

				<div>
					<Label htmlFor={ids.description}>
						{__('What’s the site about?', 'themegrill-demo-importer')}
					</Label>
					<textarea
						ref={descriptionRef}
						id={ids.description}
						rows={4}
						value={form.description}
						placeholder={__(
							'A family bakery in Kathmandu selling fresh bread and custom cakes',
							'themegrill-demo-importer',
						)}
						onChange={(e) => dispatch({ type: 'UPDATE_FORM', patch: { description: e.target.value } })}
						onBlur={() => touch('description')}
						aria-required="true"
						aria-invalid={!!descriptionError}
						aria-describedby={`${ids.description}-hint${descriptionError ? ` ${ids.description}-error` : ''}`}
						className={cn(fieldClass, '!py-3 !leading-6 resize-y min-h-[120px]', descriptionError && invalidClass)}
					/>
					<div className="flex justify-between gap-4 mt-2">
						<p id={`${ids.description}-hint`} className="m-0 text-[13px] text-[#6B6B6B]">
							{__(
								'Mention what you offer, who it’s for and where you are. More detail gives better copy.',
								'themegrill-demo-importer',
							)}
						</p>
						<span
							className={cn(
								'shrink-0 text-[13px] tabular-nums',
								descriptionLength > DESCRIPTION_MAX ? 'text-[#DC2626]' : 'text-[#909090]',
							)}
						>
							{descriptionLength}/{DESCRIPTION_MAX}
						</span>
					</div>
					<FieldError id={`${ids.description}-error`} message={descriptionError} />
				</div>

				<div className="grid grid-cols-1 lg:grid-cols-2 gap-7 lg:gap-5">
					<div>
						<Label htmlFor={ids.niche} optional>
							{__('Niche', 'themegrill-demo-importer')}
						</Label>
						<select
							id={ids.niche}
							value={form.niche}
							onChange={(e) => dispatch({ type: 'UPDATE_FORM', patch: { niche: e.target.value } })}
							className={cn(fieldClass, '!h-12 !pr-10 ![background-position:right_12px_center]')}
						>
							{NICHES.map((n) => (
								<option key={n.value} value={n.value}>
									{n.label}
								</option>
							))}
						</select>
					</div>
					<div>
						<Label htmlFor={ids.language} optional>
							{__('Language', 'themegrill-demo-importer')}
						</Label>
						<select
							id={ids.language}
							value={form.language}
							onChange={(e) => dispatch({ type: 'UPDATE_FORM', patch: { language: e.target.value } })}
							className={cn(fieldClass, '!h-12 !pr-10 ![background-position:right_12px_center]')}
						>
							{LANGUAGES.map((l) => (
								<option key={l.value} value={l.value}>
									{l.label}
								</option>
							))}
						</select>
					</div>
				</div>

				<fieldset className="m-0 p-0 border-0 min-w-0">
					<legend className="p-0 text-[14px] font-medium text-[#1F1F1F] mb-3">
						{__('Tone', 'themegrill-demo-importer')}
					</legend>
					<div className="flex flex-wrap gap-3">
						{TONES.map((tone) => (
							<label key={tone.value} className="relative cursor-pointer">
								<input
									type="radio"
									name={`${uid}-tone`}
									value={tone.value}
									checked={form.tone === tone.value}
									onChange={() => dispatch({ type: 'UPDATE_FORM', patch: { tone: tone.value } })}
									className="peer sr-only"
								/>
								<span
									className={cn(
										'inline-flex items-center h-10 px-5 rounded-full border-2 border-solid text-[14px] transition-colors',
										'peer-focus-visible:ring-2 peer-focus-visible:ring-[#5182EF] peer-focus-visible:ring-offset-2',
										form.tone === tone.value
											? 'bg-[#2563EB] border-[#2563EB] text-white'
											: 'bg-white border-[#EBEDEF] text-[#383838] hover:border-[#B9CDF9]',
									)}
								>
									{tone.label}
								</span>
							</label>
						))}
					</div>
				</fieldset>

				<fieldset className="m-0 p-0 border-0 min-w-0" aria-describedby={errors.pages ? `${uid}-pages-error` : undefined}>
					<legend className="p-0 text-[14px] font-medium text-[#1F1F1F] mb-3">
						{__('Pages', 'themegrill-demo-importer')}
					</legend>
					<div className="grid grid-cols-2 sm:grid-cols-3 gap-3">
						{PAGES.map((page) => {
							const checked = form.pages.includes(page.value);
							return (
								<label
									key={page.value}
									className={cn(
										'flex items-center gap-3 h-12 px-4 rounded-md border-2 border-solid text-[14px] text-[#383838]',
										checked ? 'border-[#B9CDF9] bg-[#F5F8FF]' : 'border-[#EBEDEF] bg-white',
										page.required ? 'cursor-default' : 'cursor-pointer',
									)}
								>
									<input
										type="checkbox"
										checked={checked}
										disabled={page.required}
										onChange={() => togglePage(page.value)}
										className="!m-0"
									/>
									{page.label}
									{page.required && (
										<span className="ml-auto text-[12px] text-[#909090]">
											{__('Required', 'themegrill-demo-importer')}
										</span>
									)}
								</label>
							);
						})}
					</div>
					<FieldError id={`${uid}-pages-error`} message={visibleError('pages')} />
				</fieldset>
			</div>

			<div className="flex flex-col-reverse sm:flex-row sm:justify-between gap-3 mt-10 pt-6 border-0 border-t border-solid border-[#E9E9E9]">
				<Button
					type="button"
					variant="outline"
					onClick={backToTemplates}
					className="h-12 px-6 gap-2 cursor-pointer border-2 border-solid border-[#EBEDEF] bg-white text-[#383838] text-[15px] hover:bg-[#FAFBFC]"
				>
					<ArrowLeft size={16} />
					{__('Back', 'themegrill-demo-importer')}
				</Button>
				<Button
					type="submit"
					className="h-12 px-8 gap-2 cursor-pointer border-0 bg-[#2563EB] text-white text-[15px] hover:bg-[#134FD2]"
				>
					<Sparkles size={18} />
					{__('Generate my site', 'themegrill-demo-importer')}
				</Button>
			</div>
		</form>
	);
};

export default DescribeScreen;
