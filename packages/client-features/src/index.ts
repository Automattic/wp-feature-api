/**
 * Internal dependencies
 */
import { navigate } from './navigation';
import { insertParagraphBlock, insertHeadingBlock } from './blocks';

/**
 * External dependencies
 */
import { registerFeature } from '@wp-feature-api/client';

/**
 * Array of all core features
 */
export const coreFeatures = [
	navigate,
	insertParagraphBlock,
	insertHeadingBlock,
];

/**
 * Registers all core features with the feature registry.
 */
export function registerCoreFeatures() {
	coreFeatures.forEach( ( feature ) => {
		if ( feature ) {
			registerFeature( feature );
		}
	} );
}
