/**
 * WordPress dependencies
 */
import { useCallback } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

/**
 * External dependencies
 */
import { getRegisteredFeatures, executeFeature } from '@wp-feature-api/client';

/**
 * Internal dependencies
 */
import type { Message } from '../context/conversation-provider';

/**
 * Hook to handle sending messages to the server
 *
 * @param addMessage   Function to add a message to the conversation
 * @param setIsLoading Function to set loading state
 * @return Function to send a message
 */
export const useMessageHandler = (
	addMessage: ( message: Message ) => void,
	setIsLoading: ( isLoading: boolean ) => void
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

				// Map all features to a tools format for the backend
				const availableTools = registeredFeatures.map(
					( {
						id,
						description,
						input_schema: inputSchema,
						output_schema: outputSchema,
						type,
						location,
					} ) => ( {
						id,
						description,
						input_schema: inputSchema || {},
						output_schema: outputSchema || {},
						type,
						location,
					} )
				);

				type ApiResponse = {
					messages: Message[];
					client_action?: {
						type: 'execute_tool';
						toolId: string;
						args: any;
						toolCallId: string;
					};
					message_history?: Message[];
				};

				const response = await apiFetch< ApiResponse >( {
					path: '/wp/v2/demo-chat',
					method: 'POST',
					data: {
						message: userMessageContent,
						available_tools: availableTools,
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
						const { toolId, args, toolCallId } =
							response.client_action;
						try {
							const result = await executeFeature( toolId, args );

							// If we have a tool result and message history, send it back
							if (
								result !== undefined &&
								response.message_history
							) {
								const toolResultPayload = {
									tool_result: {
										tool_call_id: toolCallId,
										content: JSON.stringify( result ),
									},
									message_history: response.message_history,
								};

								const finalResponse = await apiFetch< {
									messages: Message[];
								} >( {
									path: '/wp/v2/demo-chat',
									method: 'POST',
									data: toolResultPayload,
								} );

								if ( finalResponse.messages ) {
									finalResponse.messages.forEach( ( msg ) =>
										addMessage( msg )
									);
								}
							}
						} catch ( error ) {
							addMessage( {
								role: 'assistant',
								content: `Error executing tool ${ toolId }: ${ error }`,
								tool_calls: [],
							} );
						}
					}
				}
			} catch ( error ) {
				// eslint-disable-next-line no-console
				console.error( error );
			} finally {
				setIsLoading( false );
			}
		},
		[ addMessage, setIsLoading ]
	);

	return {
		sendMessage,
	};
};
