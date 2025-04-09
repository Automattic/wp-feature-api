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
	name: __( 'Navigate Browser' ), // Assuming i18n domain
	description: __( 'Navigates the browser to a specified URL.' ),
	type: 'tool',
	location: 'client', // Explicitly mark as client-side
	categories: [ 'core', 'navigation' ], // Suggesting categories
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
					// Starts with '/', treat as relative to site root
					finalUrl = `${ location.origin }${ finalUrl }`;
				} else {
					// Assume relative to admin root
					const adminBase = ajaxurl.substring(
						0,
						ajaxurl.lastIndexOf( '/' ) + 1
					);
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
