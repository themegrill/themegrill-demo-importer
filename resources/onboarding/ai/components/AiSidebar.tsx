import { useRouter } from '@tanstack/react-router';
import { __ } from '@wordpress/i18n';
import { ArrowLeft, Check, Sparkles, X } from 'lucide-react';
import logo from '../../assets/images/starter-template-logo.png';
import { Tooltip, TooltipContent, TooltipTrigger } from '../../components/ui/Tooltip';
import { cn } from '../../lib/utils';
import { AiStage } from '../store/AiFlowContext';

export const FLOW_STEPS: { label: string; stages: AiStage[] }[] = [
	{ label: __('Describe your site', 'themegrill-demo-importer'), stages: ['describe'] },
	{ label: __('Generate', 'themegrill-demo-importer'), stages: ['generating'] },
	{ label: __('Preview & tweak', 'themegrill-demo-importer'), stages: ['preview'] },
	{ label: __('Import', 'themegrill-demo-importer'), stages: ['confirm', 'importing', 'success'] },
];

export const getStepIndex = (stage: AiStage) =>
	Math.max(
		0,
		FLOW_STEPS.findIndex((s) => s.stages.includes(stage)),
	);

export const useBackToTemplates = () => {
	const router = useRouter();
	return () =>
		router.navigate({
			to: '/',
			search: { search: undefined, builder: undefined, category: undefined },
		});
};

const AiSidebar = ({ stage }: { stage: AiStage }) => {
	const backToTemplates = useBackToTemplates();
	const current = getStepIndex(stage);

	return (
		<aside className="hidden md:flex w-[300px] min-w-[300px] xl:w-[350px] xl:min-w-[350px] flex-col bg-[#FAFBFC] border-0 border-r border-solid border-[#E9E9E9]">
			<div className="px-6 pt-6">
				<div className="flex justify-between items-center border-0 border-b border-solid border-[#E3E3E3] pb-6">
					<img src={logo} alt="Starter Templates and Sites Pack By ThemeGrill" width={50} />
					<Tooltip>
						<TooltipTrigger asChild>
							<button
								type="button"
								onClick={() => (window.location.href = '/wp-admin')}
								aria-label={__('Exit Import Process', 'themegrill-demo-importer')}
								className="bg-transparent border-0 p-0 cursor-pointer flex"
							>
								<X size={20} color="#909090" strokeWidth={2} />
							</button>
						</TooltipTrigger>
						<TooltipContent side="bottom" sideOffset={-4}>
							{__('Exit Import Process', 'themegrill-demo-importer')}
						</TooltipContent>
					</Tooltip>
				</div>
			</div>

			<div className="flex flex-col gap-8 px-6 pt-6 pb-10 overflow-y-auto tg-scrollbar">
				<button
					type="button"
					onClick={backToTemplates}
					className="self-start flex items-center gap-2 bg-transparent border-0 p-0 cursor-pointer text-[14px] text-[#6B6B6B] hover:text-[#1F1F1F]"
				>
					<ArrowLeft size={16} />
					{__('Back to Starter Templates', 'themegrill-demo-importer')}
				</button>

				<div>
					<div className="flex items-center gap-2 mb-2">
						<Sparkles size={20} color="#2563EB" />
						<h2 className="text-[20px] leading-7 text-[#1F1F1F] m-0">
							{__('Build with AI', 'themegrill-demo-importer')}
						</h2>
					</div>
					<p className="text-[14px] leading-[21px] text-[#6B6B6B] m-0">
						{__(
							'We start from one of our tested designs and make it yours: your brand colors, your words and images that fit.',
							'themegrill-demo-importer',
						)}
					</p>
				</div>

				<ol className="list-none m-0 p-0 flex flex-col gap-1" aria-label={__('Progress', 'themegrill-demo-importer')}>
					{FLOW_STEPS.map((step, index) => {
						const done = index < current;
						const active = index === current;
						return (
							<li
								key={step.label}
								aria-current={active ? 'step' : undefined}
								className={cn(
									'flex items-center gap-3 px-3 py-3 rounded-md m-0 text-[15px]',
									active ? 'bg-white text-[#1F1F1F] font-medium shadow-sm' : 'text-[#6B6B6B]',
								)}
							>
								<span
									className={cn(
										'flex items-center justify-center w-7 h-7 rounded-full text-[13px] border-2 border-solid shrink-0',
										done && 'bg-[#2563EB] border-[#2563EB] text-white',
										active && 'border-[#2563EB] text-[#2563EB]',
										!done && !active && 'border-[#DADDE2] text-[#909090]',
									)}
								>
									{done ? <Check size={14} strokeWidth={3} /> : index + 1}
								</span>
								{step.label}
							</li>
						);
					})}
				</ol>
			</div>
		</aside>
	);
};

export default AiSidebar;
