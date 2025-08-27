const defaults = require('@wordpress/scripts/config/webpack.config');
const { resolve } = require('path');
const ForkTsCheckerPlugin = require('fork-ts-checker-webpack-plugin');
const { TanStackRouterWebpack } = require('@tanstack/router-plugin/webpack');

module.exports = {
	...defaults,
	output: {
		filename: '[name].js',
		path: resolve(process.cwd(), 'dist'),
	},
	entry: {
		dashboard: resolve(process.cwd(), 'resources/onboarding', 'index.tsx'),
	},
	plugins: [
		...defaults.plugins,
		TanStackRouterWebpack({
			routesDirectory: resolve(process.cwd(), 'resources/onboarding/routes'),
			generatedRouteTreeFile: resolve(process.cwd(), 'resources/onboarding/routeTree.gen.ts'),
			routeFileExtensions: ['.ts', '.tsx'],
		}),
		new ForkTsCheckerPlugin(),
	],
	resolve: {
		...defaults.resolve,
		alias: {
			...defaults.resolve.alias,
			'@/*': resolve(process.cwd(), 'resources/*'),
		},
	},
	devServer:
		process.env.NODE_ENV === 'production'
			? undefined
			: {
					...defaults.devServer,
					headers: { 'Access-Control-Allow-Origin': '*' },
					allowedHosts: 'all',
				},
};
