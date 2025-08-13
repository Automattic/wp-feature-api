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
import { getConfig, getEntityName } from '../config';

export function getRegisteredFeatures() {
	return async ( { dispatch, registry } ) => {
		const config = getConfig();
		const entityName = getEntityName();

		// Start with page 1
		let page = 1;
		let allFeatures = [];
		let hasMore = true;

		// Keep fetching pages until we have all features/abilities
		while ( hasMore ) {
			const features = await registry
				.resolveSelect( coreStore )
				.getEntityRecords( ENTITY_KIND, entityName, {
					page,
					per_page: 100,
				} );

			if ( ! features || features.length === 0 ) {
				hasMore = false;
			} else {
				// If fetching from abilities, convert them to feature format
				const processedFeatures =
					config.backend === 'abilities'
						? features.map( ( ability ) => ( {
								...ability,
								// Ensure abilities have the 'ability/' prefix in their ID
								id: ability.id.startsWith( 'ability/' )
									? ability.id
									: `ability/${ ability.id }`,
								name: ability.label || ability.name,
								location: ability.meta?.location || 'server',
						  } ) )
						: features;

				allFeatures = [ ...allFeatures, ...processedFeatures ];
				page++;
			}
		}

		dispatch( receiveFeatures( allFeatures ) );
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
