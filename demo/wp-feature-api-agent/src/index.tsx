/**
 * WordPress dependencies
 */
import { createRoot } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { ConversationProvider } from './context/ConversationProvider';
import { ChatApp } from './components/ChatApp';

import './style.scss';

document.addEventListener( 'DOMContentLoaded', () => {
	const targetElement = document.getElementById(
		'wp-feature-api-agent-chat'
	);

	if ( targetElement ) {
		const root = createRoot( targetElement );
		root.render(
			<ConversationProvider>
				<ChatApp />
			</ConversationProvider>
		);
	} else {
		// eslint-disable-next-line no-console
		console.error( 'Target element #wp-feature-api-agent-chat not found.' );
	}
} );
