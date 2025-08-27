import { QueryClientProvider } from '@tanstack/react-query';
import { RouterProvider } from '@tanstack/react-router';
import { lazy, StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import { queryClient } from './lib/query-client';
import { createRouter } from './lib/router';
import { LocalizedDataProvider } from './LocalizedDataContext';
import './styles/pcss/dashboard.pcss';

export const router = createRouter();

const TanStackReactQueryDevtools =
	process.env.NODE_ENV === 'production'
		? () => null
		: lazy(() =>
				import('@tanstack/react-query-devtools').then((res) => ({
					default: res.ReactQueryDevtools,
				})),
			);

// const TanStackRouterDevtools =
// 	process.env.NODE_ENV === 'production'
// 		? () => null
// 		: lazy(() =>
// 				import('@tanstack/router-devtools').then((res) => ({
// 					default: res.TanStackRouterDevtools,
// 				})),
// 			);

const root = createRoot(document.getElementById('tg-demo-importer')!);
root.render(
	<StrictMode>
		<QueryClientProvider client={queryClient}>
			<LocalizedDataProvider>
				<RouterProvider router={router} />
				{/* <TanStackRouterDevtools router={router} /> */}
				<TanStackReactQueryDevtools initialIsOpen={false} />
			</LocalizedDataProvider>
		</QueryClientProvider>
	</StrictMode>,
);
