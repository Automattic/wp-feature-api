/**
 * WordPress dependencies
 */
import { select } from '@wordpress/data';

/**
 * Helper function to check if we're in the post editor
 */
export const isInPostEditor = (): boolean => {
	try {
		const postType = select( 'core/editor' )?.getCurrentPostType();
		return !! postType && ! isInSiteEditor();
	} catch ( error ) {
		return false;
	}
};

/**
 * Helper function to check if we're in the site editor
 */
export const isInSiteEditor = (): boolean => {
	try {
		return !! select( 'core/edit-site' );
	} catch ( error ) {
		return false;
	}
};

/**
 * Helper function to check if we're in the editor
 */
export const isInEditor = (): boolean => {
	return isInPostEditor() || isInSiteEditor();
};
