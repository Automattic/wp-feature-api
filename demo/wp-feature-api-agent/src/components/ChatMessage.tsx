/**
 * WordPress dependencies
 */
import { Spinner } from '@wordpress/components';
import { useContext } from '@wordpress/element';

/**
 * External dependencies
 */
import Markdown from 'react-markdown';

/**
 * Internal dependencies
 */
import type { Message } from '../types/messages';
import { ConversationContext } from '../context/ConversationProvider';

interface MessageProps {
	text: string;
}

/*
 * Renders user message with markdown support
 */
export const UserMessage = ( { text }: MessageProps ) => (
	<div className="demo-chat-message demo-chat-message-user">
		<Markdown>{ text }</Markdown>
	</div>
);

/**
 * Helper to attempt parsing JSON and formatting, falling back to raw string
 * @param content
 */
function formatToolContent( content: string | null ): string {
	if ( content === null ) {
		return 'null';
	}

	try {
		const parsed = JSON.parse( content );
		return JSON.stringify( parsed, null, 2 );
	} catch ( e ) {
		return content;
	}
}

/*
 * Assistant message component that renders AI responses with markdown support
 * and handles tool calls
 */
export const AssistantMessage = ( { message }: { message: Message } ) => {
	const { content, role, name, tool_calls: toolCalls } = message;
	const context = useContext( ConversationContext );
	const toolNameMap = context?.toolNameMap || {};

	// Tool Message Rendering
	if ( role === 'tool' ) {
		const displayName = name ? toolNameMap[ name ] || name : 'unknown tool';
		const formattedContent = formatToolContent( content );
		const hasError = content?.toLowerCase().includes( 'error:' );

		return (
			<div className="demo-chat-message demo-chat-message-tool">
				<details open={ hasError }>
					<summary>Tool Result: { displayName }</summary>
					<pre>
						<code>{ formattedContent }</code>
					</pre>
				</details>
			</div>
		);
	}

	let displayContent = content;

	if (
		( content === null || content.trim() === '' ) &&
		toolCalls &&
		toolCalls.length > 0
	) {
		const firstToolName = toolCalls[ 0 ].function?.name
			? toolNameMap[ toolCalls[ 0 ].function.name ] ||
			  toolCalls[ 0 ].function.name
			: 'unknown tool';
		displayContent = `*Using tool: ${ firstToolName }...*`;
	}

	if ( displayContent === null || displayContent.trim() === '' ) {
		return null;
	}

	return (
		<div className="demo-chat-message demo-chat-message-assistant">
			<Markdown>{ displayContent }</Markdown>
		</div>
	);
};

/**
 * Pending message component that shows a loading indicator
 */
export const PendingAssistantMessage = () => (
	<div className="demo-chat-message demo-chat-message-assistant demo-chat-message-pending">
		<Spinner />
	</div>
);
