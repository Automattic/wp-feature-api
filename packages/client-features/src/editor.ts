/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { store as editorStore } from '@wordpress/editor';

/**
 * External dependencies
 */
import type { Feature } from '@wp-feature-api/client';

/**
 * Client-side feature to set the post title.
 */
export const setTitle: Feature = {
	id: 'editor/set-title',
	name: __( 'Set Post Title' ),
	description: __( 'Updates the title of the current post in the editor.' ),
	type: 'tool',
	location: 'client',
	categories: [ 'core', 'editor' ],
	input_schema: {
		type: 'object',
		properties: {
			title: {
				type: 'string',
				description: __( 'The new title for the post.' ),
			},
		},
		required: [ 'title' ],
	},
	output_schema: {
		type: 'object',
		properties: {
			success: { type: 'boolean' },
		},
		required: [ 'success' ],
	},
	callback: ( args: { title: string }, { data } ) => {
		if ( typeof args?.title !== 'string' ) {
			throw new Error( 'Title argument is missing or invalid.' );
		}
		try {
			data.dispatch( editorStore ).editPost( { title: args.title } );
			return { success: true };
		} catch ( error ) {
			throw new Error(
				`Failed to set post title: ${
					error instanceof Error ? error.message : String( error )
				}`
			);
		}
	},
};

/**
 * Client-side feature to save the post.
 */
export const savePost: Feature = {
	id: 'editor/save-post',
	name: __( 'Save Post' ),
	description: __( 'Triggers the save action for the current post.' ),
	type: 'tool',
	location: 'client',
	categories: [ 'core', 'editor' ],
	output_schema: {
		type: 'object',
		properties: {
			success: { type: 'boolean' },
		},
		required: [ 'success' ],
	},
	callback: ( _args: any, { data } ) => {
		try {
			data.dispatch( editorStore ).savePost();
			return { success: true };
		} catch ( error ) {
			throw new Error(
				`Failed to save post: ${
					error instanceof Error ? error.message : String( error )
				}`
			);
		}
	},
};

/**
 * Client-side feature to get the editor content.
 */
export const getEditorContent: Feature = {
	id: 'resource-editor/get-content',
	name: __( 'Get Editor Content' ),
	description: __(
		'Retrieves the content of the current post in the editor.'
	),
	type: 'resource',
	location: 'client',
	categories: [ 'core', 'editor' ],
	output_schema: {
		type: 'object',
		properties: {
			content: { type: 'string' },
		},
		required: [ 'content' ],
	},
	callback: ( _args: any, { data } ) => {
		const content = data.select( editorStore ).getCurrentPost().content;
		return { content };
	},
};
