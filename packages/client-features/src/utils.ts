/**
 * Helper function to check if we're in the post editor
 */
export const isInPostEditor = (): boolean => {
	try {
		return !! document.querySelector( '.block-editor' );
	} catch ( error ) {
		return false;
	}
};

/**
 * Helper function to check if we're in the site editor
 */
export const isInSiteEditor = (): boolean => {
	try {
		return !! document.querySelector( '#site-editor' );
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
