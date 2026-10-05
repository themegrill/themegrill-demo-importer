import { useNavigate } from '@tanstack/react-router';
import { __ } from '@wordpress/i18n';
import { ArrowRight, Sparkles } from 'lucide-react';
import { Button } from '../../components/ui/Button';
import { getAiConfig } from '../config';

const AiEntryCard = () => {
	const navigate = useNavigate();

	if (!getAiConfig().enabled) {
		return null;
	}

	return (
		<div className="rounded-md p-5 border-2 border-solid border-[#B9CDF9] bg-gradient-to-br from-[#EEF3FF] to-[#FFFFFF]">
			<div className="flex items-center gap-2 mb-2">
				<Sparkles size={18} color="#2563EB" aria-hidden="true" />
				<h3 className="text-[16px] text-[#1F1F1F] m-0">
					{__('Build with AI', 'themegrill-demo-importer')}
				</h3>
				<span className="ml-auto px-2 py-[2px] rounded-[4px] bg-[#2563EB] text-white text-[11px] font-medium uppercase tracking-wide">
					{__('New', 'themegrill-demo-importer')}
				</span>
			</div>
			<p className="text-[14px] leading-[21px] text-[#4B4B4B] mt-0 mb-4">
				{__(
					'Describe your business and get a ready-made site with your brand colors, copy and images.',
					'themegrill-demo-importer',
				)}
			</p>
			<Button
				type="button"
				onClick={() => navigate({ to: '/ai' })}
				className="w-full h-11 gap-2 cursor-pointer border-0 bg-[#2563EB] text-white text-[15px] hover:bg-[#134FD2]"
			>
				{__('Start building', 'themegrill-demo-importer')}
				<ArrowRight size={16} />
			</Button>
		</div>
	);
};

export default AiEntryCard;
