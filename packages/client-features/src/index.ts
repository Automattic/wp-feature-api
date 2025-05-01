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
import { setTitle, savePost, getEditorContent } from './editor';
import {
	searchPatterns,
	getPatternCategories,
	insertPattern,
	getPatternContent,
} from './patterns';

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
	savePost,
	getEditorContent,
	// Pattern Features
	searchPatterns,
	getPatternContent,
	getPatternCategories,
	insertPattern,
];

/**
 * Registers all core features with the feature registry.
 */
export function registerCoreFeatures() {
	coreFeatures.filter( ( feature ) => !! feature ).forEach( registerFeature );
}
