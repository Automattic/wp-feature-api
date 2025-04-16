/**
 * WordPress dependencies
 */
import { useCallback } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

/**
 * External dependencies
 */
import { getRegisteredFeatures } from '@wp-feature-api/client';

/**
 * Internal dependencies
 */
import type { Message, ClientAction } from '../context/conversation-provider';

/**
 * Hook to handle sending messages to the server
 *
 * @param addMessage         Function to add a message to the conversation
 * @param setIsLoading       Function to set loading state
 * @param handleClientAction Function to handle client actions
 * @return Function to send a message
 */
export const useMessageHandler = (
	addMessage: ( message: Message ) => void,
	setIsLoading: ( isLoading: boolean ) => void,
	handleClientAction: (
		action: ClientAction,
		history: Message[] | null
	) => Promise< void >
) => {
	const sendMessage = useCallback(
		async ( userMessageContent: string ) => {
			if ( ! userMessageContent.trim() ) {
				return;
			}

			setIsLoading( true );

			addMessage( {
				role: 'user',
				content: userMessageContent,
				tool_calls: [],
			} );

			try {
				const registeredFeatures =
					( await getRegisteredFeatures() ) || [];

				const clientFeatures = registeredFeatures
					.filter( ( feature ) => feature?.location === 'client' )
					.map(
						( {
							id,
							description,
							input_schema: inputSchema,
						} ) => ( {
							id,
							description,
							input_schema: inputSchema || {},
						} )
					);

				type ApiResponse = {
					messages: Message[];
					client_action?: ClientAction;
					message_history?: Message[];
				};

				const response = await apiFetch< ApiResponse >( {
					path: '/wp/v2/demo-chat',
					method: 'POST',
					data: {
						message: userMessageContent,
						client_features: clientFeatures,
					},
				} );

				if ( response.messages ) {
					const serverMessages = response.messages || [];
					serverMessages
						.filter(
							( msg ) =>
								! (
									msg.role === 'user' &&
									msg.content === userMessageContent
								)
						)
						.forEach( ( msg ) => addMessage( msg ) );

					if ( response.client_action ) {
						const historyFromServer =
							response.message_history || null;
						handleClientAction(
							response.client_action,
							historyFromServer
						);
					}
				}
			} catch ( error ) {
				// eslint-disable-next-line no-console
				console.error( error );
			} finally {
				setIsLoading( false );
			}
		},
		[ addMessage, setIsLoading, handleClientAction ]
	);

	return {
		sendMessage,
	};
};
