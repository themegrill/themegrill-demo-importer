import { cn } from "@/starter-templates/lib/utils";

function Skeleton({
	className,
	...props
}: React.HTMLAttributes<HTMLDivElement>) {
	return (
		<div
			className={cn("animate-pulse rounded-md bg-[#E5E5E5]", className)}
			{...props}
		/>
	);
}

export { Skeleton };
