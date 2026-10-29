import { __, sprintf } from '@wordpress/i18n';
import { keepPreviousData, useQuery } from '@tanstack/react-query';
import { ArrowRight, Info, Loader2, PencilLine, RefreshCw, RotateCcw } from 'lucide-react';
import { useEffect, useId, useState } from 'react';
import { Button } from '../../components/ui/Button';
import { cn } from '../../lib/utils';
import BrandColorField from '../components/BrandColorField';
import { outlineButtonClass, primaryButtonClass } from '../components/fields';
import SectionPreview from '../components/preview/SectionPreview';
import SitePreview from '../components/preview/SitePreview';
import { useGoogleFonts } from '../components/preview/useGoogleFonts';
import { getColorMaps, getPreviewHtml } from '../api/site';
import { getImportPackage } from '../rebrand';
import { useAiFlow } from '../store/AiFlowContext';

const PreviewScreen = () => {
	const { state, dispatch } = useAiFlow();
	const pkg = state.pkg;
	const [confirmReset, setConfirmReset] = useState(false);
	const colorId = useId();
	// Only the home page is generated for now.
	const page = pkg?.pages.find((p) => p.slug === 'home') ?? pkg?.pages[0];
	const importable = !!pkg && !!getImportPackage(pkg);
	const primary = pkg?.brand.palette.primary ?? '';
	const designColor = pkg?.demo.primaryColor;

	// Applied once the picker settles, not on every step of a drag.
	const [draftColor, setDraftColor] = useState(primary);
	useEffect(() => setDraftColor(primary), [primary]);
	useEffect(() => {
		if (!pkg || draftColor === primary) return undefined;
		const timer = setTimeout(() => {
			// Regenerating keeps the color; the design's own color needs no override.
			dispatch({ type: 'UPDATE_FORM', patch: { primaryColor: draftColor === designColor ? undefined : draftColor } });
			dispatch({ type: 'UPDATE_BRAND', patch: { palette: { primary: draftColor } } });
			if (!importable) return;
			// The other swatches follow the backend's mapping; the preview only needs the primary.
			getColorMaps(pkg.demo.slug, draftColor)
				.then(({ palette }) => palette && dispatch({ type: 'UPDATE_BRAND', patch: { palette } }))
				.catch(() => undefined);
		}, 400);
		return () => clearTimeout(timer);
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [draftColor]);

	// The real page needs the live service; mock packages show section cards.
	const preview = useQuery({
		queryKey: ['ai-preview', pkg?.id, page?.slug, primary],
		queryFn: () => getPreviewHtml(pkg!, page!.slug),
		enabled: importable && !!page,
		staleTime: Infinity,
		retry: 1,
		// A new color re-renders the page; keep showing the last one meanwhile.
		placeholderData: keepPreviousData,
	});
	const showCards = !importable || preview.isError;

	useGoogleFonts(pkg && showCards ? [pkg.brand.fonts.heading, pkg.brand.fonts.body] : []);

	if (!pkg) {
		return null;
	}

	// Same description and design, fresh copy and images.
	const regenerate = () => dispatch({ type: 'SET_STAGE', stage: 'generating' });

	return (
		<div className="max-w-[1200px] mx-auto">
			<p className="text-[13px] font-medium uppercase tracking-wide text-[#2563EB] m-0 mb-2">
				{sprintf(
					/* translators: 1: current step, 2: total steps. */
					__('Step %1$d of %2$d', 'themegrill-demo-importer'),
					4,
					5,
				)}
			</p>
			<h1 className="text-[28px] leading-9 font-semibold text-[#1F1F1F] m-0 p-0">
				{sprintf(
					/* translators: %s: site title. */
					__('Preview of %s', 'themegrill-demo-importer'),
					pkg.brand.siteTitle,
				)}
			</h1>
			<p className="flex flex-wrap items-center gap-2 text-[14px] text-[#6B6B6B] mt-2 mb-6">
				{sprintf(
					/* translators: 1: design name, 2: heading font, 3: body font. */
					__('%1$s design · %2$s / %3$s', 'themegrill-demo-importer'),
					pkg.demo.name,
					pkg.brand.fonts.heading,
					pkg.brand.fonts.body,
				)}
				<span className="flex gap-1" aria-hidden="true">
					{Object.values(pkg.brand.palette).map((color, i) => (
						<span key={i} className="w-4 h-4 rounded-full border border-solid border-black/10" style={{ background: color }} />
					))}
				</span>
			</p>

			<div className="flex flex-wrap items-end gap-x-6 gap-y-3 mb-6">
				<div>
					<label htmlFor={colorId} className="block text-[14px] font-medium text-[#1F1F1F] mb-2">
						{__('Brand color', 'themegrill-demo-importer')}
					</label>
					<BrandColorField id={colorId} value={draftColor} onChange={setDraftColor} />
				</div>
				<div className="flex items-center gap-3 h-12">
					{designColor && draftColor !== designColor && (
						<Button
							type="button"
							variant="outline"
							onClick={() => setDraftColor(designColor)}
							className={cn(outlineButtonClass, 'h-10 px-4 gap-2 text-[14px]')}
						>
							<RotateCcw size={14} />
							{__('Use the design’s color', 'themegrill-demo-importer')}
						</Button>
					)}
					{preview.isFetching && !preview.isPending && (
						<span className="flex items-center gap-2 text-[13px] text-[#6B6B6B]" role="status">
							<Loader2 size={14} className="animate-spin" aria-hidden="true" />
							{__('Updating the preview…', 'themegrill-demo-importer')}
						</span>
					)}
				</div>
			</div>

			<div className="flex gap-3 items-start mb-6 p-4 rounded-lg border border-solid border-[#B9CDF9] bg-[#F5F8FF] text-[14px] leading-6 text-[#1E3A8A]">
				<PencilLine size={18} className="shrink-0 mt-[3px]" aria-hidden="true" />
				<p className="m-0">
					{__('Happy with it? Import it, and edit any text, image, color or font later in the WordPress editor. Not quite right? Regenerate for fresh copy and images.', 'themegrill-demo-importer')}
				</p>
			</div>

			{!!pkg.notes?.length && (
				<div className="flex gap-2 items-start mb-6 p-3 rounded-md border border-solid border-[#FDE68A] bg-[#FFFBEB] text-[14px] text-[#92400E]">
					<Info size={16} className="shrink-0 mt-[2px]" aria-hidden="true" />
					<ul className="m-0 p-0 list-none">
						{pkg.notes.map((note) => (
							<li key={note} className="m-0">
								{note}
							</li>
						))}
					</ul>
				</div>
			)}

			{showCards ? (
				<div className="flex flex-col gap-4">
					{page?.sections.map((section) => (
						<SectionPreview key={`${page.slug}-${section.id}`} section={section} brand={pkg.brand} />
					))}
				</div>
			) : (
				<SitePreview html={preview.data} loading={preview.isPending} title={pkg.brand.siteTitle} />
			)}

			<div className="sticky bottom-0 -mx-4 sm:-mx-10 lg:-mx-14 mt-8 px-4 sm:px-10 lg:px-14 py-4 bg-white border-0 border-t border-solid border-[#E9E9E9] flex flex-wrap items-center justify-between gap-3">
				{confirmReset ? (
					<div className="flex flex-wrap items-center gap-2 text-[14px] text-[#383838]">
						{__('Discard this site and start over?', 'themegrill-demo-importer')}
						<Button type="button" onClick={() => dispatch({ type: 'RESET' })} className={cn(primaryButtonClass, 'h-9 px-4 bg-[#DC2626] hover:bg-[#B91C1C]')}>
							{__('Start over', 'themegrill-demo-importer')}
						</Button>
						<Button type="button" variant="outline" onClick={() => setConfirmReset(false)} className={cn(outlineButtonClass, 'h-9 px-4')}>
							{__('Cancel', 'themegrill-demo-importer')}
						</Button>
					</div>
				) : (
					<Button type="button" variant="outline" onClick={() => setConfirmReset(true)} className={cn(outlineButtonClass, 'h-11 px-5')}>
						{__('Start over', 'themegrill-demo-importer')}
					</Button>
				)}
				<div className="flex flex-wrap items-center gap-3">
					{!importable && (
						<span className="text-[13px] text-[#6B6B6B]">
							{__('Preview only: importing needs the live AI service.', 'themegrill-demo-importer')}
						</span>
					)}
					<Button type="button" variant="outline" onClick={regenerate} className={cn(outlineButtonClass, 'h-11 px-5 gap-2')}>
						<RefreshCw size={16} />
						{__('Regenerate', 'themegrill-demo-importer')}
					</Button>
					<Button
						type="button"
						disabled={!importable}
						onClick={() => dispatch({ type: 'SET_STAGE', stage: 'confirm' })}
						className={cn(primaryButtonClass, 'h-11 px-6 gap-2')}
					>
						{__('Import this site', 'themegrill-demo-importer')}
						<ArrowRight size={16} />
					</Button>
				</div>
			</div>
		</div>
	);
};

export default PreviewScreen;
