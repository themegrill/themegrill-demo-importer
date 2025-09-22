import { createFileRoute } from '@tanstack/react-router';
import { __ } from '@wordpress/i18n';
import { siteDataQueryOptions } from '../components/features/api/site.api';
import Import from '../components/features/sites/components/detail/import/Import';
import ImportSkeleton from '../components/features/sites/components/detail/import/ImportSkeleton';
import { queryClient } from '../lib/query-client';
import { PluginItem } from '../lib/types';

export const Route = createFileRoute('/import/$theme/$id')({
	component: RouteComponent,
	loader: async ({ params }) => {
		try {
			const data = await queryClient.ensureQueryData(siteDataQueryOptions(params));
			if (!data) {
				throw new Error('No data received');
			}

			const isEmpty = !data.data || Object.keys(data.data).length === 0;

			const newPlugins: PluginItem[] = Object.entries(data?.data?.plugins || {}).map(
				([pluginPath, pluginData]) => ({
					plugin: pluginPath,
					name: pluginData.name,
					description: pluginData.description,
					isSelected: true,
					isMandatory: pluginData.mandatory,
				}),
			);

			const sortedPlugins = newPlugins.sort((a, b) => {
				if (a.isMandatory === b.isMandatory) return 0;
				return a.isMandatory ? -1 : 1;
			});

			return {
				demo: data?.data,
				plugins: sortedPlugins || [],
				pages: data?.data?.pages || [],
				isEmpty,
			};
		} catch (error) {
			console.error('Failed to load route data:', error);
			throw error;
		}
	},
	pendingComponent: () => <ImportSkeleton />,
	errorComponent({ error }) {
		return (
			<div className="flex items-center justify-center h-screen">
				<div className="text-center">
					<h2>{__('Something went wrong.', 'themegrill-demo-importer')}</h2>
					{/* <p>{error.message}</p> */}
					<button onClick={() => window.location.reload()}>
						{__('Try Again', 'themegrill-demo-importer')}
					</button>
				</div>
			</div>
		);
	},
});

function RouteComponent() {
	// const data = Route.useLoaderData();
	return <Import />;
}
