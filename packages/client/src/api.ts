/**
 * WordPress dependencies
 */
import { dispatch, select, resolveSelect } from '@wordpress/data';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { store } from './store';
import type { Feature } from './types';
import { removeNullValues } from './utils';

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
export function getRegisteredFeature( featureId: string ): Feature | null {
	const feature = select( store )?.getRegisteredFeature( featureId );
	return feature || null;
}

/**
 * Retrieves all registered features.
 *
 * @return An array of all registered feature definition objects, or null if the store is not ready.
 */
export async function getRegisteredFeatures(): Promise< Feature[] | null > {
	const features = await resolveSelect( store )?.getRegisteredFeatures();
	return features || null;
}

/**
 * Executes a registered feature.
 *
 * @param featureId The ID of the feature to execute
 * @param args      Arguments to pass to the feature callback
 * @return The result of the feature execution
 */
export async function executeFeature(
	featureId: string,
	args: any
): Promise< unknown > {
	const feature = select( store ).getRegisteredFeature( featureId );

	if ( ! feature ) {
		throw new Error( `Feature not found: ${ featureId }` );
	}

	try {
		if ( feature.location === 'client' ) {
			const callback =
				select( store ).getRegisteredFeatureCallback( featureId );

			if ( typeof callback !== 'function' ) {
				throw new Error(
					`No callback registered for client feature: ${ featureId }`
				);
			}

			return await callback( args );
		}

		// Server-side features
		const method = feature.type === 'tool' ? 'POST' : 'GET';
		let requestPath = `/wp/v2/features/${ featureId }/run`;
		const options: { method: string; data?: any } = { method };

		// The LLM may pass in a bunch of new values for things, that can cause validation errors for certain
		// fields like 'slug', etc. This cleans the args by removing null values.
		const cleanedArgs = removeNullValues( args );

		if (
			method === 'GET' &&
			cleanedArgs &&
			Object.keys( cleanedArgs ).length
		) {
			requestPath = `${ requestPath }?${ new URLSearchParams(
				Object.entries( cleanedArgs ).map( ( [ key, value ] ) => [
					key,
					typeof value === 'object'
						? JSON.stringify( value )
						: String( value ),
				] )
			) }`;
		} else if ( method === 'POST' && cleanedArgs ) {
			options.data = cleanedArgs;
		}

		return await apiFetch< unknown >( {
			path: requestPath,
			...options,
		} );
	} catch ( error ) {
		// eslint-disable-next-line no-console
		console.error( `Error executing feature ${ featureId }:`, error );
		throw error;
	}
}
