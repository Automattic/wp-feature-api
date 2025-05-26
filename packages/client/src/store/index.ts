/**
 * WordPress dependencies
 */
import { createReduxStore, dispatch, register } from '@wordpress/data';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import reducer from './reducer';
import * as actions from './actions';
import * as selectors from './selectors';
import * as resolvers from './resolvers';
import { ENTITY_KIND, ENTITY_NAME, STORE_NAME } from './constants';
import { __ } from '@wordpress/i18n';
import { store as coreStore } from '@wordpress/core-data';

// Create a global variable to track if the store has been registered, this ensures we only register the store once across all imports
// if multiple plugins are using the Feature API.
// TODO: We may want to expose the api over wp.featureApi.* in the future like WordPress does.
declare global {
	interface Window {
		__WP_FEATURE_API_STORE_REGISTERED?: boolean;
		__WP_FEATURE_API_REST_AVAILABLE?: boolean;
	}
}

const isStoreRegistered = () => {
	return window.__WP_FEATURE_API_STORE_REGISTERED === true;
};

const isRestApiAvailable = async () => {
	if ( typeof window.__WP_FEATURE_API_REST_AVAILABLE !== 'undefined' ) {
		return window.__WP_FEATURE_API_REST_AVAILABLE;
	}

	try {
		// Try to fetch the features endpoint
		await apiFetch( { path: '/wp/v2/features' } );
		window.__WP_FEATURE_API_REST_AVAILABLE = true;
		return true;
	} catch ( e ) {
		window.__WP_FEATURE_API_REST_AVAILABLE = false;
		return false;
	}
};

export const store = createReduxStore( STORE_NAME, {
	reducer,
	actions,
	selectors,
	resolvers,
} );

if ( ! isStoreRegistered() ) {
	try {
		register( store );
		window.__WP_FEATURE_API_STORE_REGISTERED = true;

		// Only register the entity if REST API is available
		isRestApiAvailable().then( ( available ) => {
			if ( available ) {
				dispatch( coreStore )?.addEntities( [
					{
						name: ENTITY_NAME,
						kind: ENTITY_KIND,
						baseURL: '/wp/v2/features',
						baseURLParams: { context: 'edit' },
						plural: 'features',
						label: __( 'Features' ),
						transientEdits: {
							callback: true,
						},
					},
				] );
			} else {
				// eslint-disable-next-line no-console
				console.warn(
					'Feature API REST endpoints are not available. Backend features cannot be interacted with.'
				);
			}
		} );
	} catch ( e ) {
		window.__WP_FEATURE_API_STORE_REGISTERED = true;
		// eslint-disable-next-line no-console
		console.warn(
			'Feature API store registration was attempted but failed. This is likely because the store is already registered.'
		);
	}
}
