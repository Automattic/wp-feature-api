/**
 * WordPress dependencies
 */
import { Spinner } from '@wordpress/components';

/**
 * External dependencies
 */
import Markdown from 'react-markdown';

/**
 * Internal dependencies
 */
import type { Message } from '../types/messages';

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
 * Helper to decode hex tool names back to original feature IDs for display
 * @param hex
 */
function hexToString( hex: string ): string {
	if ( hex.length % 2 !== 0 ) {
		return hex;
	}

	try {
		return (
			hex
				.match( /.{1,2}/g )
				?.map( ( byte ) => String.fromCharCode( parseInt( byte, 16 ) ) )
				.join( '' ) || hex
		);
	} catch ( error ) {
		return hex;
	}
}

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

	// Tool Message Rendering
	if ( role === 'tool' ) {
		const decodedName = name ? hexToString( name ) : 'unknown tool';
		const formattedContent = formatToolContent( content );
		const hasError = content?.toLowerCase().includes( 'error:' );

		return (
			<div className="demo-chat-message demo-chat-message-tool">
				<details open={ hasError }>
					<summary>Tool Result: { decodedName }</summary>
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
			? hexToString( toolCalls[ 0 ].function.name )
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
