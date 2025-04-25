/**
 * WordPress dependencies
 */
import {
	createContext,
	useState,
	useCallback,
	useMemo,
	useEffect,
	useRef,
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
import { createToolExecutor, type ToolExecutor } from '../agent/tool-executor';
import { createWpFeatureToolProvider } from '../agent/wp-feature-tool-provider';

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
	const [ toolExecutor, setToolExecutor ] = useState< ToolExecutor | null >(
		null
	);
	const isInitializing = useRef( false );

	useEffect( () => {
		if ( messages.length > 0 ) {
			localStorage.setItem( STORAGE_KEY, JSON.stringify( messages ) );
		}
	}, [ messages ] );

	useEffect( () => {
		if ( isInitializing.current ) {
			return;
		}
		isInitializing.current = true;

		const initializeExecutor = async () => {
			const executor = createToolExecutor();
			const provider = createWpFeatureToolProvider();
			try {
				await executor.addProvider( provider );
				setToolExecutor( executor );
			} catch ( error ) {
				// eslint-disable-next-line no-console
				console.error( 'Failed to initialize Tool Executor:', error );
			}
		};

		initializeExecutor();
	}, [] );

	const agent: Agent | null = useMemo( () => {
		if ( toolExecutor ) {
			return createAgent( { apiClient: wpApiClient, toolExecutor } );
		}
		return null;
	}, [ toolExecutor ] );

	const sendMessage = useCallback(
		async ( query: string ) => {
			if ( isLoading || ! agent ) {
				return;
			}

			// TODO: Consider making this a setting.
			const defaultModel = 'gpt-4o';

			setIsLoading( true );

			const historyBeforeQuery = messages;

			try {
				const messageStream = agent.processQuery(
					query,
					historyBeforeQuery,
					defaultModel
				);

				for await ( const messageChunk of messageStream ) {
					setMessages( ( prev ) => {
						if (
							messageChunk.role === 'user' &&
							prev.some(
								( m ) =>
									m.role === 'user' &&
									m.content === messageChunk.content
							)
						) {
							return prev;
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
		[ isLoading, agent, messages, toolExecutor ]
	);

	const clearConversation = useCallback( () => {
		setMessages( [] );
		localStorage.removeItem( STORAGE_KEY );
	}, [] );

	const contextValue = useMemo(
		() => ( {
			messages,
			setMessages,
			sendMessage,
			isLoading,
			clearConversation,
		} ),
		[ messages, setMessages, sendMessage, isLoading, clearConversation ]
	);

	return (
		<ConversationContext.Provider value={ contextValue }>
			{ children }
		</ConversationContext.Provider>
	);
};
