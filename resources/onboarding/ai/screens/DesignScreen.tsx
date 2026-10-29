// Step 2: every design, closest to the described business first. The user
// picks one and the site is generated from it.
import { __, sprintf } from '@wordpress/i18n';
import { AlertCircle, ArrowLeft, Check, Eye, Loader2, RotateCcw, X } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { Button } from '../../components/ui/Button';
import { cn } from '../../lib/utils';
import { getAiClient } from '../api/client';
import { AiApiError, toAiApiError } from '../api/errors';
import { outlineButtonClass, primaryButtonClass } from '../components/fields';
import { describeKey, useAiFlow } from '../store/AiFlowContext';
import { DesignMatch, DesignOption } from '../types';

const GROUPS: { match: DesignMatch; title: string; hint: string }[] = [
	{
		match: 'best',
		title: __('Best matches', 'themegrill-demo-importer'),
		hint: __('Made for businesses like yours.', 'themegrill-demo-importer'),
	},
	{
		match: 'related',
		title: __('Also a good fit', 'themegrill-demo-importer'),
		hint: __('Designs for neighbouring businesses that adapt well.', 'themegrill-demo-importer'),
	},
	{
		match: 'other',
		title: __('More designs', 'themegrill-demo-importer'),
		hint: __(
			'Further from your business, but any design works: we write your content into it.',
			'themegrill-demo-importer',
		),
	},
];

const ProBadge = () => (
	<span className="absolute top-2 right-2 px-2 py-[2px] rounded text-[11px] font-semibold uppercase tracking-wide bg-[#1F1F1F]/80 text-white">
		{__('Pro', 'themegrill-demo-importer')}
	</span>
);

// The design of the site in the preview, when coming back to pick again.
const CurrentBadge = () => (
	<span className="absolute top-2 left-2 px-2 py-[2px] rounded text-[11px] font-semibold uppercase tracking-wide bg-[#2563EB] text-white">
		{__('Current', 'themegrill-demo-importer')}
	</span>
);

const DesignCard = ({
	design,
	current,
	onPreview,
	onSelect,
}: {
	design: DesignOption;
	current: boolean;
	onPreview: () => void;
	onSelect: () => void;
}) => (
	<div className="group flex flex-col rounded-lg border border-solid border-[#E9E9E9] bg-white overflow-hidden hover:border-[#B9CDF9] hover:shadow-[0_4px_16px_rgba(15,23,42,0.08)] transition">
		<button
			type="button"
			onClick={onSelect}
			aria-label={sprintf(
				/* translators: %s: design name. */
				__('Use the %s design', 'themegrill-demo-importer'),
				design.name,
			)}
			className="relative block p-0 border-0 bg-[#F1F5F9] cursor-pointer aspect-[4/3] overflow-hidden"
		>
			{design.thumbnail && (
				<img
					src={design.thumbnail}
					alt=""
					loading="lazy"
					className="block w-full h-full object-cover object-top transition-transform duration-300 group-hover:scale-[1.03]"
				/>
			)}
			{current && <CurrentBadge />}
			{design.premium && <ProBadge />}
		</button>
		<div className="flex items-center justify-between gap-2 px-3 py-[10px]">
			<span className="text-[14px] font-medium text-[#1F1F1F] truncate">{design.name}</span>
			<div className="flex shrink-0 gap-1">
				{design.demoUrl && (
					<Button
						type="button"
						variant="outline"
						onClick={onPreview}
						className={cn(outlineButtonClass, 'h-8 px-2 gap-1 text-[13px]')}
					>
						<Eye size={14} />
						{__('Preview', 'themegrill-demo-importer')}
					</Button>
				)}
				<Button
					type="button"
					onClick={onSelect}
					className={cn(primaryButtonClass, 'h-8 px-3 text-[13px]')}
				>
					{__('Use', 'themegrill-demo-importer')}
				</Button>
			</div>
		</div>
	</div>
);

// The live demo in an iframe, so the whole design can be scrolled through.
const PreviewModal = ({
	design,
	onClose,
	onSelect,
}: {
	design: DesignOption;
	onClose: () => void;
	onSelect: () => void;
}) => {
	const [loaded, setLoaded] = useState(false);

	useEffect(() => {
		const onKey = (event: KeyboardEvent) => event.key === 'Escape' && onClose();
		document.addEventListener('keydown', onKey);
		return () => document.removeEventListener('keydown', onKey);
	}, [onClose]);

	return (
		<div
			role="dialog"
			aria-modal="true"
			aria-label={design.name}
			className="fixed inset-0 z-[100000] flex flex-col bg-[#0F172A]/70 p-4 sm:p-8"
			onClick={onClose}
		>
			<div
				className="flex flex-col flex-1 min-h-0 rounded-lg overflow-hidden bg-white"
				onClick={(e) => e.stopPropagation()}
			>
				<div className="flex items-center justify-between gap-3 px-4 py-3 border-0 border-b border-solid border-[#E9E9E9]">
					<span className="text-[15px] font-medium text-[#1F1F1F] truncate">{design.name}</span>
					<div className="flex gap-2 shrink-0">
						<Button
							type="button"
							onClick={onSelect}
							className={cn(primaryButtonClass, 'h-9 px-4 gap-2 text-[14px]')}
						>
							<Check size={16} />
							{__('Use this design', 'themegrill-demo-importer')}
						</Button>
						<Button
							type="button"
							variant="outline"
							onClick={onClose}
							aria-label={__('Close preview', 'themegrill-demo-importer')}
							className={cn(outlineButtonClass, 'h-9 w-9 p-0')}
						>
							<X size={16} />
						</Button>
					</div>
				</div>
				<div className="relative flex-1 min-h-0 bg-[#F1F5F9]">
					{!loaded && (
						<div className="absolute inset-0 flex items-center justify-center text-[#6B6B6B]">
							<Loader2 size={24} className="animate-spin" />
						</div>
					)}
					<iframe
						src={design.demoUrl}
						title={design.name}
						onLoad={() => setLoaded(true)}
						className="w-full h-full border-0"
					/>
				</div>
			</div>
		</div>
	);
};

const SkeletonGrid = () => (
	<div className="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5" aria-hidden="true">
		{Array.from({ length: 6 }, (_, i) => (
			<div key={i} className="rounded-lg border border-solid border-[#E9E9E9] overflow-hidden">
				<div className="aspect-[4/3] bg-[#F1F5F9] animate-pulse" />
				<div className="h-[52px]" />
			</div>
		))}
	</div>
);

const DesignScreen = () => {
	const { state, dispatch } = useAiFlow();
	const { form, designs, pkg } = state;
	const [error, setError] = useState<AiApiError | null>(null);
	const [attempt, setAttempt] = useState(0);
	const [preview, setPreview] = useState<DesignOption | null>(null);
	const headingRef = useRef<HTMLHeadingElement>(null);

	useEffect(() => {
		if (designs) return undefined;
		const controller = new AbortController();
		setError(null);
		getAiClient()
			.designs(form, { signal: controller.signal })
			.then((response) => {
				if (!controller.signal.aborted)
					dispatch({ type: 'SET_DESIGNS', designs: response, key: describeKey(form) });
			})
			.catch((err) => {
				const apiError = toAiApiError(err);
				if (!controller.signal.aborted && apiError.code !== 'CANCELLED') setError(apiError);
			});
		return () => controller.abort();
		// Ranked once per description; the form can't change on this screen.
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [attempt]);

	useEffect(() => headingRef.current?.focus(), []);

	const select = (design: DesignOption) => {
		dispatch({ type: 'UPDATE_FORM', patch: { demoSlug: design.slug, briefId: designs?.briefId } });
		dispatch({ type: 'SET_STAGE', stage: 'generating' });
	};

	const back = () => dispatch({ type: 'SET_STAGE', stage: 'describe' });

	return (
		<div className="max-w-[1120px] mx-auto">
			<p className="text-[13px] font-medium uppercase tracking-wide text-[#2563EB] m-0 mb-2">
				{sprintf(
					/* translators: 1: current step, 2: total steps. */
					__('Step %1$d of %2$d', 'themegrill-demo-importer'),
					2,
					5,
				)}
			</p>
			<div className="flex flex-wrap items-end justify-between gap-4">
				<div>
					<h1
						ref={headingRef}
						tabIndex={-1}
						className="text-[28px] leading-9 font-semibold text-[#1F1F1F] m-0 p-0 outline-none"
					>
						{__('Choose a design', 'themegrill-demo-importer')}
					</h1>
					<p className="text-[15px] leading-6 text-[#6B6B6B] mt-2 mb-0">
						{sprintf(
							/* translators: %s: brand name. */
							__(
								'Sorted by how well each design fits %s. Pick the style you like: we write your content and find images for it.',
								'themegrill-demo-importer',
							),
							form.brandName,
						)}
					</p>
				</div>
				<Button
					type="button"
					variant="outline"
					onClick={back}
					className={cn(outlineButtonClass, 'h-10 px-4 gap-2')}
				>
					<ArrowLeft size={16} />
					{__('Edit description', 'themegrill-demo-importer')}
				</Button>
			</div>

			<div className="mt-8">
				{error ? (
					<div
						role="alert"
						className="p-6 rounded-lg border border-solid border-[#FECACA] bg-[#FEF2F2]"
					>
						<div className="flex items-start gap-3">
							<AlertCircle
								size={22}
								className="shrink-0 text-[#DC2626] mt-[2px]"
								aria-hidden="true"
							/>
							<div>
								<h2 className="text-[17px] leading-6 font-semibold text-[#7F1D1D] m-0">
									{__('We couldn’t load the designs', 'themegrill-demo-importer')}
								</h2>
								<p className="text-[14px] leading-[21px] text-[#991B1B] mt-1 mb-0">
									{error.message}
								</p>
							</div>
						</div>
						{error.code !== 'RATE_LIMITED' && error.code !== 'UNAUTHORIZED' && (
							<Button
								type="button"
								onClick={() => setAttempt((a) => a + 1)}
								className={cn(primaryButtonClass, 'h-10 px-5 gap-2 mt-5 ml-[34px]')}
							>
								<RotateCcw size={16} />
								{__('Try again', 'themegrill-demo-importer')}
							</Button>
						)}
					</div>
				) : !designs ? (
					<>
						<p
							className="flex items-center gap-2 text-[14px] text-[#6B6B6B] m-0 mb-5"
							role="status"
						>
							<Loader2 size={16} className="animate-spin" />
							{__('Finding the designs that fit your business…', 'themegrill-demo-importer')}
						</p>
						<SkeletonGrid />
					</>
				) : (
					<div className="flex flex-col gap-10">
						{GROUPS.map(({ match, title, hint }) => {
							const group = designs.designs.filter((d) => d.match === match);
							if (!group.length) return null;
							return (
								<section key={match} aria-label={title}>
									<h2 className="text-[18px] leading-6 font-semibold text-[#1F1F1F] m-0">
										{title}
									</h2>
									<p className="text-[14px] text-[#6B6B6B] mt-1 mb-4">{hint}</p>
									<div className="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5">
										{group.map((design) => (
											<DesignCard
												key={design.slug}
												design={design}
												current={!!pkg && design.slug === pkg.demo.slug}
												onPreview={() => setPreview(design)}
												onSelect={() => select(design)}
											/>
										))}
									</div>
								</section>
							);
						})}
					</div>
				)}
			</div>

			{preview && (
				<PreviewModal
					design={preview}
					onClose={() => setPreview(null)}
					onSelect={() => select(preview)}
				/>
			)}
		</div>
	);
};

export default DesignScreen;
