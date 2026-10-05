import { __, sprintf } from '@wordpress/i18n';
import { ArrowRight, Info, Loader2 } from 'lucide-react';
import { useState } from 'react';
import { Button } from '../../components/ui/Button';
import { cn } from '../../lib/utils';
import { outlineButtonClass, primaryButtonClass } from '../components/fields';
import BrandPanel from '../components/preview/BrandPanel';
import DesignPanel from '../components/preview/DesignPanel';
import SectionCard from '../components/preview/SectionCard';
import { useGoogleFonts } from '../components/preview/useGoogleFonts';
import { getImportPackage } from '../rebrand';
import { useAiFlow } from '../store/AiFlowContext';

const panelClass = 'p-4 rounded-lg border border-solid border-[#E9E9E9] bg-white';
const panelTitle = 'm-0 mb-3 text-[13px] font-semibold uppercase tracking-wide text-[#6B6B6B]';

const PreviewScreen = () => {
	const { state, dispatch } = useAiFlow();
	const pkg = state.pkg;
	const [pageSlug, setPageSlug] = useState(pkg?.pages[0]?.slug ?? 'home');
	const [switching, setSwitching] = useState(false);
	const [confirmReset, setConfirmReset] = useState(false);

	useGoogleFonts(pkg ? [pkg.brand.fonts.heading, pkg.brand.fonts.body] : []);

	if (!pkg) {
		return null;
	}

	const page = pkg.pages.find((p) => p.slug === pageSlug) ?? pkg.pages[0];
	const importable = !!getImportPackage(pkg);

	return (
		<div className="max-w-[1200px] mx-auto">
			<p className="text-[13px] font-medium uppercase tracking-wide text-[#2563EB] m-0 mb-2">
				{sprintf(
					/* translators: 1: current step, 2: total steps. */
					__('Step %1$d of %2$d', 'themegrill-demo-importer'),
					3,
					4,
				)}
			</p>
			<h1 className="text-[28px] leading-9 font-semibold text-[#1F1F1F] m-0 p-0">
				{__('Preview and tweak', 'themegrill-demo-importer')}
			</h1>
			<p className="text-[15px] leading-6 text-[#6B6B6B] mt-2 mb-6">
				{__('Click any text to edit it, swap images, or rewrite a section. Nothing changes on your site until you import.', 'themegrill-demo-importer')}
			</p>

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

			<div className="grid gap-6 lg:grid-cols-[300px_minmax(0,1fr)] items-start">
				<aside className="flex flex-col gap-4 lg:sticky lg:top-0">
					<div className={panelClass}>
						<h2 className={panelTitle}>{__('Design', 'themegrill-demo-importer')}</h2>
						<DesignPanel pkg={pkg} onBusy={setSwitching} />
					</div>
					<div className={panelClass}>
						<h2 className={panelTitle}>{__('Brand', 'themegrill-demo-importer')}</h2>
						<BrandPanel brand={pkg.brand} />
					</div>
				</aside>

				<div className="relative min-w-0">
					<div role="tablist" aria-label={__('Pages', 'themegrill-demo-importer')} className="flex flex-wrap gap-2 mb-4">
						{pkg.pages.map((p) => (
							<button
								key={p.slug}
								type="button"
								role="tab"
								aria-selected={p.slug === page?.slug}
								onClick={() => setPageSlug(p.slug)}
								className={cn(
									'h-9 px-4 rounded-full border-2 border-solid text-[14px] cursor-pointer',
									p.slug === page?.slug
										? 'bg-[#2563EB] border-[#2563EB] text-white'
										: 'bg-white border-[#EBEDEF] text-[#383838] hover:border-[#B9CDF9]',
								)}
							>
								{p.title}
							</button>
						))}
					</div>

					<div role="tabpanel" className="flex flex-col gap-4">
						{page && page.sections.length > 0 ? (
							page.sections.map((section) => (
								<SectionCard key={`${page.slug}-${section.id}`} page={page.slug} section={section} brand={pkg.brand} generationId={pkg.id} />
							))
						) : (
							<p className="m-0 p-6 rounded-lg border border-dashed border-[#E2E8F0] text-[14px] text-[#6B6B6B] text-center">
								{__('This page is filled in automatically (for example, your latest blog posts), so there is nothing to edit here.', 'themegrill-demo-importer')}
							</p>
						)}
					</div>

					{switching && (
						<div className="absolute inset-0 flex items-start justify-center pt-24 bg-white/70" role="status">
							<span className="flex items-center gap-2 px-4 py-2 rounded-full bg-white shadow text-[14px] text-[#1F1F1F]">
								<Loader2 size={16} className="animate-spin text-[#2563EB]" />
								{__('Writing content for the new design…', 'themegrill-demo-importer')}
							</span>
						</div>
					)}
				</div>
			</div>

			<div className="sticky bottom-0 -mx-4 sm:-mx-10 lg:-mx-14 mt-8 px-4 sm:px-10 lg:px-14 py-4 bg-white border-0 border-t border-solid border-[#E9E9E9] flex flex-wrap items-center justify-between gap-3">
				{confirmReset ? (
					<div className="flex flex-wrap items-center gap-2 text-[14px] text-[#383838]">
						{__('Discard this site and start over?', 'themegrill-demo-importer')}
						<Button type="button" onClick={() => dispatch({ type: 'RESET' })} className={cn(primaryButtonClass, 'h-9 px-4 bg-[#DC2626] hover:bg-[#B91C1C]')}>
							{__('Start over', 'themegrill-demo-importer')}
						</Button>
						<Button type="button" variant="outline" onClick={() => setConfirmReset(false)} className={cn(outlineButtonClass, 'h-9 px-4')}>
							{__('Keep editing', 'themegrill-demo-importer')}
						</Button>
					</div>
				) : (
					<Button type="button" variant="outline" onClick={() => setConfirmReset(true)} className={cn(outlineButtonClass, 'h-11 px-5')}>
						{__('Start over', 'themegrill-demo-importer')}
					</Button>
				)}
				<div className="flex items-center gap-3">
					{!importable && (
						<span className="text-[13px] text-[#6B6B6B]">
							{__('Preview only: importing needs the live AI service.', 'themegrill-demo-importer')}
						</span>
					)}
					<Button
						type="button"
						disabled={!importable || switching}
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
