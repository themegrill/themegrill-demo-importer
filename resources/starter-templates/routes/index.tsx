import {
	SidebarProvider,
	SidebarTrigger,
} from "@/starter-templates/components/ui/sidebar";
import { sitesQueryOptions } from "@/starter-templates/features/sites/api/sites.queries";
import { SiteSidebar } from "@/starter-templates/features/sites/components/listing/site-sidebar";
import { SiteList } from "@/starter-templates/features/sites/components/listing/site-list";
import { queryClient } from "@/starter-templates/lib/query-client";
import { useSitesStore } from "@/starter-templates/stores/sites.store";
import { useSuspenseQuery } from "@tanstack/react-query";
import { createFileRoute } from "@tanstack/react-router";
import React from "react";
import { SiteListSkeleton } from "@/starter-templates/features/sites/components/listing/site-list-skeleton";

export const Route = createFileRoute("/")({
	component: RouteComponent,
	validateSearch: ({ page, ...search }: Record<string, unknown>) => {
		return {
			q: ((search.q as string | undefined) ?? undefined)?.replaceAll(
				"?page=themegrill-starter-templates",
				""
			),
			builder: (
				(search.builder as string | undefined) ?? undefined
			)?.replaceAll("?page=themegrill-starter-templates", ""),
			categories: (
				(search.categories as string | undefined) ?? undefined
			)?.replaceAll("?page=themegrill-starter-templates", ""),
		};
	},
	loader: async () => {
		const data = await queryClient.ensureQueryData(sitesQueryOptions());
		useSitesStore.getState().actions.init(data);
	},
	pendingComponent: SiteListSkeleton,
});

function RouteComponent() {
	useSuspenseQuery(sitesQueryOptions());
	return (
		<SidebarProvider
			style={
				{
					"--sidebar-width": "20rem",
					"--sidebar-width-mobile": "20rem",
				} as React.CSSProperties
			}
		>
			<SiteSidebar />
			<SiteList />
		</SidebarProvider>
	);
}
