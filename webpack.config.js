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
	resolve: {
		...defaultConfig.resolve,
		alias: {
			...defaultConfig.resolve?.alias,
			'@wp-feature-api/client': path.resolve(
				__dirname,
				'packages/client'
			),
			'@wp-feature-api/core-features': path.resolve(
				__dirname,
				'packages/core-features'
			),
		},
	},
};
