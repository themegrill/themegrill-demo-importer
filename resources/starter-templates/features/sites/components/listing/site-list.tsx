import { ScrollArea } from "@/starter-templates/components/ui/scroll-area";
import { SiteItem } from "@/starter-templates/features/sites/components/listing/site-item";
import { Route } from "@/starter-templates/routes";
import { useSitesStore } from "@/starter-templates/stores/sites.store";
import { memo, useMemo, useRef } from "react";
import { useVirtualizer } from "@tanstack/react-virtual";
import useResizeObserver from "use-resize-observer";
import { SidebarTrigger } from "@/starter-templates/components/ui/sidebar";

export const SiteList = memo(() => {
	const { builders, actions } = useSitesStore.getState();
	const search = Route.useSearch();
	const parentRef = useRef<HTMLDivElement>(null);
	const scrollRef = useRef<HTMLDivElement>(null);

	const { width: containerWidth = 0 } = useResizeObserver({ ref: parentRef });

	const sites = useMemo(() => {
		let builder = search?.builder ?? builders[0] ?? "elementor";
		if (builders && !builders.includes(builder)) {
			builder = builders.at(0)!;
		}
		return actions.getFilteredSites({
			query: search.q,
			builder: builder,
			categories: search?.categories?.split(",") ?? [],
		});
	}, [search, builders]);

	const columns = Math.max(1, Math.floor((containerWidth + 40) / 344));

	const rows = Math.ceil(sites.length / columns);

	const rowVirtualizer = useVirtualizer({
		count: rows,
		getScrollElement: () => scrollRef.current,
		estimateSize: () => 400,
		overscan: 2,
		gap: 40,
		measureElement: (element) => element.getBoundingClientRect().height,
	});

	return (
		<ScrollArea className="w-full h-svh" ref={scrollRef}>
			<div className="p-5 sm:p-14 2xl:p-[88px]">
				<div
					ref={parentRef}
					style={{
						height: `${rowVirtualizer.getTotalSize()}px`,
						position: "relative",
					}}
				>
					{rowVirtualizer.getVirtualItems().map((virtualRow) => {
						const startIndex = virtualRow.index * columns;
						const endIndex = Math.min(
							startIndex + columns,
							sites.length
						);
						const rowSites = sites.slice(startIndex, endIndex);
						return (
							<div
								key={virtualRow.key}
								data-index={virtualRow.index}
								ref={rowVirtualizer.measureElement}
								style={{
									position: "absolute",
									top: 0,
									left: 0,
									width: "100%",
									transform: `translateY(${virtualRow.start}px)`,
								}}
							>
								<div
									className="grid gap-10"
									style={{
										gridTemplateColumns: `repeat(${columns}, 1fr)`,
									}}
								>
									{rowSites.map((site) => (
										<SiteItem key={site.slug} site={site} />
									))}
								</div>
							</div>
						);
					})}
				</div>
			</div>
		</ScrollArea>
	);
});

SiteList.displayName = "SiteList";
