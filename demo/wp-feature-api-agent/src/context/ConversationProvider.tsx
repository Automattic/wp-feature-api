/**
 * WordPress dependencies
 */
import {
	createContext,
	useState,
	useCallback,
	useMemo,
	useEffect,
} from '@wordpress/element';

/**
 * External dependencies
 */
import { type ReactNode, type Dispatch, type SetStateAction } from 'react';

/**
 * Internal dependencies
 */
import type { Message } from '../types/messages';
import { createAgent, type Agent, type ApiClient } from '../agent/orchestrator';

export interface ConversationContextType {
	messages: Message[];
	setMessages: Dispatch< SetStateAction< Message[] > >;
	sendMessage: ( query: string ) => Promise< void >;
	isLoading: boolean;
	clearConversation: () => void;
}

export const ConversationContext =
	createContext< ConversationContextType | null >( null );

interface ConversationProviderProps {
	children: ReactNode;
}

// TODO: Thinking of removing this.
declare global {
	interface Window {
		wpFeatureApiAgentData?: {
			defaultModel?: string;
		};
	}
}

// TODO: Should import from wordpress/api-fetch
const wpApiClient: ApiClient = async ( endpoint, data ) => {
	const apiFetch = ( window as any ).wp?.apiFetch;
	if ( ! apiFetch ) {
		throw new Error(
			'wp.apiFetch is not available. Ensure script dependencies are loaded.'
		);
	}
	return await apiFetch( { path: endpoint, method: 'POST', data } );
};

// Storage key for localStorage, basic memory persistence.
const STORAGE_KEY = 'wp-feature-api-agent-conversation';

export const ConversationProvider = ( {
	children,
}: ConversationProviderProps ) => {
	const [ messages, setMessages ] = useState< Message[] >( () => {
		try {
			const stored = localStorage.getItem( STORAGE_KEY );
			return stored ? JSON.parse( stored ) : [];
		} catch ( error ) {
			return [];
		}
	} );
	const [ isLoading, setIsLoading ] = useState< boolean >( false );

	// Save messages to localStorage whenever they change
	useEffect( () => {
		localStorage.setItem( STORAGE_KEY, JSON.stringify( messages ) );
	}, [ messages ] );

	// Instantiate the agent, injecting the WordPress API client
	const agent: Agent = useMemo(
		() => createAgent( { apiClient: wpApiClient } ),
		[]
	);

	// Function to handle sending a message
	const sendMessage = useCallback(
		async ( query: string ) => {
			if ( isLoading ) {
				return;
			}

			// Get the default model from localized data, fallback if needed
			const defaultModel =
				window.wpFeatureApiAgentData?.defaultModel || 'gpt-3.5-turbo'; // Provide a sensible fallback

			setIsLoading( true );
			const userMessage: Message = { role: 'user', content: query };
			setMessages( ( prevMessages ) => [ ...prevMessages, userMessage ] );

			try {
				const currentHistory = [ ...messages, userMessage ];
				let assistantResponse = '';

				for await ( const messageChunk of agent.processQuery(
					query,
					currentHistory,
					defaultModel
				) ) {
					setMessages( ( prev ) => {
						const lastMessage = prev[ prev.length - 1 ];
						if (
							lastMessage?.role === 'assistant' &&
							messageChunk.role === 'assistant'
						) {
							assistantResponse += messageChunk.content ?? '';
							return [
								...prev.slice( 0, -1 ),
								{ ...lastMessage, content: assistantResponse },
							];
						}
						return [ ...prev, messageChunk ];
					} );
				}
			} catch ( error ) {
				// eslint-disable-next-line no-console
				console.error( 'Error sending message:', error );
				setMessages( ( prev ) => [
					...prev,
					{
						role: 'assistant',
						content: `Error: ${
							error instanceof Error
								? error.message
								: 'Failed to get response'
						}`,
					},
				] );
			} finally {
				setIsLoading( false );
			}
		},
		[ isLoading, agent, messages ]
	);

	const clearConversation = useCallback( () => {
		setMessages( [] );
	}, [] );

	const contextValue = useMemo(
		() => ( {
			messages,
			setMessages,
			sendMessage,
			isLoading,
			clearConversation,
		} ),
		[ messages, sendMessage, isLoading, clearConversation ]
	);

	return (
		<ConversationContext.Provider value={ contextValue }>
			{ children }
		</ConversationContext.Provider>
	);
};
