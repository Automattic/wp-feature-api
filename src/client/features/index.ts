/**
 * WordPress dependencies
 */
import { dispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { store as featureStore } from '../store';
import { navigate } from './navigation';
import { insertParagraphBlock, insertHeadingBlock } from './blocks';

/**
 * Registers the default client-side features with the feature store.
 */
export const registerDefaultWPFeatures = () => {
	const { registerFeature } = dispatch( featureStore );

	[ navigate, insertParagraphBlock, insertHeadingBlock ]
		.filter( ( feature ) => !! feature )
		.forEach( registerFeature );
};
