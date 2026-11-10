import { createFileRoute } from '@tanstack/react-router';
import { __ } from '@wordpress/i18n';
import { siteDataQueryOptions } from '../components/features/api/site.api';
import Import from '../components/features/sites/components/detail/import/Import';
import ImportSkeleton from '../components/features/sites/components/detail/import/ImportSkeleton';
import { decodeHtmlEntities } from '../lib/sanitize-plugin-description';
import { queryClient } from '../lib/query-client';
import { PluginItem } from '../lib/types';

const EASY_MCP_AI_PLUGIN = 'easy-mcp-ai/easy-mcp-ai.php';

// Shorter copy for plugins whose demo API description is too long for the sidebar.
const getDescriptionOverrides = (): Record<string, string> => ({
	'allfeedback/allfeedback.php': __(
		'Collect feedback with simple forms, stored on your own site.',
		'themegrill-demo-importer',
	),
});

export const Route = createFileRoute('/import/$theme/$id')({
	component: RouteComponent,
	loader: async ({ params }) => {
		try {
			const data = await queryClient.ensureQueryData(siteDataQueryOptions(params));
			if (!data) {
				throw new Error('No data received');
			}

			const isEmpty = !data.data || Object.keys(data.data).length === 0;
			const descriptionOverrides = getDescriptionOverrides();

			const newPlugins: PluginItem[] = Object.entries(data?.data?.plugins || {}).map(
				([pluginPath, pluginData]) => {
					const pluginSlug = pluginPath.split('/')[0] || pluginPath;
					const fallbackName = pluginSlug
						.split('-')
						.map((word: string) => word.charAt(0).toUpperCase() + word.slice(1))
						.join(' ');
					return {
						plugin: pluginPath,
						name: decodeHtmlEntities(pluginData.name || fallbackName),
						description: descriptionOverrides[pluginPath] || pluginData.description || '',
						isSelected: true,
						isMandatory: pluginData.mandatory,
					};
				},
			);

			const sortedPlugins = newPlugins.sort((a, b) => {
				if (a.isMandatory === b.isMandatory) return 0;
				return a.isMandatory ? -1 : 1;
			});

			// Easy MCP AI is offered on every template as the last, opt-in feature.
			if (!isEmpty && !sortedPlugins.some((item) => item.plugin === EASY_MCP_AI_PLUGIN)) {
				sortedPlugins.push({
					plugin: EASY_MCP_AI_PLUGIN,
					name: 'Easy MCP AI',
					description: __(
						'Let AI assistants like Claude and ChatGPT work with your site.',
						'themegrill-demo-importer',
					),
					isSelected: false,
					isMandatory: false,
				});
			}

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
