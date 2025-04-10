/**
 * WordPress dependencies
 */
import { render, createElement } from '@wordpress/element';

/**
 * External dependencies
 */
import { registerCoreFeatures } from '@wp-feature-api/core-features';

/**
 * Internal dependencies
 */
import { ChatApp } from './chat-app';
import './style.scss';

// Register core features
registerCoreFeatures();

document.addEventListener( 'DOMContentLoaded', () => {
	const container = document.getElementById( 'wp-feature-api-demo-root' );
	if ( container ) {
		render( createElement( ChatApp ), container );
	}
} );
