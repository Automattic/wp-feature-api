/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import type { Feature } from '../types';

/**
 * Client-side feature for browser navigation.
 */
export const navigate: Feature = {
	id: 'core/navigate',
	name: __( 'Navigate Browser' ),
	description: __( 'Navigates the browser to a specified URL.' ),
	type: 'tool',
	location: 'client',
	categories: [ 'core', 'navigation' ],
	input_schema: {
		type: 'object',
		properties: {
			url: {
				type: 'string',
				description: __(
					'The URL to navigate to (can be absolute or relative).'
				),
			},
		},
		required: [ 'url' ],
	},
	callback: ( args: { url: string } ) => {
		// Some simple validation, and making sure we only try to redirect somewhere on site
		if ( typeof args?.url !== 'string' || args.url.trim() === '' ) {
			// eslint-disable-next-line no-console
			console.error(
				'Navigation feature called without a valid URL string.'
			);
			throw new Error( 'A valid URL string is required for navigation.' );
		}

		let finalUrl = args.url;
		try {
			if ( typeof ajaxurl !== 'string' ) {
				throw new Error(
					'Cannot determine WordPress admin URL (ajaxurl not found).'
				);
			}

			if (
				! finalUrl.startsWith( 'http://' ) &&
				! finalUrl.startsWith( 'https://' )
			) {
				if ( finalUrl.startsWith( '/' ) ) {
					const wpAdminPath = '/wp-admin/';
					const currentPath = location.pathname; // e.g., /site-wp-dev/wp-admin/some-page.php
					const adminPathIndex = currentPath.indexOf( wpAdminPath );
					let siteRoot = location.origin;

					if ( adminPathIndex > 0 ) {
						// Subdirectory found (e.g., /site-wp-dev)
						const subDirectoryPath = currentPath.substring(
							0,
							adminPathIndex
						);
						siteRoot += subDirectoryPath;
					} else if ( adminPathIndex === -1 ) {
						// eslint-disable-next-line no-console
						console.warn(
							'Could not determine WP admin path from location.pathname. Assuming root installation.',
							currentPath
						);
					}

					finalUrl = siteRoot + finalUrl; // e.g., http://localhost:6888/site-wp-dev + /wp-admin/edit.php
				} else {
					// Non-root relative paths (e.g., 'edit.php') - needs admin base path
					// Reconstruct admin base path using the same logic as above
					const wpAdminPathForRelative = '/wp-admin/';
					const currentPathForRelative = location.pathname;
					const adminPathIndexForRelative =
						currentPathForRelative.indexOf(
							wpAdminPathForRelative
						);
					let adminBase = location.origin;

					if ( adminPathIndexForRelative !== -1 ) {
						adminBase += currentPathForRelative.substring(
							0,
							adminPathIndexForRelative +
								wpAdminPathForRelative.length
						);
					} else {
						// eslint-disable-next-line no-console
						console.warn(
							'Could not determine WP admin path from location.pathname for relative URL. Assuming /wp-admin/ base.',
							currentPathForRelative
						);
						adminBase += wpAdminPathForRelative;
					}

					finalUrl = adminBase + finalUrl.replace( /^\/+/, '' );
				}
			}

			document.location.href = finalUrl;
			return { success: true, url: finalUrl };
		} catch ( error ) {
			// eslint-disable-next-line no-console
			console.error(
				`Navigation failed for URL: ${ finalUrl } (original: ${ args.url })`,
				error
			);
			throw new Error(
				`Navigation failed: ${
					error instanceof Error ? error.message : String( error )
				}`
			);
		}
	},
};
