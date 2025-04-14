/**
 * WordPress dependencies
 */
import { render, createElement } from '@wordpress/element';

/**
 * External dependencies
 */
import { registerFeature } from '@wp-feature-api/client';

/**
 * Internal dependencies
 */
import { ChatApp } from './chat-app';
import { logMessage } from './features/log-message';
import './style.scss';

// Register demo specific feature
registerFeature( logMessage );

document.addEventListener( 'DOMContentLoaded', () => {
	const container = document.getElementById( 'wp-feature-api-demo-root' );
	if ( container ) {
		render( createElement( ChatApp ), container );
	}
} );
