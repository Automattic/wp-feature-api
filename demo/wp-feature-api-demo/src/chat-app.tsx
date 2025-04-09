/**
 * WordPress dependencies
 */
import { useState } from '@wordpress/element';
import { Button, Spinner } from '@wordpress/components';
import { arrowRight } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import {
	UserMessage,
	AssistantMessage,
	PendingAssistantMessage,
	FeatureTool,
} from './components/chat-message';
import {
	ConversationProvider,
	useConversation,
} from './context/conversation-provider';
import { useClientActionHandler } from './hooks/use-client-action-handler';
import { useMessageHandler } from './hooks/use-message-handler';

const ChatAppContent = () => {
	const { messages, addMessage, clearMessages } = useConversation();
	const [ inputValue, setInputValue ] = useState( '' );
	const [ isLoading, setIsLoading ] = useState( false );

	const { handleClientAction } = useClientActionHandler(
		addMessage,
		setIsLoading
	);
	const { sendMessage } = useMessageHandler(
		addMessage,
		setIsLoading,
		handleClientAction
	);

	const handleSendMessage = () => {
		if ( ! inputValue.trim() || isLoading ) {
			return;
		}

		setInputValue( '' );
		sendMessage( inputValue );
	};

	return (
		<div className="chat-container">
			<div className="chat-header">
				<h2>Demo AI Assistant</h2>
				<Button
					className="chat-header-clear"
					variant="tertiary"
					onClick={ clearMessages }
				>
					Clear
				</Button>
			</div>
			<div className="chat-body">
				<div className="chat-messages">
					{ messages.map( ( message, index ) => {
						switch ( message.role ) {
							case 'user':
								return (
									<UserMessage
										key={ index }
										text={ message.content ?? '' }
									/>
								);
							case 'tool':
								return (
									<FeatureTool
										key={ index }
										message={ message }
									/>
								);
							default:
								return (
									<AssistantMessage
										key={ index }
										message={ message }
									/>
								);
						}
					} ) }
					{ isLoading && <PendingAssistantMessage /> }
				</div>
				<div className="chat-input">
					<textarea
						className="chat-input-textarea"
						value={ inputValue }
						onChange={ ( e ) => {
							setInputValue( e.target.value );
						} }
						placeholder="Type your message..."
						onKeyDown={ ( e ) => {
							if ( e.key === 'Enter' && ! e.shiftKey ) {
								e.preventDefault();
								handleSendMessage();
							}
						} }
					/>
					<Button
						className="chat-input-submit"
						onClick={ handleSendMessage }
						disabled={ ! inputValue.trim() || isLoading }
						icon={ isLoading ? null : arrowRight }
					>
						{ isLoading ? (
							// @ts-expect-error: Spinner is not returning the correct type.
							<Spinner />
						) : (
							<span className="screen-reader-text">Send</span>
						) }
					</Button>
				</div>
			</div>
		</div>
	);
};

export const ChatApp = () => {
	return (
		<ConversationProvider>
			<ChatAppContent />
		</ConversationProvider>
	);
};
