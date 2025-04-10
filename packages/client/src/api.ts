/**
 * WordPress dependencies
 */
import { dispatch, select } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { store } from './store';
import type { Feature } from './types';

/**
 * Registers a feature with the feature registry.
 *
 * @param {Feature} feature The feature to register
 */
export function registerFeature( feature: Feature ) {
	dispatch( store ).registerFeature( feature );
}

/**
 * Unregisters a feature from the feature registry.
 *
 * @param {string} featureId The ID of the feature to unregister
 */
export function unregisterFeature( featureId: string ) {
	dispatch( store ).unregisterFeature( featureId );
}

/**
 * Retrieves the definition of a registered feature.
 *
 * @param {string} featureId The ID of the feature to retrieve.
 * @return {Feature | null} The feature definition object or null if not found.
 */
export function getFeatureDefinition( featureId: string ): Feature | null {
	const feature = select( store )?.getRegisteredFeature( featureId );
	return feature || null;
}

/**
 * Executes a registered feature.
 *
 * @param {string} featureId The ID of the feature to execute
 * @param {any}    args      Arguments to pass to the feature callback
 * @return {Promise<unknown>} The result of the feature execution
 */
export async function executeFeature(
	featureId: string,
	args: any
): Promise< unknown > {
	const callback = select( store ).getRegisteredFeatureCallback( featureId );

	if ( typeof callback !== 'function' ) {
		throw new Error( `No callback registered for feature: ${ featureId }` );
	}

	try {
		return await callback( args );
	} catch ( error ) {
		// eslint-disable-next-line no-console
		console.error( `Error executing feature ${ featureId }:`, error );
		throw error;
	}
}
