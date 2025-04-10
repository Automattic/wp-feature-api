/**
 * WordPress dependencies
 */
import { createReduxStore, register, resolveSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import reducer from './reducer';
import * as actions from './actions';
import * as selectors from './selectors';
import * as resolvers from './resolvers';
import { STORE_NAME } from './constants';

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

		resolveSelect( STORE_NAME ).getRegisteredFeatures();
	} catch ( e ) {
		window.__WP_FEATURE_API_STORE_REGISTERED = true;
		// eslint-disable-next-line no-console
		console.warn(
			'Feature API store registration was attempted but failed. This is likely because the store is already registered.'
		);
	}
}
