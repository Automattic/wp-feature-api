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
	// Ensure the default DependencyExtractionWebpackPlugin is removed or overridden
	// We'll add our own configured instance below.
	plugins: [
		// Filter out the default DependencyExtractionWebpackPlugin instance
		...defaultConfig.plugins.filter(
			( plugin ) =>
				plugin.constructor.name !== 'DependencyExtractionWebpackPlugin'
		),
		// Add the plugin back with default configuration. By pointing the aliases
		// to the source code below, the default plugin should now correctly detect
		// the dependencies when building the main entry point (src/index.js).
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
				'packages/client/src' // Point to src, not the pre-built package root
			),
			'@wp-feature-api/client-features': path.resolve(
				__dirname,
				'packages/client-features/src' // Point to src
			),
		},
	},
};
