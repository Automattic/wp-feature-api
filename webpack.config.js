/**
 * WordPress dependencies
 */
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const DependencyExtractionWebpackPlugin = require( '@wordpress/dependency-extraction-webpack-plugin' );
/**
 * External dependencies
 */
const path = require( 'path' );
module.exports = {
	...defaultConfig,
	externals: {
		...defaultConfig.externals,
	},
	plugins: [
		...defaultConfig.plugins.filter(
			( plugin ) =>
				plugin.constructor.name !== 'DependencyExtractionWebpackPlugin'
		),
		new DependencyExtractionWebpackPlugin(),
	],
	module: {
		...defaultConfig.module,
	},
	resolve: {
		...defaultConfig.resolve,
		alias: {
			...defaultConfig.resolve?.alias,
			'@wp-feature-api/client-features': path.resolve(
				__dirname,
				'packages/client-features/src'
			),
		},
	},
};
