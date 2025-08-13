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

export default publicApi;
