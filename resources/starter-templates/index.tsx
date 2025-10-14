import { QueryClientProvider } from "@tanstack/react-query";
import { RouterProvider } from "@tanstack/react-router";
import { lazy, StrictMode } from "react";
import { createRoot } from "react-dom/client";
import { queryClient } from "./lib/query-client";
import { createRouter } from "./lib/router";
import "./styles/global.pcss";

export const router = createRouter();

const TanStackReactQueryDevtools =
	process.env.NODE_ENV === "production"
		? () => null
		: lazy(() =>
				import("@tanstack/react-query-devtools").then((res) => ({
					default: res.ReactQueryDevtools,
				}))
			);

const root = createRoot(
	document.getElementById("ThemeGrill-Starter-Templates-App")!
);
root.render(
	<StrictMode>
		<QueryClientProvider client={queryClient}>
			<RouterProvider router={router} />
			<TanStackReactQueryDevtools initialIsOpen={false} />
		</QueryClientProvider>
	</StrictMode>
);
