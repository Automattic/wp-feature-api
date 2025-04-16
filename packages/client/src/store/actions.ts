/**
 * Internal dependencies
 */
import {
	REGISTER_FEATURE,
	RECEIVE_FEATURE,
	UNREGISTER_FEATURE,
	RECEIVE_FEATURES,
	REGISTER_FEATURE_CALLBACK,
	SET_FEATURE_INPUT_IN_PROGRESS,
} from './constants';

import { store } from './index';
import type { Feature } from '../types';

// Action Creators
export function registerFeature( feature: Feature ) {
	return {
		type: REGISTER_FEATURE,
		feature,
	};
}

export function receiveFeature( feature: Feature ) {
	return {
		type: RECEIVE_FEATURE,
		feature,
	};
}

export function unregisterFeature( featureId: string ) {
	return {
		type: UNREGISTER_FEATURE,
		feature: { id: featureId },
	};
}

export function receiveFeatures( features: Feature[] ) {
	return {
		type: RECEIVE_FEATURES,
		features,
	};
}

export function registerFeatureCallback(
	id: string,
	callback: () => unknown | Promise< unknown >
) {
	return async ( { registry, dispatch } ) => {
		const feature = await registry
			.resolveSelect( store )
			.getRegisteredFeature( id );
		if ( ! feature ) {
			return;
		}
		dispatch( {
			type: REGISTER_FEATURE_CALLBACK,
			id,
			callback,
		} );
	};
}

export function setFeatureInputInProgress( id: string ) {
	return {
		type: SET_FEATURE_INPUT_IN_PROGRESS,
		id,
	};
}
