/**
 * Internal dependencies
 */
import { navigate } from './navigation';
import {
	insertParagraphBlock,
	insertHeadingBlock,
	insertQuoteBlock,
	insertListBlock,
} from './blocks';
import { setTitle, previewPost } from './editor';

/**
 * External dependencies
 */
import { registerFeature } from '@wp-feature-api/client';

export const coreFeatures = [
	// Navigation
	navigate,
	// Block Insertion
	insertParagraphBlock,
	insertHeadingBlock,
	insertQuoteBlock,
	insertListBlock,
	// Editor Actions
	setTitle,
	previewPost,
];

/**
 * Registers all core features with the feature registry.
 */
export function registerCoreFeatures() {
	coreFeatures.filter( ( feature ) => !! feature ).forEach( registerFeature );
}
