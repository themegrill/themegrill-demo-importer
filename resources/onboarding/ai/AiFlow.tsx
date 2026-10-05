import { __, sprintf } from '@wordpress/i18n';
import { ArrowLeft, Sparkles } from 'lucide-react';
import { useEffect } from 'react';
import AiSidebar, { FLOW_STEPS, getStepIndex, useBackToTemplates } from './components/AiSidebar';
import DescribeScreen from './screens/DescribeScreen';
import GeneratingScreen from './screens/GeneratingScreen';
import ImportScreen from './screens/ImportScreen';
import PreviewScreen from './screens/PreviewScreen';
import { AiFlowProvider, AiStage, useAiFlow } from './store/AiFlowContext';

// Confirm, importing and success share one component so the import's state
// survives the stage changes that drive the step indicator.
const STAGE_SCREENS: Record<AiStage, () => JSX.Element | null> = {
	describe: DescribeScreen,
	generating: GeneratingScreen,
	preview: PreviewScreen,
	confirm: ImportScreen,
	importing: ImportScreen,
	success: ImportScreen,
};

// Compact header for narrow screens, where the sidebar is hidden.
const MobileHeader = () => {
	const { state } = useAiFlow();
	const backToTemplates = useBackToTemplates();
	const index = getStepIndex(state.stage);

	return (
		<div className="md:hidden flex items-center justify-between gap-3 px-4 py-3 bg-[#FAFBFC] border-0 border-b border-solid border-[#E9E9E9]">
			<button
				type="button"
				onClick={backToTemplates}
				aria-label={__('Back to Starter Templates', 'themegrill-demo-importer')}
				className="flex bg-transparent border-0 p-1 cursor-pointer"
			>
				<ArrowLeft size={20} color="#6B6B6B" />
			</button>
			<span className="flex items-center gap-2 text-[15px] font-medium text-[#1F1F1F]">
				<Sparkles size={16} color="#2563EB" />
				{__('Build with AI', 'themegrill-demo-importer')}
			</span>
			<span className="text-[13px] text-[#6B6B6B]">
				{sprintf(
					/* translators: 1: current step, 2: total steps. */
					__('%1$d/%2$d', 'themegrill-demo-importer'),
					index + 1,
					FLOW_STEPS.length,
				)}
			</span>
		</div>
	);
};

const AiFlowLayout = () => {
	const { state } = useAiFlow();
	const Screen = STAGE_SCREENS[state.stage];

	return (
		<div className="flex h-screen content-container">
			<AiSidebar stage={state.stage} />
			<main className="flex-1 flex flex-col min-w-0 bg-white">
				<MobileHeader />
				<div className="flex-1 overflow-y-auto tg-scrollbar px-4 py-8 sm:px-10 lg:px-14 lg:py-14">
					{Screen ? <Screen /> : null}
				</div>
			</main>
		</div>
	);
};

const AiFlow = () => {
	useEffect(() => {
		document.body.classList.add('tg-full-overlay-active');
		document.documentElement.classList.remove('wp-toolbar');
	}, []);

	return (
		<AiFlowProvider>
			<AiFlowLayout />
		</AiFlowProvider>
	);
};

export default AiFlow;
