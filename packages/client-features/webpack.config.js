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
	entry: {
		index: './src/index.ts',
	},
	output: {
		...defaultConfig.output,
		filename: '[name].js',
		path: __dirname + '/build',
		library: {
			type: 'window',
			export: 'default',
		},
	},
	resolve: {
		...defaultConfig.resolve,
		alias: {
			...defaultConfig.resolve?.alias,
			'@automattic/wp-feature-api': path.resolve(
				__dirname,
				'../client'
			),
		},
	},
	externals: {
		'@wordpress/api-fetch': 'wp.apiFetch',
		'@wordpress/block-editor': 'wp.blockEditor',
		'@wordpress/editor': 'wp.editor',
		'@wordpress/blocks': 'wp.blocks',
		'@wordpress/data': 'wp.data',
		'@wordpress/i18n': 'wp.i18n',
		'@automattic/wp-feature-api': 'wp.features',
	},
	plugins: [
		...defaultConfig.plugins.filter(
			( plugin ) =>
				plugin.constructor.name !== 'DependencyExtractionWebpackPlugin'
		),
		new DependencyExtractionWebpackPlugin(),
	],
};
