import { QueryClient } from '@tanstack/react-query';
import { createHashHistory, createRouter as createTanStackRouter } from '@tanstack/react-router';
import { routeTree } from '../routeTree.gen';

const hashHistory = createHashHistory();

export function createRouter() {
	const router = createTanStackRouter({
		routeTree,
		history: hashHistory,
	});
	return router;
}

export type Router = ReturnType<typeof createRouter>;

export type RouterContext = {
	queryClient: QueryClient;
};

declare module '@tanstack/react-router' {
	interface Register {
		router: Router;
	}
}
