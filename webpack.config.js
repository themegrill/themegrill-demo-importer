const defaults = require('@wordpress/scripts/config/webpack.config');
const { resolve } = require('path');
const ForkTsCheckerPlugin = require('fork-ts-checker-webpack-plugin');

module.exports = {
	...defaults,
	output: {
		filename: '[name].js',
		path: resolve(process.cwd(), 'build'),
	},
	entry: {
		dashboard: resolve(process.cwd(), 'src', 'dashboard.tsx'),
	},
	plugins: [...defaults.plugins, new ForkTsCheckerPlugin()],
	resolve: {
		...defaults.resolve,
		alias: {
			...defaults.resolve.alias,
			'@/*': resolve(process.cwd(), 'src/*'),
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
