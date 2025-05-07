/**
 * Core message types for the AI agent, based on common LLM patterns.
 */

export type Role = 'system' | 'user' | 'assistant' | 'tool';

export interface ToolCallFunction {
	name: string;
	arguments: string;
}

export interface ToolCall {
	id: string;
	type: 'function';
	function: ToolCallFunction;
}

export interface Message {
	role: Role;
	content: string | null;
	name?: string;
	tool_call_id?: string;
	tool_calls?: ToolCall[];
}

export interface Tool {
	name: string;
	displayName: string;
	description: string;
	parameters: Record< string, any >;
	execute: ( args: Record< string, unknown > ) => Promise< ToolResult >;
}

export interface ToolResult {
	result: unknown;
	error?: string;
}
