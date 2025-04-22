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
import type {
	ApiResponse,
	ClientAction,
	FeatureTool,
	ToolResultPayload,
} from '../types';

/**
 * Hook to handle sending messages to the server
 *
 * @param messages     Current array of messages in the conversation
 * @param addMessage   Function to add a message to the conversation
 * @param setIsLoading Function to set loading state
 * @return Function to send a message
 */
export const useMessageHandler = (
	messages: Message[],
	addMessage: ( message: Message ) => void,
	setIsLoading: ( isLoading: boolean ) => void
) => {
	// Function to handle a single client action (tool execution) and potential chaining
	const handleClientAction = useCallback(
		async (
			action: ClientAction,
			history: Message[],
			availableTools: FeatureTool[]
		): Promise< void > => {
			const { toolId, args, toolCallId } = action;
			try {
				// eslint-disable-next-line no-console
				console.log( 'Executing tool:', toolId, 'with args:', args );
				// Execute the requested tool
				const result = await executeFeature( toolId, args );

				// Tool execution succeeded (didn't throw). Now, send the result back.
				// The agent needs confirmation even if the result payload is undefined.
				if ( history ) {
					const toolResultPayload: ToolResultPayload = {
						tool_result: {
							tool_call_id: toolCallId,
							// Send null if result is undefined, otherwise stringify the result
							content: JSON.stringify( result ?? null ),
						},
						message_history: history,
						// Include available_tools again in case the agent needs them after tool result
						available_tools: availableTools,
					};

					// Send tool result and get the next response from the agent
					const nextResponse = await apiFetch< ApiResponse >( {
						path: '/wp/v2/demo-chat',
						method: 'POST',
						data: toolResultPayload,
					} );

					// Add any new messages from the response
					if ( nextResponse.messages ) {
						let messagesToAdd: Message[] = [];
						if ( Array.isArray( nextResponse.messages ) ) {
							messagesToAdd = nextResponse.messages;
						} else if (
							typeof nextResponse.messages === 'object' &&
							nextResponse.messages !== null &&
							Array.isArray(
								(
									nextResponse.messages as {
										messages: Message[];
									}
								 ).messages
							)
						) {
							messagesToAdd = (
								nextResponse.messages as { messages: Message[] }
							 ).messages;
						}

						if ( messagesToAdd.length > 0 ) {
							messagesToAdd.forEach( ( msg: Message ) =>
								addMessage( msg )
							);
						}
					}

					// Check if there's another client action (tool chaining)
					if (
						nextResponse.client_action &&
						nextResponse.message_history
					) {
						// Recursively handle the next action
						await handleClientAction(
							nextResponse.client_action,
							nextResponse.message_history,
							availableTools
						);
					}
				} else {
					// Should not happen if client_action was received, but handle defensively
					// eslint-disable-next-line no-console
					console.error(
						'Missing message history when trying to send tool result.'
					);
					addMessage( {
						role: 'assistant',
						content: 'An internal error occurred: missing history.',
						tool_calls: [],
					} );
				}
			} catch ( error ) {
				// Handle errors during tool execution or the subsequent API call
				addMessage( {
					role: 'assistant',
					content: `Error during tool execution or processing for ${ toolId }: ${
						error instanceof Error ? error.message : String( error )
					}`,
					tool_calls: [],
				} );
				// Stop the chain on error
				throw error; // Re-throw to be caught by the main try/catch
			}
		},
		[ addMessage ] // Dependencies for handleClientAction
	);

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
				const availableTools: FeatureTool[] = registeredFeatures.map(
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

				// Initial request
				const initialResponse = await apiFetch< ApiResponse >( {
					path: '/wp/v2/demo-chat',
					method: 'POST',
					data: {
						message: userMessageContent,
						available_tools: availableTools,
						message_history: messages, // Send the current message history
					},
				} );

				// Add initial messages
				if ( initialResponse.messages ) {
					let messagesToAdd: Message[] = [];
					if ( Array.isArray( initialResponse.messages ) ) {
						// Case 1: messages is directly an array
						messagesToAdd = initialResponse.messages;
					} else if (
						typeof initialResponse.messages === 'object' &&
						initialResponse.messages !== null &&
						Array.isArray(
							(
								initialResponse.messages as {
									messages: Message[];
								}
							 ).messages
						)
					) {
						// Case 2: messages is nested { messages: [...] }
						messagesToAdd = (
							initialResponse.messages as { messages: Message[] }
						 ).messages;
					}

					if ( messagesToAdd.length > 0 ) {
						messagesToAdd.forEach( ( msg: Message ) =>
							addMessage( msg )
						);
					}
				}

				// Handle the first client action if it exists
				if (
					initialResponse.client_action &&
					initialResponse.message_history
				) {
					await handleClientAction(
						initialResponse.client_action,
						initialResponse.message_history,
						availableTools
					);
				}
			} catch ( error ) {
				// eslint-disable-next-line no-console
				console.error( 'Chat request failed:', error );
				// Optionally add an error message to the chat UI
				// addMessage({ role: 'assistant', content: 'An error occurred. Please try again.' });
			} finally {
				setIsLoading( false ); // Ensure loading is turned off after all actions/errors
			}
		},
		[ messages, addMessage, setIsLoading, handleClientAction ] // Dependencies for sendMessage
	);

	return {
		sendMessage,
	};
};
