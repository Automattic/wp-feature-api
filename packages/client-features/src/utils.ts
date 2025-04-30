/**
 * Helper function to check if we're in the block editor
 */
export const isInEditor = (): boolean => {
	try {
		return !! document.querySelector( '.block-editor' );
	} catch ( error ) {
		return false;
	}
};
