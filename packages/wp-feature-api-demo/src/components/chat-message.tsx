/**
 * WordPress dependencies
 */
import { Spinner, Button } from '@wordpress/components';

/**
 * External dependencies
 */
import { useState } from '@wordpress/element';
import Markdown from 'react-markdown';

/**
 * Internal dependencies
 */
import type { Message } from '../context/conversation-provider';

interface MessageProps {
	text: string;
}

export const UserMessage = ( { text }: MessageProps ) => (
	<div className="demo-chat-message demo-chat-message-user">
		<Markdown>{ text }</Markdown>
	</div>
);

export const AssistantMessage = ( { message }: { message: Message } ) => {
	const { content, tool_calls: toolCalls } = message;

	if ( content === null && toolCalls.length > 0 ) {
		return <ToolCall text={ JSON.stringify( toolCalls ) } />;
	}

	return (
		<div className="demo-chat-message demo-chat-message-assistant">
			<Markdown>{ content }</Markdown>
		</div>
	);
};

export const ToolCall = ( { text: json }: MessageProps ) => {
	const data = JSON.parse( json );
	const [ isExpanded, setIsExpanded ] = useState( false );
	const args = JSON.parse( data[ 0 ].function.arguments );
	return (
		<div className="demo-chat-message demo-chat-message-tool">
			<Button
				variant="secondary"
				onClick={ () => setIsExpanded( ! isExpanded ) }
				className="demo-chat-tool-toggle"
			>
				Tool Call { isExpanded ? '▼' : '▶' }
			</Button>
			{ isExpanded && <pre>{ JSON.stringify( args, null, 2 ) }</pre> }
		</div>
	);
};

export const FeatureTool = ( { message }: { message: Message } ) => {
	const { content } = message;
	const data = content ? JSON.parse( content ) : null;
	const [ isExpanded, setIsExpanded ] = useState( false );

	return (
		<div className="demo-chat-message demo-chat-message-tool">
			<Button
				variant="secondary"
				onClick={ () => setIsExpanded( ! isExpanded ) }
				className="demo-chat-tool-toggle"
			>
				Feature Call Result { isExpanded ? '▼' : '▶' }
			</Button>
			{ isExpanded && <pre>{ JSON.stringify( data, null, 2 ) }</pre> }
		</div>
	);
};

export const PendingAssistantMessage = () => (
	<div className="demo-chat-message demo-chat-message-assistant demo-chat-message-pending">
		<Spinner />
	</div>
);
