/**
 * WordPress dependencies
 */
import {
	createReduxStore,
	createRegistrySelector,
	register,
	dispatch,
} from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';
import { __ } from '@wordpress/i18n';

const DEFAULT_STATE = {};
const STORE_NAME = 'features-api';
const ENTITY_KIND = 'root';
const ENTITY_NAME = 'features';

const store = createReduxStore( STORE_NAME, {
	reducer( state = DEFAULT_STATE ) {
		return state;
	},
	actions: {
		registerFeatureCallback:
			( id, callback ) =>
			async ( { registry } ) => {
				if (
					await registry
						.resolveSelect( store )
						.getRegisteredFeature( id )
				) {
					registry
						.dispatch( coreStore )
						.editEntityRecord( ENTITY_KIND, ENTITY_NAME, id, {
							callback,
						} );
				}
			},
	},
	selectors: {
		getRegisteredFeatures: createRegistrySelector( ( select ) => () => {
			return select( coreStore ).getEntityRecords(
				ENTITY_KIND,
				ENTITY_NAME
			);
		} ),
		getRegisteredFeature: createRegistrySelector(
			( select ) => ( _, id ) => {
				return select( coreStore ).getEditedEntityRecord(
					ENTITY_KIND,
					ENTITY_NAME,
					id
				);
			}
		),
		getRegisteredFeatureCallback: createRegistrySelector(
			( select ) => ( _, id ) => {
				return select( store ).getRegisteredFeature( id )?.callback;
			}
		),
	},
	resolvers: {
		getRegisteredFeature:
			( id ) =>
			async ( { registry } ) => {
				return registry
					.resolveSelect( coreStore )
					.getEntityRecord( ENTITY_KIND, ENTITY_NAME, id );
			},
	},
} );

register( store );
dispatch( coreStore )?.addEntities( [
	{
		name: ENTITY_NAME,
		kind: ENTITY_KIND,
		baseURL: '/wp/v2/features',
		baseURLParams: { context: 'edit' },
		plural: 'features',
		label: __( 'Features' ),
		transientEdits: {
			callback: true,
		},
	},
] );
