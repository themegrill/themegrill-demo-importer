import { __ } from '@wordpress/i18n';
import { useId } from 'react';
import { cn } from '../../../lib/utils';
import { FONT_CHOICES } from '../../constants';
import { useAiFlow } from '../../store/AiFlowContext';
import { BrandKit, BrandPalette } from '../../types';
import { fieldClass } from '../fields';

const SWATCHES: { key: keyof BrandPalette; label: string }[] = [
	{ key: 'primary', label: __('Primary', 'themegrill-demo-importer') },
	{ key: 'secondary', label: __('Secondary', 'themegrill-demo-importer') },
	{ key: 'accent', label: __('Accent', 'themegrill-demo-importer') },
	{ key: 'text', label: __('Text', 'themegrill-demo-importer') },
	{ key: 'background', label: __('Background', 'themegrill-demo-importer') },
];

const labelClass = 'block text-[13px] font-medium text-[#383838] mb-1';

const BrandPanel = ({ brand }: { brand: BrandKit }) => {
	const { dispatch } = useAiFlow();
	const uid = useId();
	const fonts = (current: string) => (FONT_CHOICES.includes(current) ? FONT_CHOICES : [current, ...FONT_CHOICES]);

	return (
		<div className="flex flex-col gap-4">
			<div>
				<label htmlFor={`${uid}-title`} className={labelClass}>
					{__('Site title', 'themegrill-demo-importer')}
				</label>
				<input
					id={`${uid}-title`}
					type="text"
					value={brand.siteTitle}
					onChange={(e) => dispatch({ type: 'UPDATE_BRAND', patch: { siteTitle: e.target.value } })}
					className={cn(fieldClass, '!h-10 !text-[14px] !px-3')}
				/>
			</div>
			<div>
				<label htmlFor={`${uid}-tagline`} className={labelClass}>
					{__('Tagline', 'themegrill-demo-importer')}
				</label>
				<input
					id={`${uid}-tagline`}
					type="text"
					value={brand.tagline}
					onChange={(e) => dispatch({ type: 'UPDATE_BRAND', patch: { tagline: e.target.value } })}
					className={cn(fieldClass, '!h-10 !text-[14px] !px-3')}
				/>
			</div>

			<fieldset className="m-0 p-0 border-0 min-w-0">
				<legend className={cn(labelClass, 'p-0')}>{__('Colors', 'themegrill-demo-importer')}</legend>
				<div className="grid grid-cols-5 gap-2">
					{SWATCHES.map(({ key, label }) => (
						<label key={key} className="flex flex-col items-center gap-1 cursor-pointer">
							<span
								className="relative block w-10 h-10 rounded-full border border-solid border-black/10 shadow-sm overflow-hidden focus-within:ring-2 focus-within:ring-[#5182EF] focus-within:ring-offset-2"
								style={{ background: brand.palette[key] }}
							>
								<input
									type="color"
									value={brand.palette[key]}
									onChange={(e) => dispatch({ type: 'UPDATE_BRAND', patch: { palette: { [key]: e.target.value } } })}
									className="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
								/>
							</span>
							<span className="text-[11px] text-[#6B6B6B]">{label}</span>
						</label>
					))}
				</div>
			</fieldset>

			<div className="grid grid-cols-2 gap-3">
				{(['heading', 'body'] as const).map((role) => (
					<div key={role}>
						<label htmlFor={`${uid}-${role}`} className={labelClass}>
							{role === 'heading'
								? __('Heading font', 'themegrill-demo-importer')
								: __('Body font', 'themegrill-demo-importer')}
						</label>
						<select
							id={`${uid}-${role}`}
							value={brand.fonts[role]}
							onChange={(e) => dispatch({ type: 'UPDATE_BRAND', patch: { fonts: { [role]: e.target.value } } })}
							className={cn(fieldClass, '!h-10 !text-[14px] !px-2')}
							style={{ fontFamily: `'${brand.fonts[role]}', sans-serif` }}
						>
							{fonts(brand.fonts[role]).map((font) => (
								<option key={font} value={font}>
									{font}
								</option>
							))}
						</select>
					</div>
				))}
			</div>
		</div>
	);
};

export default BrandPanel;
