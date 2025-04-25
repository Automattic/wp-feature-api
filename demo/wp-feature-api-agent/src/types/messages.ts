/**
 * Core message types for the AI agent, based on common LLM patterns.
 */

export type Role = 'system' | 'user' | 'assistant' | 'tool';

// Represents a single message in the conversation history.
export interface Message {
	role: Role;
	content: string | null;
	name?: string;
	toolCallId?: string;
}
