/**
 * WordPress dependencies
 */
import { combineReducers } from '@wordpress/data';

/**
 * Internal dependencies
 */
import type { Feature } from '../types';
import {
	REGISTER_FEATURE,
	RECEIVE_FEATURE,
	UNREGISTER_FEATURE,
	RECEIVE_FEATURES,
	REGISTER_FEATURE_CALLBACK,
	SET_FEATURE_INPUT_IN_PROGRESS,
} from './constants';

interface FeatureAction {
	type: string;
	feature?: Feature;
	features?: Feature[];
	id?: string;
	callback?: () => unknown | Promise< unknown >;
}

const DEFAULT_STATE: Record< string, Feature > = {};

function featuresById(
	state: Record< string, Feature > = DEFAULT_STATE,
	action: FeatureAction
): Record< string, Feature > {
	switch ( action.type ) {
		case REGISTER_FEATURE:
		case RECEIVE_FEATURE:
			return { ...state, [ action.feature.id ]: action.feature };
		case UNREGISTER_FEATURE: {
			const newState = { ...state };
			delete newState[ action.feature.id ];
			return newState;
		}
		case RECEIVE_FEATURES: {
			const newState = { ...state };
			if ( Array.isArray( action.features ) ) {
				action.features.forEach( ( feature ) => {
					newState[ feature.id ] = feature;
				} );
			}
			return newState;
		}
		case REGISTER_FEATURE_CALLBACK: {
			const feature = state[ action.id ];
			if ( ! feature ) {
				return state;
			}
			return {
				...state,
				[ action.id ]: {
					...feature,
					callback: action.callback,
				},
			};
		}
		default:
			return state;
	}
}

function featureInputInProgressId(
	state: string | null = null,
	action: FeatureAction
): string | null {
	switch ( action.type ) {
		case SET_FEATURE_INPUT_IN_PROGRESS:
			return action.id;
		default:
			return state;
	}
}

export default combineReducers( {
	featuresById,
	featureInputInProgressId,
} );
