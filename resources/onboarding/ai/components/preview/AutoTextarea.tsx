import React, { useLayoutEffect, useRef } from 'react';
import { cn } from '../../../lib/utils';

type Props = Omit<React.TextareaHTMLAttributes<HTMLTextAreaElement>, 'value' | 'onChange'> & {
	value: string;
	onChange: (value: string) => void;
};

// Grows with its content so copy edits in place, like the page it previews.
const AutoTextarea = ({ value, onChange, className, ...props }: Props) => {
	const ref = useRef<HTMLTextAreaElement>(null);

	useLayoutEffect(() => {
		const el = ref.current;
		if (!el) return;
		el.style.height = 'auto';
		el.style.height = `${el.scrollHeight}px`;
	}, [value]);

	return (
		<textarea
			ref={ref}
			rows={1}
			value={value}
			onChange={(e) => onChange(e.target.value)}
			className={cn(
				'!block !w-full !min-h-0 !max-w-none !resize-none !overflow-hidden !bg-transparent !border !border-dashed !border-transparent !rounded !p-1 !-m-1 !shadow-none',
				'hover:!border-[#CBD5E1] focus:!border-[#5182EF] focus:!bg-white/70 focus:!outline-none',
				className,
			)}
			{...props}
		/>
	);
};

export default AutoTextarea;
