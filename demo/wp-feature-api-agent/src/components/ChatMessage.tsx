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

export const UserMessage = ( { text }: MessageProps ) => (
	<div className="demo-chat-message demo-chat-message-user">
		<Markdown>{ text }</Markdown>
	</div>
);

export const AssistantMessage = ( { message }: { message: Message } ) => {
	const { content, role, name } = message;

	// Handle potential null content (e.g., initial tool call request)
	const displayContent =
		content ??
		( role === 'tool' ? `Tool Result (${ name || 'unknown' }):` : '' );

	// Basic rendering for tool messages, to be expanded later
	if ( role === 'tool' ) {
		return (
			<div className="demo-chat-message demo-chat-message-tool">
				<details>
					<summary>Tool Result: { name || 'Result' }</summary>
					<pre>{ displayContent }</pre>
				</details>
			</div>
		);
	}

	return (
		<div className="demo-chat-message demo-chat-message-assistant">
			<Markdown>{ displayContent }</Markdown>
		</div>
	);
};

export const PendingAssistantMessage = () => (
	<div className="demo-chat-message demo-chat-message-assistant demo-chat-message-pending">
		<Spinner />
	</div>
);
