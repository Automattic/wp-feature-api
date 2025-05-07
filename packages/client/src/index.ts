/**
 * Internal dependencies
 */
import { store } from './store';
import {
	registerFeature,
	unregisterFeature,
	executeFeature,
	getRegisteredFeature,
	getRegisteredFeatures,
} from './api';

const publicApi = {
	store,
	registerFeature,
	unregisterFeature,
	executeFeature,
	getRegisteredFeature,
	getRegisteredFeatures,
};

if ( typeof window !== 'undefined' ) {
	// @ts-ignore
	window.wp = window.wp || {};
	// @ts-ignore
	window.wp.features = publicApi;
}

export { store };
export * from './types';
export {
	registerFeature,
	unregisterFeature,
	executeFeature,
	getRegisteredFeature,
	getRegisteredFeatures,
};
export * from './command-integration';
export { publicApi as wpFeatures };
