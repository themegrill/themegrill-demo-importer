import * as TooltipPrimitive from '@radix-ui/react-tooltip';
import * as React from 'react';

import { cn } from '../lib/utils';

function TooltipProvider({
	delayDuration = 0,
	...props
}: React.ComponentProps<typeof TooltipPrimitive.Provider>) {
	return (
		<TooltipPrimitive.Provider
			data-slot="tooltip-provider"
			delayDuration={delayDuration}
			{...props}
		/>
	);
}

function Tooltip({ ...props }: React.ComponentProps<typeof TooltipPrimitive.Root>) {
	return (
		<TooltipProvider>
			<TooltipPrimitive.Root data-slot="tooltip" {...props} />
		</TooltipProvider>
	);
}

function TooltipTrigger({ ...props }: React.ComponentProps<typeof TooltipPrimitive.Trigger>) {
	return <TooltipPrimitive.Trigger data-slot="tooltip-trigger" {...props} />;
}

function TooltipContent({
	className,
	sideOffset = 0,
	children,
	...props
}: React.ComponentProps<typeof TooltipPrimitive.Content>) {
	return (
		<TooltipPrimitive.Portal>
			<TooltipPrimitive.Content
				data-slot="tooltip-content"
				sideOffset={sideOffset}
				className={cn(
					'bg-white text-[#646464] animate-in fade-in-0 zoom-in-95 data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=closed]:zoom-out-95 data-[side=bottom]:slide-in-from-top-2 data-[side=left]:slide-in-from-right-2 data-[side=right]:slide-in-from-left-2 data-[side=top]:slide-in-from-bottom-2 z-[50001] w-fit origin-(--radix-tooltip-content-transform-origin) rounded-sm px-[12px] py-2 text-balance border border-solid border-[#DDDFE4]',
					className,
				)}
				style={{ boxShadow: '0 5px 35px 0 rgba(0, 0, 0, 0.10)' }}
				{...props}
			>
				{children}
				<TooltipPrimitive.Arrow className="bg-white fill-white z-50 size-2.5 translate-y-[calc(-50%_-_1px)] rotate-45 rounded-[2px] border-0 border-r border-b border-solid border-[#DDDFE4]" />
			</TooltipPrimitive.Content>
		</TooltipPrimitive.Portal>
	);
}

export { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger };
