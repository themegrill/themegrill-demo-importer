import { __, sprintf } from '@wordpress/i18n';
import { Loader2 } from 'lucide-react';
import { useState } from 'react';
import { Button } from '../../../components/ui/Button';
import { cn } from '../../../lib/utils';
import { getAiClient } from '../../api/client';
import { toAiApiError } from '../../api/errors';
import { useAiFlow } from '../../store/AiFlowContext';
import { DemoSummary, GenerationPackage } from '../../types';
import { outlineButtonClass, primaryButtonClass } from '../fields';

const Thumb = ({ demo, className }: { demo: DemoSummary; className?: string }) => (
	<img
		src={demo.thumbnail}
		alt=""
		loading="lazy"
		className={cn('block w-full aspect-[.84/1] object-cover object-top rounded-md border border-solid border-[#E9E9E9] bg-[#F1F5F9]', className)}
	/>
);

const DesignPanel = ({ pkg, onBusy }: { pkg: GenerationPackage; onBusy: (busy: boolean) => void }) => {
	const { dispatch } = useAiFlow();
	const [open, setOpen] = useState(false);
	const [pending, setPending] = useState<DemoSummary | null>(null);
	const [busy, setBusy] = useState(false);
	const [error, setError] = useState('');

	const switchTo = async (demo: DemoSummary) => {
		setBusy(true);
		onBusy(true);
		setError('');
		try {
			const next = await getAiClient().switchDemo({ generationId: pkg.id, demoSlug: demo.slug });
			// Keep the brand kit as tuned here; the server only knows the original.
			dispatch({ type: 'SET_PACKAGE', pkg: { ...next, brand: pkg.brand } });
			setOpen(false);
			setPending(null);
		} catch (e) {
			setError(toAiApiError(e).message);
		} finally {
			setBusy(false);
			onBusy(false);
		}
	};

	return (
		<div className="flex flex-col gap-3">
			<div className="flex gap-3 items-start">
				<Thumb demo={pkg.demo} className="w-[72px] shrink-0" />
				<div className="min-w-0">
					<p className="m-0 text-[15px] font-medium text-[#1F1F1F]">{pkg.demo.name}</p>
					{pkg.alternatives.length > 0 && (
						<button
							type="button"
							onClick={() => setOpen((o) => !o)}
							aria-expanded={open}
							className="mt-1 p-0 bg-transparent border-0 text-[13px] text-[#2563EB] cursor-pointer hover:underline"
						>
							{open ? __('Keep this design', 'themegrill-demo-importer') : __('Try a different design', 'themegrill-demo-importer')}
						</button>
					)}
				</div>
			</div>

			{open && (
				<div className="flex flex-col gap-2">
					<div className="grid grid-cols-3 gap-2">
						{pkg.alternatives.map((demo) => (
							<button
								key={demo.slug}
								type="button"
								onClick={() => setPending(demo)}
								disabled={busy}
								aria-pressed={pending?.slug === demo.slug}
								className={cn(
									'p-1 bg-white border-2 border-solid rounded-md cursor-pointer text-left',
									pending?.slug === demo.slug ? 'border-[#2563EB]' : 'border-transparent hover:border-[#B9CDF9]',
								)}
							>
								<Thumb demo={demo} />
								<span className="block mt-1 text-[12px] leading-4 text-[#383838] truncate">{demo.name}</span>
							</button>
						))}
					</div>
					{pending && (
						<div className="p-3 rounded-md bg-[#FFFBEB] border border-solid border-[#FDE68A]">
							<p className="m-0 text-[13px] text-[#92400E]">
								{sprintf(
									/* translators: %s: design name. */
									__('Switching to %s writes new content for that design. Your text edits will be lost; colors and fonts are kept.', 'themegrill-demo-importer'),
									pending.name,
								)}
							</p>
							<div className="flex gap-2 mt-2">
								<Button type="button" onClick={() => switchTo(pending)} disabled={busy} className={cn(primaryButtonClass, 'h-8 px-3 gap-2 text-[13px]')}>
									{busy && <Loader2 size={14} className="animate-spin" />}
									{busy ? __('Switching…', 'themegrill-demo-importer') : __('Switch design', 'themegrill-demo-importer')}
								</Button>
								<Button type="button" variant="outline" onClick={() => setPending(null)} disabled={busy} className={cn(outlineButtonClass, 'h-8 px-3 text-[13px]')}>
									{__('Cancel', 'themegrill-demo-importer')}
								</Button>
							</div>
						</div>
					)}
					{error && (
						<p role="alert" className="m-0 text-[13px] text-[#DC2626]">
							{error}
						</p>
					)}
				</div>
			)}
		</div>
	);
};

export default DesignPanel;
