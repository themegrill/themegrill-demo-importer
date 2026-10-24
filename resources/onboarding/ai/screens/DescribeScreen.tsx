import { __, sprintf } from '@wordpress/i18n';
import { AlertCircle, ArrowLeft, Check, Sparkles } from 'lucide-react';
import React, { useId, useMemo, useRef, useState } from 'react';
import { Button } from '../../components/ui/Button';
import { cn } from '../../lib/utils';
import { useBackToTemplates } from '../components/AiSidebar';
import { fieldClass, invalidClass } from '../components/fields';
import { BRAND_NAME_MAX, DESCRIPTION_MAX, PAGES } from '../constants';
import { describeKey, useAiFlow } from '../store/AiFlowContext';
import { DescribeErrors, validateDescribe } from '../validation';


const Label = ({ htmlFor, children, optional }: { htmlFor?: string; children: React.ReactNode; optional?: boolean }) => (
	<label htmlFor={htmlFor} className="block text-[14px] font-medium text-[#1F1F1F] mb-2">
		{children}
		{optional ? (
			<span className="font-normal text-[#909090]"> {__('(optional)', 'themegrill-demo-importer')}</span>
		) : (
			<span className="text-[#DC2626]" aria-hidden="true"> *</span>
		)}
	</label>
);

const FieldError = ({ id, message }: { id: string; message?: string }) =>
	message ? (
		<p id={id} className="flex items-center gap-1 mt-2 mb-0 text-[13px] text-[#DC2626]">
			<AlertCircle size={14} aria-hidden="true" />
			{message}
		</p>
	) : null;

const DescribeScreen = () => {
	const { state, dispatch } = useAiFlow();
	const { form } = state;
	const backToTemplates = useBackToTemplates();
	const uid = useId();
	const ids = {
		brandName: `${uid}-brand`,
		description: `${uid}-description`,
	};

	const [touched, setTouched] = useState<Partial<Record<keyof DescribeErrors, boolean>>>({});
	const [submitted, setSubmitted] = useState(false);
	const brandRef = useRef<HTMLInputElement>(null);
	const descriptionRef = useRef<HTMLTextAreaElement>(null);

	const errors = useMemo(() => validateDescribe(form), [form]);
	const visibleError = (field: keyof DescribeErrors) =>
		submitted || touched[field] ? errors[field] : undefined;
	const touch = (field: keyof DescribeErrors) => setTouched((t) => ({ ...t, [field]: true }));

	const handleSubmit = (event: React.FormEvent) => {
		event.preventDefault();
		setSubmitted(true);

		if (errors.brandName) {
			brandRef.current?.focus();
			return;
		}
		if (errors.description) {
			descriptionRef.current?.focus();
			return;
		}

		const brandName = form.brandName.trim();
		const description = form.description.trim();
		// Designs are ranked for this description; any change ranks them again.
		if (describeKey(form) !== state.designsKey) {
			dispatch({ type: 'SET_DESIGNS', designs: null, key: '' });
		}
		dispatch({ type: 'UPDATE_FORM', patch: { brandName, description, demoSlug: undefined, briefId: undefined } });
		dispatch({ type: 'SET_STAGE', stage: 'design' });
	};

	const descriptionLength = form.description.trim().length;
	const brandError = visibleError('brandName');
	const descriptionError = visibleError('description');

	return (
		<form onSubmit={handleSubmit} noValidate className="max-w-[720px] mx-auto">
			<p className="text-[13px] font-medium uppercase tracking-wide text-[#2563EB] m-0 mb-2">
				{sprintf(
					/* translators: 1: current step, 2: total steps. */
					__('Step %1$d of %2$d', 'themegrill-demo-importer'),
					1,
					5,
				)}
			</p>
			<h1 className="text-[28px] leading-9 font-semibold text-[#1F1F1F] m-0 p-0">
				{__('Describe your site', 'themegrill-demo-importer')}
			</h1>
			<p className="text-[15px] leading-6 text-[#6B6B6B] mt-2 mb-8">
				{__(
					'Just two things. We work out the rest: the right design, your copy, images, colors and fonts.',
					'themegrill-demo-importer',
				)}
			</p>

			<div className="flex flex-col gap-7">
				<div>
					<Label htmlFor={ids.brandName}>{__('Brand / site name', 'themegrill-demo-importer')}</Label>
					<input
						ref={brandRef}
						id={ids.brandName}
						type="text"
						autoComplete="organization"
						maxLength={BRAND_NAME_MAX + 20}
						value={form.brandName}
						placeholder={__('e.g. Sweet Crumbs', 'themegrill-demo-importer')}
						onChange={(e) => dispatch({ type: 'UPDATE_FORM', patch: { brandName: e.target.value } })}
						onBlur={() => touch('brandName')}
						aria-required="true"
						aria-invalid={!!brandError}
						aria-describedby={brandError ? `${ids.brandName}-error` : undefined}
						className={cn(fieldClass, '!h-12', brandError && invalidClass)}
					/>
					<FieldError id={`${ids.brandName}-error`} message={brandError} />
				</div>

				<div>
					<Label htmlFor={ids.description}>
						{__('What does your business do?', 'themegrill-demo-importer')}
					</Label>
					<textarea
						ref={descriptionRef}
						id={ids.description}
						rows={3}
						value={form.description}
						placeholder={__(
							'e.g. Bakery, or: A family bakery in Kathmandu selling fresh bread and custom cakes',
							'themegrill-demo-importer',
						)}
						onChange={(e) => dispatch({ type: 'UPDATE_FORM', patch: { description: e.target.value } })}
						onBlur={() => touch('description')}
						aria-required="true"
						aria-invalid={!!descriptionError}
						aria-describedby={`${ids.description}-hint${descriptionError ? ` ${ids.description}-error` : ''}`}
						className={cn(fieldClass, '!py-3 !leading-6 resize-y min-h-[96px]', descriptionError && invalidClass)}
					/>
					<div className="flex justify-between gap-4 mt-2">
						<p id={`${ids.description}-hint`} className="m-0 text-[13px] text-[#6B6B6B]">
							{__(
								'A niche like “bakery” is enough. A sentence about what you offer and where gives more personal copy.',
								'themegrill-demo-importer',
							)}
						</p>
						<span
							className={cn(
								'shrink-0 text-[13px] tabular-nums',
								descriptionLength > DESCRIPTION_MAX ? 'text-[#DC2626]' : 'text-[#909090]',
							)}
						>
							{descriptionLength}/{DESCRIPTION_MAX}
						</span>
					</div>
					<FieldError id={`${ids.description}-error`} message={descriptionError} />
				</div>

				<fieldset className="m-0 p-0 border-0">
					<legend className="block p-0 mb-2 text-[14px] font-medium text-[#1F1F1F]">
						{__('Pages', 'themegrill-demo-importer')}
					</legend>
					<ul className="flex flex-wrap gap-2 m-0 p-0 list-none">
						{PAGES.map((page) =>
							page.value === 'home' ? (
								<li
									key={page.value}
									className="m-0 flex items-center gap-2 h-10 px-4 rounded-full border-2 border-solid border-[#2563EB] bg-[#F5F8FF] text-[14px] text-[#1E3A8A]"
								>
									<Check size={16} aria-hidden="true" />
									{page.label}
								</li>
							) : (
								<li
									key={page.value}
									aria-disabled="true"
									className="m-0 flex items-center gap-2 h-10 px-4 rounded-full border-2 border-solid border-[#EBEDEF] bg-[#F9FAFB] text-[14px] text-[#A3A3A3] cursor-not-allowed"
								>
									{page.label}
									<span className="text-[11px] font-medium uppercase tracking-wide text-[#909090]">
										{__('Coming soon', 'themegrill-demo-importer')}
									</span>
								</li>
							),
						)}
					</ul>
					<p className="m-0 mt-2 text-[13px] text-[#6B6B6B]">
						{__('We build your home page for now. More pages are on the way.', 'themegrill-demo-importer')}
					</p>
				</fieldset>
			</div>

			<div className="flex flex-col-reverse sm:flex-row sm:justify-between gap-3 mt-10 pt-6 border-0 border-t border-solid border-[#E9E9E9]">
				<Button
					type="button"
					variant="outline"
					onClick={backToTemplates}
					className="h-12 px-6 gap-2 cursor-pointer border-2 border-solid border-[#EBEDEF] bg-white text-[#383838] text-[15px] hover:bg-[#FAFBFC]"
				>
					<ArrowLeft size={16} />
					{__('Back', 'themegrill-demo-importer')}
				</Button>
				<Button
					type="submit"
					className="h-12 px-8 gap-2 cursor-pointer border-0 bg-[#2563EB] text-white text-[15px] hover:bg-[#134FD2]"
				>
					<Sparkles size={18} />
					{__('Choose a design', 'themegrill-demo-importer')}
				</Button>
			</div>
		</form>
	);
};

export default DescribeScreen;
