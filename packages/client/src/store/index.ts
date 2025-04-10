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

// Create a global variable to track if the store has been registered
// This ensures we only register the store once across all imports
declare global {
	interface Window {
		__WP_FEATURE_API_STORE_REGISTERED?: boolean;
	}
}

// Check if the store is already registered
const isStoreRegistered = () => {
	return window.__WP_FEATURE_API_STORE_REGISTERED === true;
};

// Create the store
export const store = createReduxStore( STORE_NAME, {
	reducer,
	actions,
	selectors,
	resolvers,
} );

// Only register the store if it's not already registered
if ( ! isStoreRegistered() ) {
	try {
		register( store );
		// Mark the store as registered
		window.__WP_FEATURE_API_STORE_REGISTERED = true;

		// Initialize the store
		resolveSelect( STORE_NAME ).getRegisteredFeatures();
	} catch ( e ) {
		// If registration fails, it's likely because the store is already registered
		window.__WP_FEATURE_API_STORE_REGISTERED = true;
		// eslint-disable-next-line no-console
		console.warn(
			'Feature API store registration was attempted but failed. This is likely because the store is already registered.'
		);
	}
}
