/**
 * WordPress dependencies
 */
import { useCallback } from '@wordpress/element';
import { select } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { store as featureStore } from '../store';

/**
 * Custom hook to execute a registered client-side feature callback.
 *
 * @return A function that executes the feature callback.
 */
export const useClientFeature = () => {
	/**
	 * Executes the callback function registered for a given feature ID.
	 *
	 * @param featureId The unique identifier of the feature.
	 * @param args      The arguments to pass to the feature's callback function.
	 * @return A promise that resolves with the result of the callback execution, or rejects on error.
	 * @throws Will re-throw any error caught during the callback execution.
	 */
	const executeClientFeature = useCallback(
		async ( featureId: string, args: any ): Promise< unknown > => {
			const callback =
				select( featureStore ).getRegisteredFeatureCallback(
					featureId
				);

			if ( typeof callback !== 'function' ) {
				// eslint-disable-next-line no-console
				console.error(
					`No callback registered for feature: ${ featureId }`
				);
				throw new Error(
					`No callback registered for feature: ${ featureId }`
				);
			}

			try {
				// Execute the registered callback with the provided arguments
				return await callback( args );
			} catch ( error ) {
				// eslint-disable-next-line no-console
				console.error(
					`Error executing feature ${ featureId }:`,
					error
				);
				// Re-throw the error to be handled by the caller
				throw error;
			}
		},
		[]
	);

	return { executeClientFeature };
};
