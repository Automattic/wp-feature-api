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
import type { Feature } from '@wp-feature-api/client';

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
 * Feature to search for patterns in the pattern directory.
 */
export const searchPatterns: Feature = {
	id: 'patterns/search',
	name: __( 'Search Patterns' ),
	description: __( 'Search for block patterns in the pattern directory.' ),
	type: 'resource',
	location: 'client',
	categories: [ 'client', 'patterns' ],
	input_schema: {
		type: 'object',
		properties: {
			search: {
				type: 'string',
				description: __( 'Search term for patterns.' ),
			},
			category: {
				type: 'string',
				description: __( 'Category ID to filter patterns.' ),
			},
			per_page: {
				type: 'number',
				description: __( 'Number of patterns per page.' ),
				default: 100,
			},
			page: {
				type: 'number',
				description: __( 'Page number.' ),
				default: 1,
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
						id: { type: 'number' },
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
		},
		required: [ 'patterns' ],
	},
	callback: async ( args: {
		search?: string;
		category?: string;
		per_page?: number;
		page?: number;
	} ) => {
		// First get all patterns
		const response = await fetch(
			'/wp-json/wp/v2/block-patterns/patterns'
		);

		if ( ! response.ok ) {
			throw new Error( 'Failed to fetch patterns' );
		}

		let patterns = await response.json();

		// Apply category filter if specified
		if ( args.category ) {
			patterns = patterns.filter( ( pattern: any ) =>
				pattern.categories.includes( args.category )
			);
		}

		// Apply search filter if specified
		if ( args.search ) {
			patterns = searchItems( patterns, args.search );
		}

		// Apply pagination
		const page = args.page || 1;
		const perPage = args.per_page || 100;
		const start = ( page - 1 ) * perPage;
		const end = start + perPage;
		patterns = patterns.slice( start, end );

		return { patterns };
	},
};

/**
 * Feature to get all pattern categories.
 */
export const getPatternCategories: Feature = {
	id: 'patterns/get-categories',
	name: __( 'Get Pattern Categories' ),
	description: __( 'Retrieve all available block pattern categories.' ),
	type: 'resource',
	location: 'client',
	categories: [ 'client', 'patterns' ],
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
	description: __( 'Insert a block pattern into the editor.' ),
	type: 'tool',
	location: 'client',
	categories: [ 'client', 'patterns', 'editor' ],
	input_schema: {
		type: 'object',
		properties: {
			patternId: {
				type: 'string',
				description: __( 'The ID of the pattern to insert.' ),
			},
		},
		required: [ 'patternId' ],
	},
	output_schema: {
		type: 'object',
		properties: {
			success: { type: 'boolean' },
		},
		required: [ 'success' ],
	},
	callback: async ( args: { patternId: string; clientId?: string } ) => {
		try {
			// First fetch the pattern content
			const response = await fetch(
				'/wp-json/wp/v2/block-patterns/patterns'
			);

			if ( ! response.ok ) {
				throw new Error( 'Failed to fetch patterns' );
			}

			const patterns = await response.json();
			const pattern = patterns.find(
				( p: any ) => p.name === args.patternId
			);

			if ( ! pattern ) {
				throw new Error(
					`Pattern with ID ${ args.patternId } not found`
				);
			}

			// Parse and insert the blocks
			const blocks = parseBlocks( pattern.content );

			if ( args.clientId ) {
				dispatch( blockEditorStore ).insertBlocks(
					blocks,
					undefined,
					args.clientId
				);
			} else {
				dispatch( blockEditorStore ).insertBlocks( blocks );
			}

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
