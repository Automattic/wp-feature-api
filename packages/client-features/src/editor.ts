/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { dispatch, select } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';
import type { BlockInstance } from '@wordpress/blocks';

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
	callback: ( args: { title: string } ) => {
		if ( typeof args?.title !== 'string' ) {
			throw new Error( 'Title argument is missing or invalid.' );
		}
		try {
			dispatch( editorStore ).editPost( { title: args.title } );
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
 * Client-side feature to preview the post.
 */
export const previewPost: Feature = {
	id: 'editor/preview-post',
	name: __( 'Preview Post' ),
	description: __( 'Opens the preview for the current post.' ),
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
	callback: () => {
		try {
			// This action typically opens a new tab directly.
			dispatch( editorStore ).previewPost();
			return { success: true };
		} catch ( error ) {
			throw new Error(
				`Failed to trigger post preview: ${
					error instanceof Error ? error.message : String( error )
				}`
			);
		}
	},
};

/**
 * Client-side feature to set the post status.
 */
export const setPostStatus: Feature = {
	id: 'editor/set-post-status',
	name: __( 'Set Post Status' ),
	description: __( 'Changes the status of the current post.' ),
	type: 'tool',
	location: 'client',
	categories: [ 'core', 'editor' ],
	input_schema: {
		type: 'object',
		properties: {
			status: {
				type: 'string',
				description: __(
					'The new status (e.g., draft, pending, publish).'
				),
				enum: [ 'draft', 'pending', 'publish', 'private' ],
			},
		},
		required: [ 'status' ],
	},
	output_schema: {
		type: 'object',
		properties: {
			success: { type: 'boolean' },
		},
		required: [ 'success' ],
	},
	callback: ( args: { status: string } ) => {
		const validStatuses = [ 'draft', 'pending', 'publish', 'private' ];
		if (
			typeof args?.status !== 'string' ||
			! validStatuses.includes( args.status )
		) {
			throw new Error(
				`Invalid status provided. Must be one of: ${ validStatuses.join(
					', '
				) }`
			);
		}
		try {
			dispatch( editorStore ).editPost( { status: args.status } );
			return { success: true };
		} catch ( error ) {
			throw new Error(
				`Failed to set post status: ${
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
	callback: () => {
		try {
			dispatch( editorStore ).savePost();
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
	id: 'editor/get-editor-content',
	name: __( 'Get Editor Content' ),
	description: __( 'Retrieves the full content of the editor.' ),
	type: 'tool',
	location: 'client',
	categories: [ 'core', 'editor' ],
	input_schema: {
		type: 'object',
		properties: {
			format: {
				type: 'string',
				description: __( 'Format for the content (html or blocks).' ),
				enum: [ 'html', 'blocks' ],
				default: 'html',
			},
		},
	},
	output_schema: {
		type: 'object',
		properties: {
			success: { type: 'boolean' },
			content: {
				type: [ 'string', 'array' ],
				description: 'The editor content in the requested format.',
			},
		},
		required: [ 'success', 'content' ],
	},
	callback: ( args?: { format?: string } ) => {
		const format = args?.format === 'blocks' ? 'blocks' : 'html';
		try {
			// Declare content type to accommodate both string (HTML) and BlockInstance array
			let content: string | BlockInstance[] = '';
			if ( format === 'html' ) {
				// eslint-disable-next-line @wordpress/data-no-store-string-literals
				content = select( 'core/editor' ).getEditedPostContent();
			} else {
				// Ensure block editor store selector is available and has getBlocks
				// eslint-disable-next-line @wordpress/data-no-store-string-literals
				const blockEditorSelector = select( 'core/block-editor' );
				if ( typeof blockEditorSelector?.getBlocks !== 'function' ) {
					throw new Error(
						'Block editor data store or getBlocks selector is not available.'
					);
				}
				content = blockEditorSelector.getBlocks();
			}
			// Explicitly type the return object to satisfy the Feature definition
			return {
				success: true,
				content: content as string | BlockInstance[],
			};
		} catch ( error ) {
			throw new Error(
				`Failed to get editor content: ${
					error instanceof Error ? error.message : String( error )
				}`
			);
		}
	},
};
