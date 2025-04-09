/**
 * WordPress dependencies
 */
import {
	createReduxStore,
	register,
	dispatch,
	resolveSelect,
} from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import reducer from './reducer';
import * as actions from './actions';
import * as selectors from './selectors';
import * as resolvers from './resolvers';
import { ENTITY_KIND, ENTITY_NAME, STORE_NAME } from './constants';

export const store = createReduxStore( STORE_NAME, {
	reducer,
	actions,
	selectors,
	resolvers,
} );

register( store );

// get registered client features
resolveSelect( STORE_NAME ).getRegisteredFeatures();

// get registered server features
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
