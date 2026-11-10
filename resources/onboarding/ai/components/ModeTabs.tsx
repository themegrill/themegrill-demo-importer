import { useRouter } from '@tanstack/react-router';
import { __ } from '@wordpress/i18n';
import { LayoutGrid, Sparkles } from 'lucide-react';
import { cn } from '../../lib/utils';
import { getAiConfig } from '../config';

type Mode = 'templates' | 'ai';

// Switches between the template library and the Build with AI flow.
const ModeTabs = ({ active }: { active: Mode }) => {
	const router = useRouter();

	if (!getAiConfig().enabled) {
		return null;
	}

	const go = (mode: Mode) => {
		if (mode === active) return;
		router.navigate(
			mode === 'ai'
				? { to: '/ai' }
				: { to: '/', search: { search: undefined, builder: undefined, category: undefined } },
		);
	};

	const tabClass = (mode: Mode) =>
		cn(
			'flex items-center gap-2 h-10 px-4 rounded-md border-0 cursor-pointer text-[14px] font-medium transition-colors',
			mode === active
				? 'bg-white text-[#1F1F1F] shadow-sm'
				: 'bg-transparent text-[#4B4B4B] hover:text-[#1F1F1F]',
		);

	return (
		<div role="tablist" className="inline-flex items-center gap-1 p-1 rounded-lg bg-[#F1F3F5]">
			<button
				type="button"
				role="tab"
				aria-selected={active === 'templates'}
				onClick={() => go('templates')}
				className={tabClass('templates')}
			>
				<LayoutGrid size={16} />
				{__('Templates', 'themegrill-demo-importer')}
			</button>
			<button
				type="button"
				role="tab"
				aria-selected={active === 'ai'}
				onClick={() => go('ai')}
				className={tabClass('ai')}
			>
				<Sparkles size={16} color="#2563EB" />
				{__('Build with AI', 'themegrill-demo-importer')}
				<span className="px-[6px] py-[1px] rounded-[4px] bg-[#2563EB] text-white text-[10px] font-semibold uppercase tracking-wide">
					{__('New', 'themegrill-demo-importer')}
				</span>
			</button>
		</div>
	);
};

export default ModeTabs;
