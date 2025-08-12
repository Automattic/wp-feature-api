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
import { configure } from './config';

const publicApi = {
	store,
	registerFeature,
	unregisterFeature,
	executeFeature,
	getRegisteredFeature,
	getRegisteredFeatures,
	configure,
};

export { store };
export * from './types';
export {
	registerFeature,
	unregisterFeature,
	executeFeature,
	getRegisteredFeature,
	getRegisteredFeatures,
	configure,
};
export * from './command-integration';
export { publicApi as wpFeatures };

export default publicApi;
