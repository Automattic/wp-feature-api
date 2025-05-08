/**
 * External dependencies
 */
import {
	getRegisteredFeatures,
	executeFeature,
	type Feature,
} from '@automattic/wp-feature-api';

/**
 * Internal dependencies
 */
import type { Tool, ToolResult } from '../types/messages';
import type { ToolProvider } from './tool-executor';

// Helper function to hash a string to a hex value using the djb2 hash algorithm
function stringToHex( str: string ): string {
	let hash = 5381;
	for ( let i = 0; i < str.length; i++ ) {
		const char = str.charCodeAt( i );
		hash = ( hash << 5 ) + hash + char;
	}
	hash = hash >>> 0; // Convert to unsigned 32-bit integer
	return hash.toString( 16 );
}

/**
 * Factory function to create a ToolProvider that sources tools
 * from the WordPress Feature API registry.
 *
 * @return A ToolProvider instance.
 */
export const createWpFeatureToolProvider = (): ToolProvider => {
	/**
	 * Fetches features from the WP Feature API and maps them to the agent's Tool format.
	 */
	const getTools = async (): Promise< Tool[] > => {
		try {
			// Fetch all registered features using the API
			const features: Feature[] | null = await getRegisteredFeatures();

			if ( ! features ) {
				// eslint-disable-next-line no-console
				console.warn(
					'WP Feature API: No features returned or store not ready.'
				);
				return [];
			}

			// Map WP Feature objects to the agent's Tool interface
			const tools: Tool[] = features.map( ( feature: Feature ): Tool => {
				const encodedToolName = stringToHex( feature.id );

				return {
					name: encodedToolName,
					displayName: feature.id,
					description: feature.description,
					parameters: feature.input_schema || {},
					execute: async (
						args: Record< string, unknown >
					): Promise< ToolResult > => {
						const originalFeatureId = feature.id;
						try {
							const result = await executeFeature(
								originalFeatureId,
								args
							);
							return { result };
						} catch ( error ) {
							// eslint-disable-next-line no-console
							console.error(
								`Error executing WP Feature (client) "${ originalFeatureId }":`,
								error
							);
							return {
								result: null,
								error:
									error instanceof Error
										? error.message
										: 'Unknown error executing client feature',
							};
						}
					},
				};
			} );

			return tools;
		} catch ( error ) {
			// eslint-disable-next-line no-console
			console.error( 'Error fetching or mapping WP Features:', error );
			return []; // Return empty array on error
		}
	};

	return {
		getTools,
	};
};
