/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * External dependencies
 */
import type { Feature } from '@wp-feature-api/client';

/**
 * Demo client-side feature to log a message.
 */
export const logMessage: Feature = {
	id: 'demo/log-message',
	name: __( 'Log Message (Demo)' ),
	description: __( 'Logs a specified message to the browser console.' ),
	type: 'tool',
	location: 'client', // Explicitly client-side
	categories: [ 'demo', 'logging' ],
	input_schema: {
		type: 'object',
		properties: {
			message: {
				type: 'string',
				description: __( 'The message to log.' ),
			},
			level: {
				type: 'string',
				description: __( 'Log level (log, warn, error, info).' ),
				enum: [ 'log', 'warn', 'error', 'info' ],
			},
		},
		required: [ 'message', 'level' ],
	},
	// No output schema needed for this simple logging feature
	callback: ( args: {
		message: string;
		level?: 'log' | 'warn' | 'error' | 'info';
	} ) => {
		const message = args.message || 'Hello from Demo Feature!';
		const level = args.level || 'log';

		try {
			switch ( level ) {
				case 'warn':
					// eslint-disable-next-line no-console
					console.warn( `[Demo Feature] ${ message }` );
					break;
				case 'error':
					// eslint-disable-next-line no-console
					console.error( `[Demo Feature] ${ message }` );
					break;
				case 'info':
					// eslint-disable-next-line no-console
					console.info( `[Demo Feature] ${ message }` );
					break;
				default:
					// eslint-disable-next-line no-console
					console.log( `[Demo Feature] ${ message }` );
					break;
			}
			return { success: true, logged: true, level };
		} catch ( error ) {
			// eslint-disable-next-line no-console
			console.error( 'Demo feature logMessage failed:', error );
			throw new Error(
				`Failed to log message: ${
					error instanceof Error ? error.message : String( error )
				}`
			);
		}
	},
};
