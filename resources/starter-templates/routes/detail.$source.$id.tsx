import { SidebarProvider } from "@/starter-templates/components/ui/sidebar";
import { siteQueryOptions } from "@/starter-templates/features/sites/api/sites.queries";
import { FlowDialog } from "@/starter-templates/features/sites/components/detail/flow/flow-dialog";
import { PreviewFrame } from "@/starter-templates/features/sites/components/detail/preview-frame";
import { SiteDetailForm } from "@/starter-templates/features/sites/components/detail/site-detail-form";
import { SiteDetailSidebar } from "@/starter-templates/features/sites/components/detail/site-detail-sidebar";
import { queryClient } from "@/starter-templates/lib/query-client";
import { useSiteDetailStore } from "@/starter-templates/stores/site-detail.store";
import { useSuspenseQuery } from "@tanstack/react-query";
import { createFileRoute } from "@tanstack/react-router";

export const Route = createFileRoute("/detail/$source/$id")({
	component: RouteComponent,
	async loader({ params }) {
		const data = await queryClient.ensureQueryData(
			siteQueryOptions(atob(params.source), params.id)
		);
		useSiteDetailStore.getState().init(data, "initial", "desktop");
	},
});

function RouteComponent() {
	const { id, source } = Route.useParams();
	useSuspenseQuery(siteQueryOptions(atob(source), id));
	return (
		<SiteDetailForm>
			<SidebarProvider>
				<SiteDetailSidebar />
				<PreviewFrame />
				<FlowDialog />
			</SidebarProvider>
		</SiteDetailForm>
	);
}
