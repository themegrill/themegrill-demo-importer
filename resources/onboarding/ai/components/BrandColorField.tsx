// The brand's primary color: a swatch picker plus a hex box. The rest of the
// design's colors follow it (mapped by the backend).
import { __ } from '@wordpress/i18n';
import { Info } from 'lucide-react';
import { useEffect, useState } from 'react';
import { cn } from '../../lib/utils';
import { fieldClass, invalidClass } from './fields';

const HEX = /^#[0-9a-f]{6}$/i;

const luminance = (hex: string) => {
	const [r, g, b] = [1, 3, 5].map((i) => {
		const c = parseInt(hex.slice(i, i + 2), 16) / 255;
		return c <= 0.04045 ? c / 12.92 : ((c + 0.055) / 1.055) ** 2.4;
	});
	return 0.2126 * r! + 0.7152 * g! + 0.0722 * b!;
};

// Below 3:1 against white, buttons with white text get a darker shade of the color.
export const isLightColor = (hex: string) => HEX.test(hex) && 1.05 / (luminance(hex) + 0.05) < 3;

const BrandColorField = ({
	id,
	value,
	onChange,
	describedBy,
}: {
	id: string;
	value: string;
	onChange: (color: string) => void;
	describedBy?: string;
}) => {
	// The hex box can hold a half-typed value; only a full color is passed on.
	const [text, setText] = useState(value);
	useEffect(() => setText(value), [value]);
	const invalid = !HEX.test(text);

	return (
		<div>
			<div className="flex items-center gap-3">
				<input
					id={id}
					type="color"
					value={value}
					onChange={(e) => onChange(e.target.value.toLowerCase())}
					aria-describedby={describedBy}
					className="!w-12 !h-12 !p-1 !m-0 cursor-pointer rounded-md border-2 border-solid border-[#EBEDEF] bg-white"
				/>
				<input
					type="text"
					value={text}
					maxLength={7}
					spellCheck={false}
					aria-label={__('Hex color code', 'themegrill-demo-importer')}
					aria-invalid={invalid}
					onChange={(e) => {
						const next = e.target.value.trim();
						setText(next);
						if (HEX.test(next)) onChange(next.toLowerCase());
					}}
					onBlur={() => setText(value)}
					className={cn(
						fieldClass,
						'!w-[120px] !h-12 font-mono uppercase',
						invalid && invalidClass,
					)}
				/>
			</div>
			{isLightColor(value) && (
				<p className="flex items-start gap-2 mt-2 mb-0 text-[13px] text-[#92400E]">
					<Info size={14} className="shrink-0 mt-[3px]" aria-hidden="true" />
					{__(
						'This is a light color. Where white text sits on it, like buttons, we use a darker shade so the text stays readable.',
						'themegrill-demo-importer',
					)}
				</p>
			)}
		</div>
	);
};

export default BrandColorField;
