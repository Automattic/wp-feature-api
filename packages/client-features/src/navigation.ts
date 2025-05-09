/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * External dependencies
 */
import type { Feature } from '@automattic/wp-feature-api';

/**
 * Client-side feature for browser navigation.
 */
export const navigate: Feature = {
	id: 'tool-navigate',
	name: __( 'Navigate Browser' ),
	description: __( 'Navigates the browser to a specified URL in WordPress.' ),
	type: 'tool',
	location: 'client',
	categories: [ 'core', 'navigation' ],
	input_schema: {
		type: 'object',
		properties: {
			url: {
				type: 'string',
				description: __(
					'The relative URL to navigate to (must start with / for frontend URLs, and /wp-admin/ for admin URLs).'
				),
				ui_hint: 'url',
				pattern: '^/',
			},
		},
		required: [ 'url' ],
	},
	callback: ( args: { url: string } ) => {
		// Validation for relative URLs only
		if ( typeof args?.url !== 'string' || args.url.trim() === '' ) {
			throw new Error( 'A valid URL string is required for navigation.' );
		}

		// Ensure URL starts with a '/'
		if ( ! args.url.startsWith( '/' ) ) {
			throw new Error( 'URL must be relative and start with "/"' );
		}

		// Check for absolute URLs - not allowed
		if (
			args.url.startsWith( 'http://' ) ||
			args.url.startsWith( 'https://' )
		) {
			throw new Error(
				'Absolute URLs are not allowed. URL must be relative and start with "/"'
			);
		}

		let finalUrl = args.url;
		try {
			if ( typeof ajaxurl !== 'string' ) {
				throw new Error(
					'Cannot determine WordPress admin URL (ajaxurl not found).'
				);
			}

			// Handle relative URLs
			const wpAdminPath = '/wp-admin/';
			const currentPath = location.pathname;
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

			finalUrl = siteRoot + finalUrl;

			// Use a timeout to ensure the success response is returned first
			setTimeout( () => {
				document.location.href = finalUrl;
			}, 1000 );
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


/**
 * Client-side resource to tell which URL is currently being viewed.
 */
export const currentUrl: Feature = {
	id: 'resource-current-url',
	name: __( 'Current URL' ),
	description: __( 'Returns the current URL.' ),
	type: 'resource',
	location: 'client',
	categories: [ 'core', 'navigation' ],
	callback: () => {
		return { url: location.href };
	},
};
