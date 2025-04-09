/**
 * WordPress dependencies
 */
import { useCallback } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { select } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { useClientFeature } from '../../../../src/client/hooks/use-client-feature';
import type { Message, ClientAction } from '../context/conversation-provider';
import { store as featureStore } from '../../../../src/client/store';

/**
 * Custom hook to handle client actions from the server
 *
 * @param addMessage   Function to add a message to the conversation
 * @param setIsLoading Function to set loading state
 * @return Object with functions to handle client actions
 */
export const useClientActionHandler = (
	addMessage: ( message: Message ) => void,
	setIsLoading: ( isLoading: boolean ) => void
) => {
	const { executeClientFeature } = useClientFeature();

	const sendToolResultToServer = useCallback(
		async (
			featureId: string,
			toolCallId: string,
			result: unknown,
			history: Message[]
		) => {
			setIsLoading( true );
			try {
				const toolResultPayload = {
					tool_result: {
						tool_call_id: toolCallId,
						content: JSON.stringify( result ),
					},
					message_history: history,
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
			} catch ( error ) {
				addMessage( {
					role: 'assistant',
					content: `System Error: Failed to get final response for ${ featureId } from server.`,
					tool_calls: [],
				} );
			} finally {
				setIsLoading( false );
			}
		},
		[ addMessage, setIsLoading ]
	);

	const handleClientAction = useCallback(
		async ( action: ClientAction, historyToUse: Message[] | null ) => {
			if (
				action?.type !== 'execute_feature' ||
				! action.id ||
				! action.tool_call_id
			) {
				return;
			}

			const { id: featureId, args, tool_call_id: toolCallId } = action;

			const feature =
				select( featureStore ).getRegisteredFeature( featureId );

			// Determine if a result should be sent back based on output_schema,
			// which might be optional for some distructive actions like navigation.
			const expectsResult =
				!! feature?.output_schema &&
				Object.keys( feature.output_schema ).length > 0;

			try {
				const executionResult = await executeClientFeature(
					featureId,
					args
				);

				if ( expectsResult ) {
					if ( ! historyToUse ) {
						addMessage( {
							role: 'assistant',
							content: `System Error: Could not send result for ${ featureId } back to server due to missing history.`,
							tool_calls: [],
						} );
					} else {
						sendToolResultToServer(
							featureId,
							toolCallId,
							executionResult,
							historyToUse
						);
					}
				} else {
					setIsLoading( false );
				}
			} catch ( error ) {
				// Error is logged within useFeatureExecutor, but we still need to stop loading
				setIsLoading( false );
				// Optionally add a user-facing error message here too
				addMessage( {
					role: 'assistant',
					content: `System Error: Failed to execute client feature '${ featureId }'.`,
					tool_calls: [],
				} );
			}
		},
		[
			executeClientFeature,
			sendToolResultToServer,
			addMessage,
			setIsLoading,
		]
	);

	return {
		handleClientAction,
	};
};
