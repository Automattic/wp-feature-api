/**
 * Internal dependencies
 */
import type { Message } from '../types/messages';
import type { ToolExecutor } from './tool-executor';

/**
 * Defines the shape of the function responsible for making API calls.
 * This allows injecting different clients (e.g., wp.apiFetch, standard fetch).
 */
export type ApiClient = (
	endpoint: string,
	data: {
		messages: Message[];
		model: string;
		tools?: any[];
		tool_choice?: string;
	}
) => Promise< any >;

/**
 * Dependencies required by the agent orchestrator.
 */
export interface AgentDependencies {
	apiClient: ApiClient;
	toolExecutor?: ToolExecutor;
}

/**
 * The interface for the created agent.
 */
export interface Agent {
	/**
	 * Processes a user query, interacts with the LLM via the ApiClient,
	 * potentially uses tools, and yields messages representing the conversation flow.
	 * @param query           The user's input string.
	 * @param currentMessages The existing conversation history.
	 * @param modelId         The ID of the model to use.
	 * @return An async generator yielding Message objects.
	 */
	processQuery: (
		query: string,
		currentMessages: Message[],
		modelId: string
	) => AsyncGenerator< Message >;
}

/**
 * Factory function to create an AI agent instance.
 * @param deps Dependencies like the API client and optional tool executor.
 * @return An Agent instance.
 */
export const createAgent = ( deps: AgentDependencies ): Agent => {
	const { apiClient, toolExecutor } = deps; // Destructure toolExecutor

	const processQuery = async function* (
		query: string,
		currentMessages: Message[],
		modelId: string
	): AsyncGenerator< Message > {
		const userMessage: Message = { role: 'user', content: query };
		yield userMessage;

		const initialHistory: Message[] = [ ...currentMessages, userMessage ];

		const currentTurnHistory: Message[] = [ ...initialHistory ];

		let loopCount = 0;
		const MAX_LOOPS = 10;

		while ( loopCount < MAX_LOOPS ) {
			loopCount++;
			let assistantResponseContent: string | null = null;
			let toolCallsFromResponse: any[] | null = null;

			try {
				const apiPayload: any = {
					messages: currentTurnHistory,
					model: modelId,
				};

				if ( toolExecutor && toolExecutor.listTools().length > 0 ) {
					apiPayload.tools = toolExecutor
						.listTools()
						.map( ( tool ) => ( {
							type: 'function',
							function: {
								name: tool.name,
								description: tool.description,
								parameters: tool.parameters,
							},
						} ) );
					apiPayload.tool_choice = 'auto';
				}

				// eslint-disable-next-line no-console
				console.log(
					'Calling API Proxy with history:',
					JSON.stringify( currentTurnHistory, null, 2 )
				);

				// eslint-disable-next-line no-console
				console.log(
					'Calling API Proxy with full payload:',
					apiPayload
				);

				const response = await apiClient(
					'/wp/v2/ai-api-proxy/v1/chat/completions',
					apiPayload
				);

				// eslint-disable-next-line no-console
				console.log( 'Received response from API Proxy:', response );

				const messageFromAPI = response?.choices?.[ 0 ]?.message;

				if ( ! messageFromAPI ) {
					throw new Error(
						'Invalid response structure from API proxy.'
					);
				}

				assistantResponseContent = messageFromAPI.content || null;
				toolCallsFromResponse = messageFromAPI.tool_calls || null;

				const assistantTurnMessage: Message = {
					role: 'assistant',
					content:
						assistantResponseContent ??
						( toolCallsFromResponse ? '' : null ),
					tool_calls: toolCallsFromResponse || undefined,
				};

				if (
					assistantTurnMessage.content !== null ||
					assistantTurnMessage.tool_calls?.length
				) {
					currentTurnHistory.push( assistantTurnMessage );
					yield assistantTurnMessage;
				}

				if (
					! toolCallsFromResponse ||
					toolCallsFromResponse.length === 0
				) {
					break; // No tool calls, conversation segment is complete, exit the loop
				}

				if ( ! toolExecutor ) {
					const toolErrorMsg =
						'Error: Tool execution requested but not supported.';
					yield { role: 'assistant', content: toolErrorMsg };
					// eslint-disable-next-line no-console
					console.error(
						'Received tool calls but no ToolExecutor is configured.'
					);

					currentTurnHistory.push( {
						role: 'assistant',
						content: toolErrorMsg,
					} );
					break;
				}

				const toolResultsPromises = toolCallsFromResponse.map(
					async ( toolCall: any ) => {
						const toolCallId = toolCall.id;
						const toolName = toolCall.function?.name;
						let toolArgs = {};
						let toolResultMessage: Message | undefined;

						if ( ! toolCallId || ! toolName ) {
							// eslint-disable-next-line no-console
							console.error(
								'Invalid tool call structure from LLM:',
								toolCall
							);
							toolResultMessage = {
								role: 'tool',
								tool_call_id: toolCallId || 'invalid_call',
								name: toolName || 'unknown',
								content:
									'Error: Invalid tool call structure received.',
							};
						} else {
							try {
								toolArgs = JSON.parse(
									toolCall.function?.arguments || '{}'
								);
							} catch ( parseError ) {
								// eslint-disable-next-line no-console
								console.error(
									`Error parsing arguments for tool ${ toolName }:`,
									parseError
								);
								toolResultMessage = {
									role: 'tool',
									tool_call_id: toolCallId,
									name: toolName,
									content: `Error: Could not parse arguments for tool ${ toolName }.`,
								};
							}

							if ( toolResultMessage === undefined ) {
								// eslint-disable-next-line no-console
								console.log(
									`Executing tool: ${ toolName } with args:`,
									toolArgs
								);
								const { result, error } =
									await toolExecutor.executeTool(
										toolName,
										toolArgs
									);
								// eslint-disable-next-line no-console
								console.log( `Tool ${ toolName } result:`, {
									result,
									error,
								} );

								let content: string;

								if ( error ) {
									content = `Error: ${ error }`;
								} else {
									content = JSON.stringify( result );
								}

								toolResultMessage = {
									role: 'tool' as const,
									tool_call_id: toolCallId,
									name: toolName,
									content,
								};
							}
						}
						return toolResultMessage as Message;
					}
				);

				const toolResults = await Promise.all( toolResultsPromises );

				for ( const toolResultMsg of toolResults ) {
					if ( toolResultMsg ) {
						currentTurnHistory.push( toolResultMsg );
						yield toolResultMsg;
					}
				}
			} catch ( error ) {
				// eslint-disable-next-line no-console
				console.error( 'Error in agent processing loop:', error );
				const errorMsgContent = `Sorry, I encountered an error: ${
					error instanceof Error ? error.message : 'Unknown error'
				}`;
				yield {
					role: 'assistant',
					content: errorMsgContent,
				};
				break;
			}
		}

		if ( loopCount >= MAX_LOOPS ) {
			// eslint-disable-next-line no-console
			console.warn( 'Agent reached maximum loop count.' );
			const loopErrorMsg =
				'Sorry, I seem to be stuck in a loop processing your request. Please try rephrasing.';
			yield {
				role: 'assistant',
				content: loopErrorMsg,
			};
		}
	};

	return { processQuery };
};
