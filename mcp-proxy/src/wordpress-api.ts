/**
 * External dependencies
 */
// @ts-ignore Import errors will be resolved at runtime
import fetch from 'node-fetch';

/**
 * Internal dependencies
 */
import { log } from './utils';

/**
 * WordPress API request function with basic auth support
 *
 * @param {string} endpoint        - The API endpoint (e.g., 'wp/v2/posts')
 * @param {Object} options         - Request options
 * @param {string} options.method  - HTTP method ('GET' or 'POST')
 * @param {Object} options.params  - Query parameters for GET or body for POST
 * @param {string} options.baseUrl - Base URL for the WordPress site (defaults to env.WP_API_URL)
 * @return {Promise<any>} API response as JSON
 */
export async function wpRequest(
	endpoint: string,
	options: {
		method?: 'GET' | 'POST';
		params?: Record< string, any >;
		baseUrl?: string;
	} = {}
) {
	const {
		method = 'GET',
		params = {},
		baseUrl = process.env.WP_API_URL,
	} = options;

	if ( ! baseUrl ) {
		throw new Error(
			'WordPress API URL not set. Set WP_API_URL environment variable.'
		);
	}

	log( `env: ${ JSON.stringify( process.env ) }` );

	// Get auth credentials from environment variables
	const username = process.env.WP_API_USERNAME;
	const password = process.env.WP_API_PASSWORD;

	if ( ! username || ! password ) {
		throw new Error(
			'WordPress API credentials not set. Set WP_API_USERNAME and WP_API_PASSWORD environment variables.'
		);
	}

	// Prepare authorization header
	const auth = Buffer.from( `${ username }:${ password }` ).toString(
		'base64'
	);

	// Build URL with query params for GET requests
	let url = `${ baseUrl.replace( /\/$/, '' ) }/wp-json/${ endpoint.replace(
		/^\//,
		''
	) }`;
	log( `Requesting url: ${ url }` );

	const headers: Record< string, string > = {
		Authorization: `Basic ${ auth }`,
		'Content-Type': 'application/json',
	};

	const fetchOptions: {
		method: string;
		headers: Record< string, string >;
		body?: string;
	} = {
		method,
		headers,
	};

	log( `Params: ${ JSON.stringify( params ) }` );

	// Handle GET vs POST requests
	if ( method === 'GET' && Object.keys( params ).length ) {
		const queryString = new URLSearchParams(
			Object.entries( params ).map( ( [ key, value ] ) => [
				key,
				typeof value === 'object'
					? JSON.stringify( value )
					: String( value ),
			] )
		).toString();
		url = `${ url }?${ queryString }`;
	} else if ( method === 'POST' ) {
		fetchOptions.body = JSON.stringify( params );
	}

	try {
		const response = await fetch( url, fetchOptions );

		// Handle error responses
		if ( ! response.ok ) {
			const errorText = await response.text();
			throw new Error(
				`WordPress API error (${ response.status }): ${ errorText }`
			);
		}

		return await response.json();
	} catch ( error ) {
		console.error( 'WordPress API request failed:', error );
		throw error;
	}
}
