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
	name: __( 'Log Message to Console' ),
	description: __( 'Logs a specified message to the browser console.' ),
	type: 'tool',
	location: 'client',
	categories: [ 'demo', 'logging' ],
	input_schema: {
		type: 'object',
		properties: {
			message: {
				type: 'string',
				description: __( 'The message to log.' ),
			},
		},
		required: [ 'message' ],
	},
	callback: ( args: { message: string } ) => {
		// eslint-disable-next-line no-console
		console.log( `[Demo Feature] ${ args.message }` );
		return { success: true, logged: true };
	},
};
