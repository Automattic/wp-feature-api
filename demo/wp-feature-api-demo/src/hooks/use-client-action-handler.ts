/**
 * WordPress dependencies
 */
import { useCallback } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import type { Message, ClientAction } from '../context/conversation-provider';

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
	const executeClientFeature = useCallback(
		async ( featureId: string, args: any ): Promise< unknown > => {
			// Access store via global wp.data
			// @todo We should probably export our feature store as a proper package and use that instead,
			// this is to avoid double bundling by calling things from the root src/client/... files directly
			const selector = ( window.wp as any )?.data?.select(
				'feature-api'
			);
			const callback =
				selector?.getRegisteredFeatureCallback?.( featureId );

			if ( typeof callback !== 'function' ) {
				// eslint-disable-next-line no-console
				console.error(
					`No callback registered for feature: ${ featureId }`
				);
				throw new Error(
					`No callback registered for feature: ${ featureId }`
				);
			}

			try {
				return await callback( args );
			} catch ( error ) {
				// eslint-disable-next-line no-console
				console.error(
					`Error executing feature ${ featureId }:`,
					error
				);
				throw error;
			}
		},
		[]
	);

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

			const selector = ( window.wp as any )?.data?.select(
				'feature-api'
			);
			const feature: any = selector?.getRegisteredFeature?.( featureId );

			// Determine if a result should be sent back based on output_schema
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
