/**
 * Internal dependencies
 */
import type { Message } from './context/conversation-provider';

/**
 * Type for a registered feature tool
 */
export type FeatureTool = {
	id: string;
	description: string;
	input_schema: Record< string, unknown >;
	output_schema?: Record< string, unknown >;
	type: string;
	location: string;
};

/**
 * Type for a client action that needs to be executed
 */
export type ClientAction = {
	type: 'execute_tool';
	toolId: string;
	args: Record< string, unknown >;
	toolCallId: string;
};

/**
 * Type for the API response from the chat endpoint
 */
export type ApiResponse = {
	messages: Message[] | { messages: Message[] };
	client_action?: ClientAction;
	message_history?: Message[];
};

/**
 * Type for the tool result payload sent back to the server
 */
export type ToolResultPayload = {
	tool_result: {
		tool_call_id: string;
		content: string;
	};
	message_history: Message[];
	available_tools: FeatureTool[];
};
