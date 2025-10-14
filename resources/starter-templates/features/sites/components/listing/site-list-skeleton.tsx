import { SidebarProvider } from "@/starter-templates/components/ui/sidebar";
import { memo } from "react";
import {
	SidebarContent,
	SidebarGroup,
	SidebarHeader,
	Sidebar,
} from "@/starter-templates/components/ui/sidebar";
import {
	Tooltip,
	TooltipArrow,
	TooltipContent,
	TooltipTrigger,
} from "@/starter-templates/components/ui/tooltip";
import { X } from "lucide-react";
import { __ } from "@wordpress/i18n";
import { Separator } from "@/starter-templates/components/ui/separator";
import { ScrollArea } from "@/starter-templates/components/ui/scroll-area";
import logo from "@/starter-templates/assets/images/logo.png";
import { Skeleton } from "@/starter-templates/components/ui/skeleton";
import { random } from "lodash";

export const SiteListSkeleton = memo(() => {
	return (
		<SidebarProvider
			style={
				{
					"--sidebar-width": "20rem",
					"--sidebar-width-mobile": "20rem",
				} as React.CSSProperties
			}
		>
			<Sidebar className="bg-[#FAFBFC]">
				<SidebarHeader className="p-6">
					<div className="flex items-center justify-between">
						<img width={50} height={50} src={logo} />
						<Tooltip>
							<TooltipTrigger>
								<X />
							</TooltipTrigger>
							<TooltipContent
								align="center"
								side="bottom"
								className="text-black/80 border border-border rounded-[2px]"
							>
								{__(
									"Exit import process",
									"themegrill-demo-importer"
								)}
								<TooltipArrow className="fill-white z-50 bg-white size-2.5 translate-y-[calc(-50%_-_1px)] rotate-45 rounded-[2px] border-0 border-r border-b border-solid border-border" />
							</TooltipContent>
						</Tooltip>
					</div>
				</SidebarHeader>
				<div className="px-6">
					<Separator className="bg-border" />
				</div>
				<SidebarContent className="overflow-hidden">
					<ScrollArea>
						<SidebarGroup className="p-6">
							<div className="flex mb-5 gap-[10px] font-semibold text-[#1f1f1f] items-center">
								<Skeleton className="h-[24px] w-4/5" />
							</div>
							<Skeleton className="w-full h-[68px]" />
						</SidebarGroup>
						<SidebarGroup className="p-6 pt-0">
							<Skeleton className="h-[24px] w-1/2 mb-5" />
							<div className="flex space-y-4 flex-col">
								{Array.from({ length: 3 }).map((_, i) => (
									<Skeleton
										key={i}
										className="h-[62px] w-full"
									/>
								))}
							</div>
						</SidebarGroup>
						<SidebarGroup className="p-6 pt-0">
							<Skeleton className="h-[24px] w-1/2 mb-5" />
							<div className="flex flex-wrap gap-[14px]">
								{Array.from({ length: 11 }).map((_, i) => (
									<Skeleton
										key={i}
										className="h-[46px]"
										style={{
											width: `${random(70, 140)}px`,
										}}
									/>
								))}
							</div>
						</SidebarGroup>
					</ScrollArea>
				</SidebarContent>
			</Sidebar>
			<div />
		</SidebarProvider>
	);
});
