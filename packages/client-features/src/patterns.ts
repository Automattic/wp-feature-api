/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { store as blockEditorStore } from '@wordpress/block-editor';
import { parse as parseBlocks } from '@wordpress/blocks';
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
 * Internal utilities for pattern search
 * @param items
 * @param searchTerm
 */
function searchItems( items: any[], searchTerm: string ) {
	if ( ! searchTerm ) {
		return items;
	}

	const normalizedSearchTerm = searchTerm.toLowerCase().trim();
	return items.filter( ( item ) => {
		const searchFields = [
			item.title,
			item.description,
			...( item.keywords || [] ),
			...( item.categories || [] ),
		].filter( Boolean );

		return searchFields.some( ( field ) =>
			field.toLowerCase().includes( normalizedSearchTerm )
		);
	} );
}

/**
 * Fetches all patterns from the WordPress API
 */
async function fetchPatterns() {
	const response = await fetch( '/wp-json/wp/v2/block-patterns/patterns' );

	if ( ! response.ok ) {
		throw new Error( 'Failed to fetch patterns' );
	}

	return await response.json();
}

/**
 * Finds a specific pattern by name
 * @param patternName Name of the pattern to find
 */
async function findPatternByName( patternName: string ) {
	if ( ! patternName ) {
		throw new Error( 'Pattern name is required' );
	}

	try {
		const patterns = await fetchPatterns();
		const pattern = patterns.find( ( p: any ) => p.name === patternName );

		if ( ! pattern ) {
			throw new Error(
				`Pattern with name "${ patternName }" not found. Make sure to use the pattern's "name" field as the ID.`
			);
		}

		return pattern;
	} catch ( error ) {
		throw new Error(
			`Failed to fetch pattern: ${
				error instanceof Error ? error.message : String( error )
			}`
		);
	}
}

/**
 * Feature to search for patterns in the pattern directory.
 */
export const searchPatterns: Feature = {
	id: 'patterns/search',
	name: __( 'Search Patterns' ),
	description: __(
		'Search for block patterns in the pattern directory. Block patterns are predefined collections of blocks that create layouts and page sections. They help you quickly build sophisticated designs without starting from scratch, and can include anything from simple buttons to complete layouts that can be customized.'
	),
	type: 'resource',
	location: 'client',
	categories: [ 'client', 'patterns', 'layout' ],
	input_schema: {
		type: 'object',
		properties: {
			search: {
				type: 'string',
				description: __( 'Search term for patterns.' ),
			},
			category: {
				type: 'string',
				description: __( 'Category ID to filter patterns by.' ),
			},
			include_content: {
				type: 'boolean',
				description: __(
					'If true, includes the pattern content in results. Default is false to reduce payload size. Pattern content can be fetched separately using the patterns/get-content feature.'
				),
				default: false,
			},
		},
	},
	output_schema: {
		type: 'object',
		properties: {
			patterns: {
				type: 'array',
				items: {
					type: 'object',
					properties: {
						name: { type: 'string' },
						title: { type: 'string' },
						description: { type: 'string' },
						categories: {
							type: 'array',
							items: { type: 'string' },
						},
						keywords: {
							type: 'array',
							items: { type: 'string' },
						},
						content: { type: 'string' },
					},
				},
			},
		},
		required: [ 'patterns' ],
	},
	callback: async ( args: {
		search?: string;
		category?: string;
		include_content?: boolean;
	} ) => {
		// First get all patterns, as there is no way to paginate or filter the patterns in the block-patterns API
		let patterns = await fetchPatterns();

		// Apply category filter if specified
		if ( args.category ) {
			patterns = patterns.filter(
				( pattern: any ) =>
					pattern.categories &&
					pattern.categories.includes( args.category )
			);
		}

		// Apply search filter if specified
		if ( args.search ) {
			patterns = searchItems( patterns, args.search );
		}

		// Unless include_content is true, remove content field to reduce payload size
		if ( ! args.include_content ) {
			patterns = patterns.map( ( pattern: any ) => {
				const { content, ...metadata } = pattern;
				return metadata;
			} );
		}

		return { patterns };
	},
};

/**
 * Feature to get a specific pattern's content by name.
 */
export const getPatternContent: Feature = {
	id: 'patterns/get-content',
	name: __( 'Get Pattern Content' ),
	description: __( 'Retrieves the content of a specific pattern by name.' ),
	type: 'resource',
	location: 'client',
	categories: [ 'client', 'patterns', 'layout' ],
	input_schema: {
		type: 'object',
		properties: {
			pattern: {
				type: 'string',
				description: __( 'The name of the pattern to retrieve.' ),
			},
		},
		required: [ 'pattern' ],
	},
	output_schema: {
		type: 'object',
		properties: {
			pattern: {
				type: 'object',
				properties: {
					id: { type: 'number' },
					name: { type: 'string' },
					title: { type: 'string' },
					content: { type: 'string' },
					categories: {
						type: 'array',
						items: { type: 'string' },
					},
					description: { type: 'string' },
				},
			},
		},
		required: [ 'pattern' ],
	},
	callback: async ( args: { pattern: string } ) => {
		const pattern = await findPatternByName( args.pattern );
		return { pattern };
	},
};

/**
 * Feature to get all pattern categories.
 */
export const getPatternCategories: Feature = {
	id: 'patterns/get-categories',
	name: __( 'Get Pattern Categories' ),
	description: __(
		'Retrieve all available block pattern categories. Categories help organize patterns by their purpose or design style (such as buttons, columns, headers, footers, content types, etc.), making it easier to find the right pattern for your specific needs. Use categories to filter patterns when searching for the layout components.'
	),
	type: 'resource',
	location: 'client',
	categories: [ 'client', 'patterns', 'layout' ],
	output_schema: {
		type: 'object',
		properties: {
			categories: {
				type: 'array',
				items: {
					type: 'object',
					properties: {
						name: { type: 'string' },
						label: { type: 'string' },
						description: { type: 'string' },
					},
				},
			},
		},
		required: [ 'categories' ],
	},
	callback: async () => {
		const response = await fetch(
			'/wp-json/wp/v2/block-patterns/categories'
		);

		if ( ! response.ok ) {
			throw new Error( 'Failed to fetch pattern categories' );
		}

		const categories = await response.json();
		return { categories };
	},
};

/**
 * Feature to insert a block pattern into the editor.
 */
export const insertPattern: Feature = {
	id: 'patterns/insert',
	name: __( 'Insert Pattern' ),
	description: __(
		'Insert a block pattern into the editor. After selecting a pattern from the available options, this feature allows you to place it directly into your content where you can then customize it to match your needs.'
	),
	type: 'tool',
	location: 'client',
	categories: [ 'client', 'patterns', 'layout', 'editor' ],
	is_eligible: isInEditor,
	input_schema: {
		type: 'object',
		properties: {
			pattern: {
				type: 'string',
				description: __( 'The name of the pattern to insert.' ),
			},
		},
		required: [ 'pattern' ],
	},
	output_schema: {
		type: 'object',
		properties: {
			success: { type: 'boolean' },
		},
		required: [ 'success' ],
	},
	callback: async ( args: { pattern: string } ) => {
		try {
			const pattern = await findPatternByName( args.pattern );

			if ( ! pattern.content ) {
				throw new Error(
					`Pattern with name "${ args.pattern }" has no content.`
				);
			}

			const blocks = parseBlocks( pattern.content );
			dispatch( blockEditorStore ).insertBlocks( blocks );

			return { success: true };
		} catch ( error ) {
			throw new Error(
				`Failed to insert pattern: ${
					error instanceof Error ? error.message : String( error )
				}`
			);
		}
	},
};
