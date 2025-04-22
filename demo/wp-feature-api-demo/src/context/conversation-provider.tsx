/**
 * WordPress dependencies
 */
import {
	createContext,
	useContext,
	useEffect,
	useState,
} from '@wordpress/element';

export type Message = {
	content: string | null;
	role: 'user' | 'assistant' | 'tool';
	tool_calls: any[];
	tool_call_id?: string;
	feature?: WP_Feature;
};

type WP_Feature = {
	id: string;
	name: string;
	description: string;
	type: 'resource' | 'tool';
};

interface ConversationContextType {
	messages: Message[];
	addMessage: ( message: Message ) => void;
	clearMessages: () => void;
}

export type ClientAction = {
	type: 'execute_tool';
	tool_id: string;
	args: any;
	tool_call_id: string;
};

const ConversationContext = createContext<
	ConversationContextType | undefined
>( undefined );

const STORAGE_KEY = 'wp-feature-api-demo-conversation';

export const ConversationProvider = ( {
	children,
}: {
	children: React.ReactNode;
} ) => {
	const [ messages, setMessages ] = useState< Message[] >( () => {
		const stored = localStorage.getItem( STORAGE_KEY );
		return stored ? JSON.parse( stored ) : [];
	} );

	useEffect( () => {
		localStorage.setItem( STORAGE_KEY, JSON.stringify( messages ) );
	}, [ messages ] );

	const addMessage = ( message: Message ) => {
		setMessages( ( prev ) => [ ...prev, message ] );
	};

	const clearMessages = () => {
		setMessages( [] );
	};

	return (
		<ConversationContext.Provider
			value={ { messages, addMessage, clearMessages } }
		>
			{ children }
		</ConversationContext.Provider>
	);
};

export const useConversation = () => {
	const context = useContext( ConversationContext );
	if ( context === undefined ) {
		throw new Error(
			'useConversation must be used within a ConversationProvider'
		);
	}
	return context;
};
