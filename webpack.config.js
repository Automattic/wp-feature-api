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
			// Point directly to the source of the packages for the root build
			// This allows the root DependencyExtractionWebpackPlugin to see the actual imports.
			'@wp-feature-api/client': path.resolve(
				__dirname,
				'packages/client/src'
			),
			'@wp-feature-api/client-features': path.resolve(
				__dirname,
				'packages/client-features/src'
			),
		},
	},
};
