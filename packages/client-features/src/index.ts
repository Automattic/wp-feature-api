/**
 * Internal dependencies
 */
import { navigate } from './navigation';
import { insertParagraphBlock, insertHeadingBlock } from './blocks';

/**
 * External dependencies
 */
import { registerFeature } from '@wp-feature-api/client';

export const coreFeatures = [
	navigate,
	insertParagraphBlock,
	insertHeadingBlock,
];

/**
 * Registers all core features with the feature registry.
 */
export function registerCoreFeatures() {
	coreFeatures.filter( ( feature ) => !! feature ).forEach( registerFeature );
}
