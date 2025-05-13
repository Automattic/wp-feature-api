/**
 * WordPress dependencies
 */
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const DependencyExtractionWebpackPlugin = require( '@wordpress/dependency-extraction-webpack-plugin' );

module.exports = {
	...defaultConfig,
	entry: {
		index: './src/index.ts',
	},
	output: {
		...defaultConfig.output,
		filename: '[name].js',
		path: __dirname + '/build',
		library: {
			name: [ 'wp', 'features' ],
			type: 'window',
			export: 'default',
		},
	},
	externals: {
		'@wordpress/api-fetch': 'wp.apiFetch',
		'@wordpress/block-editor': 'wp.blockEditor',
		'@wordpress/blocks': 'wp.blocks',
		'@wordpress/components': 'wp.components',
		'@wordpress/core-data': 'wp.coreData',
		'@wordpress/data': 'wp.data',
		'@wordpress/element': 'wp.element',
		'@wordpress/i18n': 'wp.i18n',
	},
	plugins: [
		...defaultConfig.plugins.filter(
			( plugin ) =>
				plugin.constructor.name !== 'DependencyExtractionWebpackPlugin'
		),
		new DependencyExtractionWebpackPlugin(),
	],
};
