import { __, _n, sprintf } from '@wordpress/i18n';
import { ChevronDown, Loader2, RotateCcw, X } from 'lucide-react';
import { useState } from 'react';
import { cn } from '../../../lib/utils';
import { getAiClient } from '../../api/client';
import { toAiApiError } from '../../api/errors';
import { useAiFlow } from '../../store/AiFlowContext';
import { GeneratedPage, RemovedGroup } from '../../types';

type Props = { page: GeneratedPage; generationId: string };

const linkButton =
	'flex items-center gap-1 shrink-0 bg-transparent border-0 p-1 text-[13px] text-[#2563EB] cursor-pointer hover:underline disabled:opacity-60 disabled:cursor-default';

// Sections the AI left out because they don't fit the business, collapsed
// below the page. Restoring one writes copy for it before it shows up.
const RemovedSections = ({ page, generationId }: Props) => {
	const { dispatch } = useAiFlow();
	const [busy, setBusy] = useState('');
	const [error, setError] = useState('');
	const removed = page.removed ?? [];

	if (!removed.length) {
		return null;
	}

	const restore = async (group: RemovedGroup) => {
		setBusy(group.groupId);
		setError('');
		try {
			// Removed sections still hold the template's copy; image-only rows have none to write.
			const sections = await Promise.all(
				group.sections.map((section) =>
					Object.values(section.slots).some((v) => typeof v === 'string')
						? getAiClient()
								.regenerateSection({ generationId, page: page.slug, sectionId: section.id })
								.then((r) => r.section)
						: section,
				),
			);
			dispatch({ type: 'SET_GROUP_RESTORED', page: page.slug, groupId: group.groupId, restored: true, sections });
		} catch (e) {
			setError(toAiApiError(e).message);
		} finally {
			setBusy('');
		}
	};

	const left = removed.filter((r) => !r.restored).length;

	return (
		<details className="group mt-4 rounded-lg border border-dashed border-[#E2E8F0] bg-[#FAFBFC]">
			<summary className="flex items-center justify-between gap-2 px-4 py-3 cursor-pointer list-none text-[14px] text-[#383838]">
				{sprintf(
					/* translators: %d: number of sections. */
					_n('%d section left out for your business', '%d sections left out for your business', left, 'themegrill-demo-importer'),
					left,
				)}
				<ChevronDown size={16} aria-hidden="true" className="transition-transform group-open:rotate-180" />
			</summary>
			<ul className="m-0 px-4 pb-3 list-none flex flex-col gap-2">
				{removed.map((group) => (
					<li key={group.groupId} className="flex items-start justify-between gap-3 m-0 p-3 rounded-md bg-white border border-solid border-[#EBEDEF]">
						<div className="min-w-0">
							<p className={cn('m-0 text-[14px] font-medium', group.restored ? 'text-[#1F1F1F]' : 'text-[#6B6B6B]')}>
								{group.title}
							</p>
							<p className="m-0 mt-1 text-[13px] text-[#909090]">
								{group.restored ? __('Restored and rewritten for your business.', 'themegrill-demo-importer') : group.reason}
							</p>
						</div>
						{group.restored ? (
							<button
								type="button"
								onClick={() => dispatch({ type: 'SET_GROUP_RESTORED', page: page.slug, groupId: group.groupId, restored: false })}
								className={linkButton}
							>
								<X size={14} aria-hidden="true" />
								{__('Remove', 'themegrill-demo-importer')}
							</button>
						) : (
							<button type="button" disabled={!!busy} onClick={() => restore(group)} className={linkButton}>
								{busy === group.groupId ? (
									<Loader2 size={14} className="animate-spin" aria-hidden="true" />
								) : (
									<RotateCcw size={14} aria-hidden="true" />
								)}
								{busy === group.groupId ? __('Restoring…', 'themegrill-demo-importer') : __('Restore', 'themegrill-demo-importer')}
							</button>
						)}
					</li>
				))}
			</ul>
			{error && (
				<p role="alert" className="m-0 px-4 pb-3 text-[13px] text-[#DC2626]">
					{error}
				</p>
			)}
		</details>
	);
};

export default RemovedSections;
