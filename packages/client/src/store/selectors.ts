/**
 * Selectors
 */

/**
 * WordPress dependencies
 */
import { createSelector, createRegistrySelector } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { store } from './index';
import type { Feature, FeaturesState } from '../types';

// Select all features
export const getRegisteredFeatures = createSelector(
	( state: FeaturesState ): Feature[] => {
		return Object.values( state.featuresById ).filter( ( feature ) => {
			// If there's no is_eligible function, feature is always eligible
			if ( typeof feature.is_eligible !== 'function' ) {
				return true;
			}

			return feature.is_eligible();
		} );
	},
	( state: FeaturesState ) => [ state.featuresById ]
);

// Select a feature by ID
export const getRegisteredFeature = (
	state: FeaturesState,
	id: string
): Feature | null => {
	const feature = state.featuresById[ id ] || null;

	// If feature doesn't exist or there's no is_eligible function, return as is
	if ( ! feature || typeof feature.is_eligible !== 'function' ) {
		return feature;
	}

	// Check if the feature is eligible
	return feature.is_eligible() ? feature : null;
};

// Return the feature callback
export const getRegisteredFeatureCallback = createRegistrySelector(
	( select ) =>
		(
			state: FeaturesState,
			id: string
		): Feature[ 'callback' ] | undefined => {
			const feature = select( store ).getRegisteredFeature( id );
			return feature?.callback;
		}
);

// Select the feature input in progress
export const getFeatureInputInProgress = (
	state: FeaturesState
): string | null => state.featureInputInProgressId;
