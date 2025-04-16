/**
 * Helper function to check if we're in the WordPress post editor
 */
export const isInPostEditor = (): boolean => {
	try {
		return !! document.querySelector( '.block-editor' );
	} catch ( error ) {
		return false;
	}
};
