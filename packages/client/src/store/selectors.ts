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
		return Object.values( state.featuresById );
	},
	( state: FeaturesState ) => [ state.featuresById ]
);

// Select a feature by ID
export const getRegisteredFeature = (
	state: FeaturesState,
	id: string
): Feature | null => state.featuresById[ id ] || null;

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
