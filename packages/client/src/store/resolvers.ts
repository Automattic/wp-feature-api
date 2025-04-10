/**
 * WordPress dependencies
 */
import { store as coreStore } from '@wordpress/core-data';

/**
 * Internal dependencies
 */
import { ENTITY_KIND, ENTITY_NAME, STORE_NAME } from './constants';
import { receiveFeatures, receiveFeature } from './actions';
import type { Feature } from '../types';

interface RegistryInterface {
	resolveSelect: ( storeName: any ) => {
		getEntityRecords: (
			kind: string,
			name: string
		) => Promise< Feature[] >;
		getEntityRecord: (
			kind: string,
			name: string,
			id: string
		) => Promise< Feature >;
	};
	select: ( storeName: string ) => {
		getRegisteredFeature: ( id: string ) => Feature | null;
	};
}

interface DispatchInterface {
	( action: any ): void;
}

export function getRegisteredFeatures() {
	return async ( {
		dispatch,
		registry,
	}: {
		dispatch: DispatchInterface;
		registry: RegistryInterface;
	} ) => {
		const features = await registry
			.resolveSelect( coreStore )
			.getEntityRecords( ENTITY_KIND, ENTITY_NAME );
		dispatch( receiveFeatures( features ) );
	};
}

export function getRegisteredFeature( id: string ) {
	return async ( {
		dispatch,
		registry,
	}: {
		dispatch: DispatchInterface;
		registry: RegistryInterface;
	} ) => {
		const featureAlreadyExists = !! registry
			.select( STORE_NAME )
			.getRegisteredFeature( id );
		if ( featureAlreadyExists ) {
			return;
		}
		const feature = await registry
			.resolveSelect( coreStore )
			.getEntityRecord( ENTITY_KIND, ENTITY_NAME, id );
		dispatch( receiveFeature( feature ) );
	};
}
