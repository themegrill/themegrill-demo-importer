import { __, sprintf } from '@wordpress/i18n';
import { AlertCircle, ArrowLeft, Check, Clock, Loader2, RotateCcw } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { Button } from '../../components/ui/Button';
import { Progress } from '../../components/ui/Progress';
import { cn } from '../../lib/utils';
import { getAiClient } from '../api/client';
import { AiApiError, toAiApiError } from '../api/errors';
import { getAiConfig } from '../config';
import { GENERATION_STEPS } from '../constants';
import { useAiFlow } from '../store/AiFlowContext';
import { GenerationProgress } from '../types';

// Until the first real progress event arrives (or if the backend doesn't
// stream), steps advance on a timer and hold on the last one.
const ESTIMATED_STEP_MS = 1200;

const formatResetTime = (retryAfter?: number) => {
	if (!retryAfter) {
		return '';
	}
	const resetAt = new Date(Date.now() + retryAfter * 1000);
	return sprintf(
		/* translators: %s: time when the limit resets. */
		__('You can generate again after %s.', 'themegrill-demo-importer'),
		resetAt.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' }),
	);
};

const ErrorPanel = ({ error, onRetry, onEdit }: { error: AiApiError; onRetry: () => void; onEdit: () => void }) => {
	const rateLimited = error.code === 'RATE_LIMITED';
	const canRetry = !rateLimited && error.code !== 'UNAUTHORIZED' && error.code !== 'INVALID_INPUT';
	const title = rateLimited
		? __('You’ve reached today’s AI generation limit', 'themegrill-demo-importer')
		: error.code === 'INVALID_INPUT'
			? __('We couldn’t use that description', 'themegrill-demo-importer')
			: error.code === 'UNAUTHORIZED'
				? __('This site can’t use AI generation', 'themegrill-demo-importer')
				: __('Your site couldn’t be generated', 'themegrill-demo-importer');

	return (
		<div role="alert" className="p-6 rounded-lg border border-solid border-[#FECACA] bg-[#FEF2F2]">
			<div className="flex items-start gap-3">
				{rateLimited ? (
					<Clock size={22} className="shrink-0 text-[#DC2626] mt-[2px]" aria-hidden="true" />
				) : (
					<AlertCircle size={22} className="shrink-0 text-[#DC2626] mt-[2px]" aria-hidden="true" />
				)}
				<div>
					<h2 className="text-[17px] leading-6 font-semibold text-[#7F1D1D] m-0">{title}</h2>
					<p className="text-[14px] leading-[21px] text-[#991B1B] mt-1 mb-0">
						{error.message}
						{rateLimited && error.retryAfter ? ` ${formatResetTime(error.retryAfter)}` : ''}
					</p>
				</div>
			</div>
			<div className="flex flex-wrap gap-3 mt-5 pl-[34px]">
				{canRetry && (
					<Button
						type="button"
						onClick={onRetry}
						className="h-10 px-5 gap-2 cursor-pointer border-0 bg-[#2563EB] text-white hover:bg-[#134FD2]"
					>
						<RotateCcw size={16} />
						{__('Try again', 'themegrill-demo-importer')}
					</Button>
				)}
				<Button
					type="button"
					variant="outline"
					onClick={onEdit}
					className="h-10 px-5 gap-2 cursor-pointer border-2 border-solid border-[#EBEDEF] bg-white text-[#383838]"
				>
					<ArrowLeft size={16} />
					{__('Edit description', 'themegrill-demo-importer')}
				</Button>
			</div>
		</div>
	);
};

const GeneratingScreen = () => {
	const { state, dispatch } = useAiFlow();
	const { form, error } = state;
	const [progress, setProgress] = useState<GenerationProgress>({ step: 'understanding_brand', progress: 0 });
	const [attempt, setAttempt] = useState(0);
	const controllerRef = useRef<AbortController | null>(null);

	useEffect(() => {
		const controller = new AbortController();
		controllerRef.current = controller;
		dispatch({ type: 'SET_ERROR', error: null });
		setProgress({ step: 'understanding_brand', progress: 0 });

		// Only the live client needs estimated progress; the mock reports real steps.
		let timer: ReturnType<typeof setInterval> | undefined;
		const onProgress = (next: GenerationProgress) => {
			clearInterval(timer);
			setProgress(next);
		};
		if (!getAiConfig().useMock) {
			let index = 0;
			timer = setInterval(() => {
				index = Math.min(index + 1, GENERATION_STEPS.length - 1);
				const step = GENERATION_STEPS[index]?.value ?? 'assembling';
				setProgress({ step, progress: Math.min(0.9, index / GENERATION_STEPS.length) });
			}, ESTIMATED_STEP_MS);
		}

		getAiClient()
			.generate(form, { signal: controller.signal, onProgress })
			.then((pkg) => {
				if (controller.signal.aborted) return;
				setProgress({ step: 'assembling', progress: 1 });
				dispatch({ type: 'SET_PACKAGE', pkg });
				dispatch({ type: 'SET_STAGE', stage: 'preview' });
			})
			.catch((err) => {
				const apiError = toAiApiError(err);
				if (controller.signal.aborted || apiError.code === 'CANCELLED') return;
				dispatch({ type: 'SET_ERROR', error: apiError });
			})
			.finally(() => clearInterval(timer));

		return () => {
			clearInterval(timer);
			controller.abort();
		};
		// Re-run only on retry; the form can't change while this screen is open.
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [attempt]);

	const backToDescribe = () => {
		controllerRef.current?.abort();
		dispatch({ type: 'SET_ERROR', error: null });
		dispatch({ type: 'SET_STAGE', stage: 'describe' });
	};

	const currentIndex = GENERATION_STEPS.findIndex((s) => s.value === progress.step);
	const done = progress.progress >= 1;

	return (
		<div className="max-w-[640px] mx-auto">
			<p className="text-[13px] font-medium uppercase tracking-wide text-[#2563EB] m-0 mb-2">
				{sprintf(
					/* translators: 1: current step, 2: total steps. */
					__('Step %1$d of %2$d', 'themegrill-demo-importer'),
					2,
					4,
				)}
			</p>
			<h1 className="text-[28px] leading-9 font-semibold text-[#1F1F1F] m-0 p-0">
				{error
					? __('Something went wrong', 'themegrill-demo-importer')
					: sprintf(
							/* translators: %s: brand name. */
							__('Building %s', 'themegrill-demo-importer'),
							form.brandName,
						)}
			</h1>
			<p className="text-[15px] leading-6 text-[#6B6B6B] mt-2 mb-8">
				{error
					? __('Nothing has been changed on your site.', 'themegrill-demo-importer')
					: __('This usually takes under a minute. Nothing is changed on your site until you import.', 'themegrill-demo-importer')}
			</p>

			{error ? (
				<ErrorPanel error={error} onRetry={() => setAttempt((a) => a + 1)} onEdit={backToDescribe} />
			) : (
				<>
					<Progress
						value={progress.progress * 100}
						aria-label={__('Generation progress', 'themegrill-demo-importer')}
						className="h-2 bg-[#E8EEFD]"
						indicatorStyle={{ transform: `translateX(-${100 - progress.progress * 100}%)` }}
					/>
					<ol className="list-none m-0 mt-8 p-0 flex flex-col gap-4" aria-live="polite">
						{GENERATION_STEPS.map((step, index) => {
							const complete = done || index < currentIndex;
							const active = !done && index === currentIndex;
							return (
								<li key={step.value} className="flex items-center gap-3 m-0 text-[15px]">
									<span
										className={cn(
											'flex items-center justify-center w-7 h-7 rounded-full shrink-0',
											complete && 'bg-[#2563EB] text-white',
											active && 'bg-[#E8EEFD] text-[#2563EB]',
											!complete && !active && 'bg-[#F4F4F5] text-[#B4B4B4]',
										)}
										aria-hidden="true"
									>
										{complete ? (
											<Check size={14} strokeWidth={3} />
										) : active ? (
											<Loader2 size={16} className="animate-spin" />
										) : (
											<span className="w-[6px] h-[6px] rounded-full bg-current" />
										)}
									</span>
									<span className={cn(complete || active ? 'text-[#1F1F1F]' : 'text-[#909090]', active && 'font-medium')}>
										{step.label}
										<span className="sr-only">
											{complete
												? ` ${__('(done)', 'themegrill-demo-importer')}`
												: active
													? ` ${__('(in progress)', 'themegrill-demo-importer')}`
													: ''}
										</span>
									</span>
								</li>
							);
						})}
					</ol>
					<div className="mt-10 pt-6 border-0 border-t border-solid border-[#E9E9E9]">
						<Button
							type="button"
							variant="outline"
							onClick={backToDescribe}
							className="h-11 px-6 cursor-pointer border-2 border-solid border-[#EBEDEF] bg-white text-[#383838] hover:bg-[#FAFBFC]"
						>
							{__('Cancel', 'themegrill-demo-importer')}
						</Button>
					</div>
				</>
			)}
		</div>
	);
};

export default GeneratingScreen;
