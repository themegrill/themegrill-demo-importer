import { Button } from "@/starter-templates/components/ui/button";
import { Input } from "@/starter-templates/components/ui/input";
import { Separator } from "@/starter-templates/components/ui/separator";
import {
	SidebarContent,
	SidebarGroup,
	SidebarHeader,
	Sidebar as SidebarPrimitive,
} from "@/starter-templates/components/ui/sidebar";
import {
	Tooltip,
	TooltipContent,
	TooltipTrigger,
} from "@/starter-templates/components/ui/tooltip";
import { BUILDERS } from "@/starter-templates/features/sites/constants/builder";
import { Route } from "@/starter-templates/routes";
import { useSitesStore } from "@/starter-templates/stores/sites.store";
import { TooltipArrow } from "@radix-ui/react-tooltip";
import { __ } from "@wordpress/i18n";
import { ArrowRight, Search, X } from "lucide-react";
import { memo, useCallback, useEffect, useMemo } from "react";
import logo from "@/starter-templates/assets/images/logo.png";
import { ScrollArea } from "@/starter-templates/components/ui/scroll-area";
import { debounce, startCase } from "lodash";

export const SiteSidebar = memo(() => {
	const { builders, categories } = useSitesStore.getState();

	const search = Route.useSearch();

	const navigate = Route.useNavigate();

	const debouncedNavigate = useMemo(
		() =>
			debounce((value: string) => {
				navigate({
					search: (prev) => ({
						...prev,
						page: undefined,
						q: value || undefined,
					}),
				});
			}, 300),
		[]
	);

	useEffect(() => {
		return () => {
			debouncedNavigate.cancel();
		};
	}, [debouncedNavigate]);

	const handleSearchChange = useCallback(
		(e: React.ChangeEvent<HTMLInputElement>) => {
			debouncedNavigate(e.target.value);
		},
		[debouncedNavigate]
	);

	const handleBuilderChange = useCallback((value: string) => {
		return () =>
			navigate({
				search: (prev) => ({
					...prev,
					page: undefined,
					builder: value,
				}),
			});
	}, []);

	const handleCategoryChange = useCallback((value: string) => {
		return () => {
			const normalizedValue = value.toLowerCase();

			navigate({
				search: (prev) => {
					const currentCategories = (prev.categories ?? "")
						.split(",")
						.filter(Boolean);
					let nextCategories: string[];

					if (normalizedValue === "all") {
						nextCategories = [];
					} else {
						const categoryIndex =
							currentCategories.indexOf(normalizedValue);
						if (categoryIndex > -1) {
							nextCategories = currentCategories.filter(
								(cat) => cat !== normalizedValue
							);
						} else {
							nextCategories = [
								...currentCategories,
								normalizedValue,
							];
						}
					}

					return {
						...prev,
						page: undefined,
						categories:
							nextCategories.length > 0
								? nextCategories.join(",")
								: undefined,
					};
				},
			});
		};
	}, []);

	return (
		<SidebarPrimitive className="bg-[#FAFBFC]">
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
							<span>
								{__(
									"Select a Template you Like",
									"themegrill-demo-importer"
								)}
							</span>
							<ArrowRight size={17} />
						</div>
						<div className="relative">
							<Input
								defaultValue={search.q}
								onChange={handleSearchChange}
								placeholder={__(
									"Search...",
									"themegrill-demo-importer"
								)}
								className="text-[#646464] py-5 pl-6 pr-14 border-[2px] focus-visible:ring-0 focus-visible:border-primary border-border rounded-md h-auto text-base md:text-base shadow-none"
							/>
							<Search className="absolute size-5 pointer-events-none right-6 text-[#909090] top-1/2 -translate-y-1/2" />
						</div>
					</SidebarGroup>
					<SidebarGroup className="p-6 pt-0">
						<label className="font-semibold text-[#1f1f1f] mb-5">
							{__("Choose Builder", "themegrill-demo-importer")}
						</label>
						<div className="flex space-y-4 flex-col">
							{Object.entries(BUILDERS)
								.filter(([k]) => builders.includes(k))
								.map(([k, v], i) => (
									<Button
										className="h-[62px] text-base bg-white gap-[10px] border-[2px] rounded-md hover:bg-white data-[state=on]:border-transparent data-[state=on]:ring-2 data-[state=on]:ring-ring data-[state=on]:bg-white text-[#383838] transition-colors py-0 px-6 justify-start"
										variant="outline"
										data-state={
											search?.builder === k ||
											(!search?.builder && 0 == i)
												? "on"
												: "off"
										}
										onClick={handleBuilderChange(k)}
										key={k}
									>
										<v.Icon height={24} width={24} />
										<span>{v.title}</span>
									</Button>
								))}
						</div>
					</SidebarGroup>
					<SidebarGroup className="p-6 pt-0">
						<label className="font-semibold text-[#1f1f1f] mb-5">
							{__("Category", "themegrill-demo-importer")}
						</label>
						<div className="flex flex-wrap gap-[14px]">
							{["All", ...categories].map((c) => (
								<Button
									className="h-[46px] text-base bg-white hover:bg-white border-[2px] rounded-md data-[state=on]:border-primary data-[state=on]:bg-primary data-[state=on]:text-white text-[#383838] transition-colors py-0 px-6"
									variant="outline"
									key={c}
									data-state={
										!search.categories && "All" === c
											? "on"
											: search.categories?.includes(
														c.toLowerCase()
												  )
												? "on"
												: "off"
									}
									onClick={handleCategoryChange(c)}
								>
									{startCase(c)}
								</Button>
							))}
						</div>
					</SidebarGroup>
				</ScrollArea>
			</SidebarContent>
		</SidebarPrimitive>
	);
});
