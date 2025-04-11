/**
 * WordPress dependencies
 */
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
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
			name: 'wpFeatureApiCoreFeatures',
			type: 'umd',
		},
	},
	resolve: {
		...defaultConfig.resolve,
		alias: {
			...defaultConfig.resolve?.alias,
			'@wp-feature-api/client': path.resolve( __dirname, '../client' ),
		},
	},
	externals: {
		'@wordpress/api-fetch': 'wp.apiFetch',
		'@wordpress/block-editor': 'wp.blockEditor',
		'@wordpress/editor': 'wp.editor',
		'@wordpress/blocks': 'wp.blocks',
		'@wordpress/data': 'wp.data',
		'@wordpress/i18n': 'wp.i18n',
	},
};
