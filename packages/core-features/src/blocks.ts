/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { createBlock } from '@wordpress/blocks';
import { dispatch } from '@wordpress/data';
import { store as blockEditorStore } from '@wordpress/block-editor';

/**
 * External dependencies
 */
import type { Feature } from '@wp-feature-api/client';

/**
 * Client-side feature to insert a paragraph block.
 */
export const insertParagraphBlock: Feature = {
	id: 'core/insert-paragraph-block',
	name: __( 'Insert Paragraph Block' ),
	description: __(
		'Inserts a new paragraph block after the current selection or at the end of the content.'
	),
	type: 'tool',
	location: 'client',
	categories: [ 'core', 'editor', 'blocks' ],
	input_schema: {
		type: 'object',
		properties: {
			content: {
				type: 'string',
				description: __( 'Text content for the paragraph.' ),
			},
		},
		required: [ 'content' ],
	},
	output_schema: {
		type: 'object',
		properties: {
			success: { type: 'boolean' },
			blockType: { type: 'string' },
		},
		required: [ 'success', 'blockType' ],
	},
	callback: ( args: { content: string } ) => {
		if ( typeof args?.content !== 'string' ) {
			throw new Error(
				'Content argument is missing or invalid for paragraph block.'
			);
		}
		try {
			const newBlock = createBlock( 'core/paragraph', {
				content: args.content,
			} );
			if ( ! newBlock ) {
				throw new Error( 'Failed to create paragraph block.' );
			}
			dispatch( blockEditorStore ).insertBlocks( newBlock );
			return { success: true, blockType: 'core/paragraph' };
		} catch ( error ) {
			// eslint-disable-next-line no-console
			console.error( 'Failed to insert paragraph block:', error );
			throw new Error(
				`Failed to insert paragraph block: ${
					error instanceof Error ? error.message : String( error )
				}`
			);
		}
	},
};

/**
 * Client-side feature to insert a heading block.
 */
export const insertHeadingBlock: Feature = {
	id: 'core/insert-heading-block',
	name: __( 'Insert Heading Block' ),
	description: __(
		'Inserts a new heading block after the current selection or at the end of the content.'
	),
	type: 'tool',
	location: 'client',
	categories: [ 'core', 'editor', 'blocks' ],
	input_schema: {
		type: 'object',
		properties: {
			content: {
				type: 'string',
				description: __( 'The text content for the heading.' ),
			},
			level: {
				type: 'integer',
				description: __( 'Heading level (intended range 1–6).' ),
			},
		},
		required: [ 'content', 'level' ],
	},
	output_schema: {
		type: 'object',
		properties: {
			success: { type: 'boolean' },
			blockType: { type: 'string' },
			level: { type: 'integer' },
		},
		required: [ 'success', 'blockType', 'level' ],
	},
	callback: ( args: { content: string; level?: number } ) => {
		if ( ! args?.content ) {
			throw new Error( 'Content is required for heading block.' );
		}
		try {
			const headingLevel =
				args.level && args.level >= 1 && args.level <= 6
					? args.level
					: 2;
			const newBlock = createBlock( 'core/heading', {
				content: args.content,
				level: headingLevel,
			} );
			if ( ! newBlock ) {
				throw new Error( 'Failed to create heading block.' );
			}
			dispatch( blockEditorStore ).insertBlocks( newBlock );
			return {
				success: true,
				blockType: 'core/heading',
				level: headingLevel,
			};
		} catch ( error ) {
			// eslint-disable-next-line no-console
			console.error( 'Failed to insert heading block:', error );
			throw new Error(
				`Failed to insert heading block: ${
					error instanceof Error ? error.message : String( error )
				}`
			);
		}
	},
};
