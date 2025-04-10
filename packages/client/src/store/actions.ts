/**
 * Internal dependencies
 */
import {
	REGISTER_FEATURE,
	RECEIVE_FEATURE,
	UNREGISTER_FEATURE,
	RECEIVE_FEATURES,
	REGISTER_FEATURE_CALLBACK,
	STORE_NAME,
} from './constants';
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

interface RegistryInterface {
	resolveSelect: ( storeName: string ) => {
		getRegisteredFeature: ( id: string ) => Promise< Feature | undefined >;
	};
}

interface DispatchInterface {
	( action: {
		type: string;
		id: string;
		callback: () => unknown | Promise< unknown >;
	} ): void;
}

export function registerFeatureCallback(
	id: string,
	callback: () => unknown | Promise< unknown >
) {
	return async ( {
		registry,
		dispatch,
	}: {
		registry: RegistryInterface;
		dispatch: DispatchInterface;
	} ) => {
		const feature = await registry
			.resolveSelect( STORE_NAME )
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
