import { createFileRoute } from '@tanstack/react-router';
import { siteDataQueryOptions } from '../components/features/api/site.api';
import Import from '../components/features/sites/components/detail/import/Import';
import ImportSkeleton from '../components/features/sites/components/detail/import/ImportSkeleton';
import { queryClient } from '../lib/query-client';
import { PluginItem } from '../lib/types';

const mergePlugins = (
	pluginsList: PluginItem[],
	plugins: Record<string, { name: string; description: string }>,
): PluginItem[] => {
	const uniquePlugins = new Map();

	// Add existing pluginsList items to the map
	pluginsList.forEach((item) => {
		uniquePlugins.set(item.plugin, { ...item, isMandatory: false });
	});

	// Add/Override with plugins from the API object with isSelected and isMandatory true
	Object.entries(plugins).forEach(([pluginPath, pluginData]) => {
		uniquePlugins.set(pluginPath, {
			plugin: pluginPath,
			name: pluginData.name,
			description: pluginData.description,
			isSelected: true,
			isMandatory: true,
		});
	});

	// Convert Map values back to array
	return Array.from(uniquePlugins.values());
};

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
					isMandatory: true,
				}),
			);

			return {
				demo: data?.data,
				plugins: newPlugins || [],
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
					<h2>Unable to Load Demo</h2>
					<p>{error.message}</p>
					<button onClick={() => window.location.reload()}>Try Again</button>
				</div>
			</div>
		);
	},
});

function RouteComponent() {
	// const data = Route.useLoaderData();
	return <Import />;
}
