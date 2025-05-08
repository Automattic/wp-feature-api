/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { createBlock } from '@wordpress/blocks';
import { store as blockEditorStore } from '@wordpress/block-editor';
import { dispatch } from '@wordpress/data';

/**
 * External dependencies
 */
import type { Feature } from '@automattic/wp-feature-api';

/**
 * Internal dependencies
 */
import { isInEditor } from './utils';

/**
 * Client-side feature to insert a paragraph block.
 */
export const insertParagraphBlock: Feature = {
	id: 'blocks/insert-paragraph-block',
	name: __( 'Insert Paragraph Block' ),
	description: __(
		'Inserts a new paragraph block after the current selection or at the end of the content.'
	),
	type: 'tool',
	location: 'client',
	categories: [ 'core', 'editor', 'blocks' ],
	is_eligible: isInEditor,
	input_schema: {
		type: 'object',
		properties: {
			content: {
				type: 'string',
				description: __( 'Text content for the paragraph.' ),
			},
		},
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
			const content = args.content
				.replace( /\\n/g, '\n' ) // First replace escaped newlines
				.replace( /\n/g, '<br>' ); // Then replace actual newlines with <br>
			const newBlock = createBlock( 'core/paragraph', {
				content,
			} );
			if ( ! newBlock ) {
				throw new Error( 'Failed to create paragraph block.' );
			}
			dispatch( blockEditorStore ).insertBlocks( newBlock );
			return { success: true, blockType: 'core/paragraph' };
		} catch ( error ) {
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
	id: 'blocks/insert-heading-block',
	name: __( 'Insert Heading Block' ),
	description: __(
		'Inserts a new heading block after the current selection or at the end of the content.'
	),
	type: 'tool',
	location: 'client',
	categories: [ 'core', 'editor', 'blocks' ],
	is_eligible: isInEditor,
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
		required: [ 'content' ],
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
			throw new Error(
				`Failed to insert heading block: ${
					error instanceof Error ? error.message : String( error )
				}`
			);
		}
	},
};

/**
 * Client-side feature to insert a quote block.
 */
export const insertQuoteBlock: Feature = {
	id: 'blocks/insert-quote-block',
	name: __( 'Insert Quote Block' ),
	description: __( 'Inserts a new quote block.' ),
	type: 'tool',
	location: 'client',
	categories: [ 'core', 'editor', 'blocks' ],
	is_eligible: isInEditor,
	input_schema: {
		type: 'object',
		properties: {
			value: {
				type: 'string',
				description: __(
					'The main quote text (will be placed in an inner paragraph block).'
				),
			},
			citation: {
				type: 'string',
				description: __( 'Optional citation for the quote.' ),
			},
		},
		required: [ 'value' ],
	},
	output_schema: {
		type: 'object',
		properties: {
			success: { type: 'boolean' },
			clientId: { type: 'string' },
		},
		required: [ 'success', 'clientId' ],
	},
	callback: ( args: { value: string; citation?: string } ) => {
		if ( typeof args?.value !== 'string' ) {
			throw new Error(
				'Value argument is missing or invalid for quote block.'
			);
		}
		try {
			const value = args.value.replace( /\n/g, '<br>' );
			const innerParagraph = createBlock( 'core/paragraph', {
				content: value,
			} );

			const newBlock = createBlock(
				'core/quote',
				{
					citation: args.citation || '',
				},
				[ innerParagraph ]
			);
			if ( ! newBlock ) {
				throw new Error( 'Failed to create quote block.' );
			}
			dispatch( blockEditorStore ).insertBlocks( newBlock );
			return { success: true, clientId: newBlock.clientId };
		} catch ( error ) {
			throw new Error(
				`Failed to insert quote block: ${
					error instanceof Error ? error.message : String( error )
				}`
			);
		}
	},
};

/**
 * Client-side feature to insert a list block.
 */
export const insertListBlock: Feature = {
	id: 'blocks/insert-list-block',
	name: __( 'Insert List Block' ),
	description: __( 'Inserts a new list block (ordered or unordered).' ),
	type: 'tool',
	location: 'client',
	categories: [ 'core', 'editor', 'blocks' ],
	is_eligible: isInEditor,
	input_schema: {
		type: 'object',
		properties: {
			values: {
				type: 'array',
				items: { type: 'string' },
				description: __(
					'An array of strings for the list items (each will become an inner list-item block).'
				),
			},
			ordered: {
				type: 'boolean',
				description: __(
					'Whether the list should be ordered (numbered).'
				),
			},
		},
		required: [ 'values' ],
	},
	output_schema: {
		type: 'object',
		properties: {
			success: { type: 'boolean' },
			clientId: { type: 'string' },
		},
		required: [ 'success', 'clientId' ],
	},
	callback: ( args: { values: string[]; ordered?: boolean } ) => {
		if ( ! Array.isArray( args?.values ) || args.values.length === 0 ) {
			throw new Error(
				'Values argument must be a non-empty array for list block.'
			);
		}
		try {
			const innerListItemBlocks = args.values.map( ( itemValue ) => {
				return createBlock( 'core/list-item', {
					content: itemValue,
				} );
			} );

			const newBlock = createBlock(
				'core/list',
				{
					ordered: !! args.ordered,
				},
				innerListItemBlocks
			);
			if ( ! newBlock ) {
				throw new Error( 'Failed to create list block.' );
			}
			dispatch( blockEditorStore ).insertBlocks( newBlock );
			return { success: true, clientId: newBlock.clientId };
		} catch ( error ) {
			throw new Error(
				`Failed to insert list block: ${
					error instanceof Error ? error.message : String( error )
				}`
			);
		}
	},
};
