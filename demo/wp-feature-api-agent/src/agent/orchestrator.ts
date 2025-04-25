/**
 * Internal dependencies
 */
import type { Message } from '../types/messages';

/**
 * Defines the shape of the function responsible for making API calls.
 * This allows injecting different clients (e.g., wp.apiFetch, standard fetch).
 */
export type ApiClient = (
	endpoint: string,
	data: {
		messages: Message[];
		model: string;
	}
) => Promise< any >; // Promise<any> for now, refine based on actual proxy response

/**
 * Dependencies required by the agent orchestrator.
 */
export interface AgentDependencies {
	apiClient: ApiClient;
}

/**
 * The interface for the created agent.
 */
export interface Agent {
	/**
	 * Processes a user query, interacts with the LLM via the ApiClient,
	 * and yields messages representing the conversation flow.
	 * @param query           The user's input string.
	 * @param currentMessages The existing conversation history.
	 * @return An async generator yielding Message objects.
	 */
	processQuery: (
		query: string,
		currentMessages: Message[],
		modelId: string // Add modelId parameter
	) => AsyncGenerator< Message >;
}

/**
 * Factory function to create an AI agent instance.
 * @param deps Dependencies like the API client.
 * @return An Agent instance.
 */
export const createAgent = ( deps: AgentDependencies ): Agent => {
	const { apiClient } = deps;

	const processQuery = async function* (
		query: string,
		currentMessages: Message[],
		modelId: string
	): AsyncGenerator< Message > {
		const userMessage: Message = { role: 'user', content: query };

		// Combine existing history with the new user message
		const messagesForApi = [ ...currentMessages, userMessage ];

		try {
			const response = await apiClient(
				'/wp/v2/ai-api-proxy/v1/chat/completions',
				{
					messages: messagesForApi,
					model: modelId,
				}
			);

			let assistantMessage: Message | null = null;

			if (
				response?.choices &&
				Array.isArray( response.choices ) &&
				response.choices.length > 0
			) {
				const choice = response.choices[ 0 ];

				if ( choice.message && choice.message.role === 'assistant' ) {
					assistantMessage = choice.message;
				} else if ( typeof choice.content === 'string' ) {
					assistantMessage = {
						role: 'assistant',
						content: choice.content,
					};
				}
			} else {
				assistantMessage = {
					role: 'assistant',
					content: `Error: Received unexpected data format from API.`,
				};
			}

			if ( assistantMessage ) {
				yield assistantMessage;
			}
		} catch ( error ) {
			yield {
				role: 'assistant',
				content: `Sorry, I encountered an error: ${
					error instanceof Error ? error.message : 'Unknown error'
				}`,
			};
		}
	};

	return { processQuery };
};
