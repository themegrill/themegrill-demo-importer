import { ProgressIndicator } from "@/starter-templates/components/progress-indicator";
import { Outlet, createRootRoute } from "@tanstack/react-router";
import * as React from "react";

export const Route = createRootRoute({
	component: RootComponent,
});

function RootComponent() {
	return (
		<React.Fragment>
			<Outlet />
			<ProgressIndicator />
		</React.Fragment>
	);
}
