/**
 * External dependencies
 */
// @ts-ignore Import errors will be resolved at runtime
import { Server } from '@modelcontextprotocol/sdk/server/index.js';
// @ts-ignore Import errors will be resolved at runtime
import { StdioServerTransport } from '@modelcontextprotocol/sdk/server/stdio.js';
import {
	ListToolsRequestSchema,
	CallToolRequestSchema,
} from '@modelcontextprotocol/sdk/types.js';
/**
 * Internal dependencies
 */
import { wpRequest } from './wordpress-api';

export interface WpFeature {
	id: string;
	name: string;
	description: string;
	type: 'tool' | 'resource';
	categories: string[];
	input_schema: Object;
}

// Create an MCP server
const server = new Server(
	{
		name: 'feature-api',
		version: '1.0.0',
	},
	{
		capabilities: { tools: {} },
	}
);

// Start receiving messages on stdin and sending messages on stdout
const transport = new StdioServerTransport();

async function initialize() {
	const features = ( await wpRequest( 'wp/v2/features' ) ) as WpFeature[];
	// console.log( features );
	// const tools = features.filter( ( feature ) => feature.type === 'tool' );

	// Tool definitions
	server.setRequestHandler( ListToolsRequestSchema, async () => {
		return {
			tools: features.map( ( tool ) => {
				let properties: {
					[ key: string ]: { type: string; description: string };
				} = {};
				properties = Object.entries( tool.input_schema || {} ).reduce(
					(
						acc: {
							[ key: string ]: {
								type: string;
								description: string;
							};
						},
						[ key, value ]: [
							string,
							{ type: string; description: string },
						]
					) => {
						acc[ key ] = {
							type: value.type,
							description: value.description,
						};
						return acc;
					},
					{}
				);
				return {
					name: tool.id,
					description: tool.description,
					inputSchema: {
						type: 'object',
						properties,
					},
				};
			} ),
		};
	} );

	// Tool handlers
	server.setRequestHandler( CallToolRequestSchema, async ( request ) => {
		const { name, arguments: args } = request.params;
		const feature = features.find( ( _feature ) => feature.id === name );
		if ( ! feature ) {
			return {
				error: 'Feature not found',
			};
		}

		const answer = await wpRequest( `wp/v2/features/${ name }/run`, {
			method: feature.type === 'tool' ? 'POST' : 'GET',
			params: args,
		} );
		return {
			type: 'text',
			text: JSON.stringify( answer ),
		};
	} );

	// Connect to the transport
	server.connect( transport ).catch( ( error ) => {
		process.stderr.write( `Error starting MCP server: ${ error }\n` );
		process.exit( 1 );
	} );
}

// Log startup message to stderr (not stdout which is used for MCP)
process.stderr.write( 'Starting MCP ping server...\n' );
initialize();
