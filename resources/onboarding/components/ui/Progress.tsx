import * as ProgressPrimitive from '@radix-ui/react-progress';
import * as React from 'react';

import { cn } from '../../lib/utils';

// Define a type for the extended props
type ProgressProps = React.ComponentPropsWithoutRef<typeof ProgressPrimitive.Root> & {
	indicatorClassName?: string;
	progressContent?: React.ReactNode;
	indicatorStyle?: React.CSSProperties;
};

const Progress = React.forwardRef<React.ElementRef<typeof ProgressPrimitive.Root>, ProgressProps>(
	({ className, value, indicatorClassName, progressContent, indicatorStyle, ...props }, ref) => (
		<ProgressPrimitive.Root
			ref={ref}
			className={cn('relative h-2 w-full overflow-hidden rounded-full bg-white', className)}
			{...props}
		>
			<ProgressPrimitive.Indicator
				className={cn(
					'h-full w-full flex-1 bg-[#2563EB] transition-all rounded-full',
					indicatorClassName,
				)}
				style={{ ...indicatorStyle }}
				// style={{ transform: `translateX(-${100 - (value || 0)}%)` }}
			/>
			{progressContent}
		</ProgressPrimitive.Root>
	),
);
Progress.displayName = ProgressPrimitive.Root.displayName;

export { Progress };
