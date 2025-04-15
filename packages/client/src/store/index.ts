/**
 * WordPress dependencies
 */
import { createReduxStore, dispatch, register } from '@wordpress/data';

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
	}
}

const isStoreRegistered = () => {
	return window.__WP_FEATURE_API_STORE_REGISTERED === true;
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
	} catch ( e ) {
		window.__WP_FEATURE_API_STORE_REGISTERED = true;
		// eslint-disable-next-line no-console
		console.warn(
			'Feature API store registration was attempted but failed. This is likely because the store is already registered.'
		);
	}
}
