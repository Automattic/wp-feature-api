/**
 * WordPress dependencies
 */
import { store as coreStore } from '@wordpress/core-data';

/**
 * Internal dependencies
 */
import { ENTITY_KIND, ENTITY_NAME } from './constants';
import { receiveFeatures, receiveFeature } from './actions';
import { store } from './index';

export function getRegisteredFeatures() {
	return async ( { dispatch, registry } ) => {
		const features = await registry
			.resolveSelect( coreStore )
			.getEntityRecords( ENTITY_KIND, ENTITY_NAME );
		dispatch( receiveFeatures( features ) );
	};
}

export function getRegisteredFeature( id: string ) {
	return async ( { dispatch, registry } ) => {
		const featureAlreadyExists = !! registry
			.select( store )
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
