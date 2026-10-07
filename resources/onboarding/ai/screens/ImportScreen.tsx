import { useQuery } from '@tanstack/react-query';
import { __, sprintf } from '@wordpress/i18n';
import { AlertCircle, AlertTriangle, ArrowLeft, Check, ExternalLink, Loader2, Paintbrush, Sparkles } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { Button } from '../../components/ui/Button';
import { Progress } from '../../components/ui/Progress';
import { Demo } from '../../lib/types';
import { cn } from '../../lib/utils';
import { useLocalizedData } from '../../LocalizedDataContext';
import { applySite, friendlyError, getColorMaps, getDemoConfig, ImportAction, prepareTheme, runImportAction } from '../api/site';
import { outlineButtonClass, primaryButtonClass } from '../components/fields';
import { buildApplyPayload, buildFontMap, getImportPackage, rebrandDemoConfig } from '../rebrand';
import { useAiFlow } from '../store/AiFlowContext';

type Step = { key: 'prepare-theme' | ImportAction | 'apply'; label: string; weight: number };

const STEPS: Step[] = [
	{ key: 'prepare-theme', label: __('Setting up the Zakra theme', 'themegrill-demo-importer'), weight: 8 },
	{ key: 'install-plugins', label: __('Installing plugins', 'themegrill-demo-importer'), weight: 12 },
	{ key: 'import-content', label: __('Preparing content', 'themegrill-demo-importer'), weight: 5 },
	{ key: 'import-content-posts', label: __('Creating pages', 'themegrill-demo-importer'), weight: 22 },
	{ key: 'import-media', label: __('Downloading images', 'themegrill-demo-importer'), weight: 28 },
	{ key: 'import-customizer', label: __('Applying theme settings', 'themegrill-demo-importer'), weight: 6 },
	{ key: 'import-widgets', label: __('Setting up widgets', 'themegrill-demo-importer'), weight: 4 },
	{ key: 'apply', label: __('Adding your content and brand', 'themegrill-demo-importer'), weight: 12 },
	{ key: 'complete', label: __('Finishing up', 'themegrill-demo-importer'), weight: 3 },
];

const BATCHED: Step['key'][] = ['import-content-posts', 'import-media'];

type Phase = 'confirm' | 'importing' | 'failed' | 'done';

const ImportScreen = () => {
	const { state, dispatch } = useAiFlow();
	const { localizedData } = useLocalizedData();
	const pkg = state.pkg!;
	const ip = getImportPackage(pkg);

	const demoQuery = useQuery({
		queryKey: ['ai-demo-config', pkg.demo.slug],
		queryFn: () => getDemoConfig(pkg.demo.slug),
		staleTime: Infinity,
		retry: 1,
	});
	const demo = demoQuery.data;

	const [phase, setPhase] = useState<Phase>('confirm');
	const [deselected, setDeselected] = useState<string[]>([]);
	const [progress, setProgress] = useState(0);
	const [current, setCurrent] = useState<Step['key'] | null>(null);
	const [error, setError] = useState('');
	const running = useRef(false);

	// Leaving mid-import would strand a half-built site.
	useEffect(() => {
		if (phase !== 'importing') return;
		const warn = (e: BeforeUnloadEvent) => e.preventDefault();
		window.addEventListener('beforeunload', warn);
		return () => window.removeEventListener('beforeunload', warn);
	}, [phase]);

	const plugins = Object.entries(demo?.plugins ?? {}).sort(([, a], [, b]) => Number(b.mandatory) - Number(a.mandatory));
	const zakraActive = localizedData.current_theme === 'zakra';
	const zakraInstalled = localizedData.installed_themes?.includes('zakra');

	const run = async (config: Demo) => {
		if (running.current || !ip) return;
		running.current = true;
		setPhase('importing');
		setError('');
		setProgress(0);
		dispatch({ type: 'SET_STAGE', stage: 'importing' });

		const selectedPlugins = plugins.map(([path]) => path).filter((path) => !deselected.includes(path));
		let done = 0;

		try {
			// The palette may have been edited in the preview; the backend owns the mapping.
			const colorMaps = await getColorMaps(pkg.demo.slug, pkg.brand.palette);
			const brandedDemo = rebrandDemoConfig(config, colorMaps.theme, buildFontMap(ip, pkg.brand.fonts), pkg.brand);

			for (const step of STEPS) {
				setCurrent(step.key);
				if (step.key === 'prepare-theme') {
					await prepareTheme();
				} else if (step.key === 'apply') {
					await applySite(buildApplyPayload(pkg, ip, config, colorMaps.blocks));
				} else if (BATCHED.includes(step.key)) {
					for (;;) {
						const batch = await runImportAction(step.key as ImportAction, brandedDemo, selectedPlugins);
						if (batch?.total > 0) {
							const imported = Math.min(batch.total, Math.max(0, batch.total - (batch.remaining ?? 0)));
							setProgress(done + (imported / batch.total) * step.weight);
						}
						if (!batch || batch.done) break;
					}
				} else {
					const result = await runImportAction(step.key as ImportAction, brandedDemo, selectedPlugins);
					// install-plugins reports per-plugin failures in a 200 response.
					if (step.key === 'install-plugins' && Array.isArray(result)) {
						const failed = result.flatMap((entry: Record<string, { status: string; message: string }>) =>
							Object.entries(entry)
								.filter(([, r]) => r?.status === 'error')
								.map(([slug, r]) => `${slug}: ${r.message}`),
						);
						if (failed.length) throw new Error(`Failed to install plugin(s) — ${failed.join('; ')}`);
					}
				}
				done += step.weight;
				setProgress(done);
			}
			setPhase('done');
			dispatch({ type: 'SET_STAGE', stage: 'success' });
		} catch (e) {
			setError(friendlyError(e));
			setPhase('failed');
		} finally {
			running.current = false;
		}
	};

	if (!ip) {
		return null;
	}

	const backToPreview = () => dispatch({ type: 'SET_STAGE', stage: 'preview' });
	const origin = window.location.origin;

	if (phase === 'done') {
		return (
			<div className="max-w-[640px] mx-auto text-center pt-6">
				<span className="inline-flex items-center justify-center w-16 h-16 rounded-full bg-[#DCFCE7] text-[#16A34A] mb-4">
					<Check size={32} strokeWidth={3} aria-hidden="true" />
				</span>
				<h1 className="text-[28px] leading-9 font-semibold text-[#1F1F1F] m-0">
					{sprintf(
						/* translators: %s: site title. */
						__('%s is live', 'themegrill-demo-importer'),
						pkg.brand.siteTitle,
					)}
				</h1>
				<p className="text-[15px] leading-6 text-[#6B6B6B] mt-2 mb-8">
					{__('Your pages, brand colors, fonts and copy are in place. Fine-tune anything in the block editor or the Customizer.', 'themegrill-demo-importer')}
				</p>
				<div className="flex flex-wrap justify-center gap-3">
					<Button asChild className={cn(primaryButtonClass, 'h-11 px-6 gap-2 no-underline')}>
						<a href={localizedData.siteUrl || origin} target="_blank" rel="noreferrer">
							<ExternalLink size={16} />
							{__('View site', 'themegrill-demo-importer')}
						</a>
					</Button>
					<Button asChild variant="outline" className={cn(outlineButtonClass, 'h-11 px-6 gap-2 no-underline')}>
						<a href={`${origin}/wp-admin/customize.php`}>
							<Paintbrush size={16} />
							{__('Customize', 'themegrill-demo-importer')}
						</a>
					</Button>
					<Button type="button" variant="outline" onClick={() => dispatch({ type: 'RESET' })} className={cn(outlineButtonClass, 'h-11 px-6 gap-2')}>
						<Sparkles size={16} />
						{__('Generate another', 'themegrill-demo-importer')}
					</Button>
				</div>
			</div>
		);
	}

	if (phase === 'importing' || phase === 'failed') {
		const currentIndex = STEPS.findIndex((s) => s.key === current);
		return (
			<div className="max-w-[640px] mx-auto">
				<h1 className="text-[28px] leading-9 font-semibold text-[#1F1F1F] m-0">
					{phase === 'failed'
						? __('The import stopped', 'themegrill-demo-importer')
						: sprintf(
								/* translators: %s: site title. */
								__('Building %s on your site', 'themegrill-demo-importer'),
								pkg.brand.siteTitle,
							)}
				</h1>
				<p className="text-[15px] leading-6 text-[#6B6B6B] mt-2 mb-8">
					{phase === 'failed'
						? __('Some content may already have been added.', 'themegrill-demo-importer')
						: __('Keep this page open. Downloading images can take a few minutes.', 'themegrill-demo-importer')}
				</p>

				<Progress
					value={progress}
					aria-label={__('Import progress', 'themegrill-demo-importer')}
					className="h-2 bg-[#E8EEFD]"
					indicatorClassName={phase === 'failed' ? 'bg-[#DC2626]' : undefined}
					indicatorStyle={{ transform: `translateX(-${100 - progress}%)` }}
				/>
				<p className="mt-2 mb-6 text-[13px] text-[#6B6B6B] tabular-nums">{Math.round(progress)}%</p>

				<ol className="list-none m-0 p-0 flex flex-col gap-3" aria-live="polite">
					{STEPS.map((step, index) => {
						const complete = index < currentIndex;
						const active = index === currentIndex;
						const failedHere = active && phase === 'failed';
						return (
							<li key={step.key} className="flex items-center gap-3 m-0 text-[15px]">
								<span
									aria-hidden="true"
									className={cn(
										'flex items-center justify-center w-6 h-6 rounded-full shrink-0',
										complete && 'bg-[#2563EB] text-white',
										active && !failedHere && 'bg-[#E8EEFD] text-[#2563EB]',
										failedHere && 'bg-[#FEE2E2] text-[#DC2626]',
										!complete && !active && 'bg-[#F4F4F5]',
									)}
								>
									{complete ? <Check size={12} strokeWidth={3} /> : failedHere ? <AlertCircle size={14} /> : active ? <Loader2 size={14} className="animate-spin" /> : null}
								</span>
								<span className={cn(complete || active ? 'text-[#1F1F1F]' : 'text-[#909090]', active && 'font-medium')}>
									{step.label}
								</span>
							</li>
						);
					})}
				</ol>

				{phase === 'failed' && (
					<div role="alert" className="mt-8 p-4 rounded-lg border border-solid border-[#FECACA] bg-[#FEF2F2]">
						<p className="m-0 text-[14px] text-[#991B1B]">{error}</p>
						<div className="flex flex-wrap gap-3 mt-4">
							<Button type="button" onClick={() => demo && run(demo)} className={cn(primaryButtonClass, 'h-10 px-5')}>
								{__('Try again', 'themegrill-demo-importer')}
							</Button>
							<Button
								type="button"
								variant="outline"
								onClick={() => {
									setPhase('confirm');
									backToPreview();
								}}
								className={cn(outlineButtonClass, 'h-10 px-5 gap-2')}
							>
								<ArrowLeft size={16} />
								{__('Back to preview', 'themegrill-demo-importer')}
							</Button>
						</div>
					</div>
				)}
			</div>
		);
	}

	const pageCount = demo?.pages?.length ?? ip.pages.length;

	return (
		<div className="max-w-[720px] mx-auto">
			<p className="text-[13px] font-medium uppercase tracking-wide text-[#2563EB] m-0 mb-2">
				{sprintf(
					/* translators: 1: current step, 2: total steps. */
					__('Step %1$d of %2$d', 'themegrill-demo-importer'),
					4,
					4,
				)}
			</p>
			<h1 className="text-[28px] leading-9 font-semibold text-[#1F1F1F] m-0 p-0">
				{__('Ready to import', 'themegrill-demo-importer')}
			</h1>
			<p className="text-[15px] leading-6 text-[#6B6B6B] mt-2 mb-6">
				{sprintf(
					/* translators: 1: site title, 2: design name. */
					__('%1$s will be built on your site from the %2$s design.', 'themegrill-demo-importer'),
					pkg.brand.siteTitle,
					pkg.demo.name,
				)}
			</p>

			{demoQuery.isLoading && (
				<p className="flex items-center gap-2 text-[14px] text-[#6B6B6B]">
					<Loader2 size={16} className="animate-spin" />
					{__('Checking what this design needs…', 'themegrill-demo-importer')}
				</p>
			)}
			{demoQuery.isError && (
				<div role="alert" className="p-4 rounded-lg border border-solid border-[#FECACA] bg-[#FEF2F2] text-[14px] text-[#991B1B]">
					{friendlyError(demoQuery.error)}
					<Button type="button" onClick={() => demoQuery.refetch()} className={cn(primaryButtonClass, 'h-9 px-4 ml-3')}>
						{__('Retry', 'themegrill-demo-importer')}
					</Button>
				</div>
			)}

			{demo && (
				<>
					<h2 className="text-[16px] font-semibold text-[#1F1F1F] m-0 mb-3">{__('What will happen', 'themegrill-demo-importer')}</h2>
					<ul className="m-0 mb-8 p-0 list-none flex flex-col gap-2 text-[14px] text-[#383838]">
						{!zakraActive && (
							<li className="flex gap-2 m-0">
								<Check size={16} className="shrink-0 mt-[2px] text-[#2563EB]" aria-hidden="true" />
								{zakraInstalled
									? __('The Zakra theme is activated (it replaces your current theme).', 'themegrill-demo-importer')
									: __('The free Zakra theme is installed from WordPress.org and activated (it replaces your current theme).', 'themegrill-demo-importer')}
							</li>
						)}
						{[
							sprintf(
								/* translators: %d: number of pages. */
								__('%d pages are created, with your copy on the pages you previewed.', 'themegrill-demo-importer'),
								pageCount,
							),
							__('Theme settings, menus and widgets are replaced and set to your brand colors and fonts.', 'themegrill-demo-importer'),
							__('Images are downloaded to your media library.', 'themegrill-demo-importer'),
							sprintf(
								/* translators: %s: site title. */
								__('Your site title becomes “%s”.', 'themegrill-demo-importer'),
								pkg.brand.siteTitle,
							),
						].map((item) => (
							<li key={item} className="flex gap-2 m-0">
								<Check size={16} className="shrink-0 mt-[2px] text-[#2563EB]" aria-hidden="true" />
								{item}
							</li>
						))}
					</ul>

					{plugins.length > 0 && (
						<fieldset className="m-0 mb-8 p-0 border-0 min-w-0">
							<legend className="p-0 text-[16px] font-semibold text-[#1F1F1F] mb-3">{__('Plugins', 'themegrill-demo-importer')}</legend>
							<div className="flex flex-col gap-2">
								{plugins.map(([path, info]) => {
									const checked = info.mandatory || !deselected.includes(path);
									return (
										<label key={path} className={cn('flex items-center gap-3 p-3 rounded-md border border-solid border-[#EBEDEF] text-[14px]', info.mandatory ? 'cursor-default' : 'cursor-pointer')}>
											<input
												type="checkbox"
												checked={checked}
												disabled={info.mandatory}
												onChange={() =>
													setDeselected((d) => (d.includes(path) ? d.filter((p) => p !== path) : [...d, path]))
												}
												className="!m-0"
											/>
											<span className="text-[#1F1F1F]">{info.name}</span>
											{info.mandatory && (
												<span className="ml-auto text-[12px] text-[#909090]">{__('Required', 'themegrill-demo-importer')}</span>
											)}
										</label>
									);
								})}
							</div>
						</fieldset>
					)}

					<div className="flex gap-2 items-start p-4 mb-8 rounded-md border border-solid border-[#FDE68A] bg-[#FFFBEB] text-[14px] text-[#92400E]">
						<AlertTriangle size={16} className="shrink-0 mt-[2px]" aria-hidden="true" />
						{__('Your existing posts and pages are not deleted, but the imported pages, menus and theme settings take over the site’s front end. Back up first if this is a live site.', 'themegrill-demo-importer')}
					</div>
				</>
			)}

			<div className="flex flex-col-reverse sm:flex-row sm:justify-between gap-3 pt-6 border-0 border-t border-solid border-[#E9E9E9]">
				<Button type="button" variant="outline" onClick={backToPreview} className={cn(outlineButtonClass, 'h-12 px-6 gap-2')}>
					<ArrowLeft size={16} />
					{__('Back to preview', 'themegrill-demo-importer')}
				</Button>
				<Button type="button" disabled={!demo} onClick={() => demo && run(demo)} className={cn(primaryButtonClass, 'h-12 px-8')}>
					{__('Import site', 'themegrill-demo-importer')}
				</Button>
			</div>
		</div>
	);
};

export default ImportScreen;
