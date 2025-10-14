const defaults = require('@wordpress/scripts/config/webpack.config');
const { resolve } = require('path');
const ForkTsCheckerPlugin = require('fork-ts-checker-webpack-plugin');
const { tanstackRouter } = require('@tanstack/router-plugin/webpack');

module.exports = {
	...defaults,
	output: {
		...defaults.output,
		filename: '[name].js',
		path: resolve(process.cwd(), 'dist'),
	},
	entry: {
		'starter-templates': resolve(process.cwd(), 'resources/starter-templates', 'index.tsx'),
	},
	resolve: {
		...defaults.resolve,
		alias: {
			...defaults.resolve.alias,
			'@/*': resolve(process.cwd(), 'resources/*'),
		},
	},
	plugins: [
		...defaults.plugins,
		new ForkTsCheckerPlugin(),
		tanstackRouter({
			target: 'react',
			routesDirectory: resolve(process.cwd(), 'resources/starter-templates/routes'),
			generatedRouteTreeFile: resolve(
				process.cwd(),
				'resources/starter-templates/routeTree.gen.ts',
			),
			routeFileExtensions: ['.ts', '.tsx'],
		}),
	].filter(Boolean),
	devServer:
		process.env.NODE_ENV === 'production'
			? undefined
			: {
					...defaults.devServer,
					headers: { 'Access-Control-Allow-Origin': '*' },
					allowedHosts: 'all',
				},
};
