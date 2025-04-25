/**
 * WordPress dependencies
 */
import { useContext } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { ConversationContext } from '../context/ConversationProvider';

/**
 * Access the conversation context.
 *
 * @return The conversation context value.
 */
export const useConversation = () => {
	const context = useContext( ConversationContext );

	if ( context === null ) {
		throw new Error(
			'useConversation must be used within a ConversationProvider'
		);
	}

	return context;
};
