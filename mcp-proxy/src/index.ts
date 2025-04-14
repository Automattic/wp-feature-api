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
import { log } from './utils';
// Suppress Node.js TLS verification warnings
process.emitWarning = function () {};

export interface WpFeature {
	id: string;
	name: string;
	description: string;
	type: 'tool' | 'resource';
	categories: string[];
	input_schema: {
		properties?: {
			[ key: string ]: {
				type: string;
				description: string;
			};
		};
		required?: string[];
	};
}

async function initialize() {
	log( 'Starting initialization...' );
	const features = ( await wpRequest( 'wp/v2/Features' ) ) as WpFeature[];
	log( `Retrieved ${ features.length } features from WordPress\n` );

	// Create an MCP server
	const server = new Server(
		{
			name: 'my-website-features-api',
			version: '1.0.1',
		},
		{
			capabilities: {
				tools: {},
				resources: {},
			},
		}
	);

	// Start receiving messages on stdin and sending messages on stdout
	const transport = new StdioServerTransport();

	// Create a wrapper for request handlers that adds logging
	const withLogging =
		( schema: string, handler: Function ) => async ( request: any ) => {
			log(
				`Received ${ schema } request: ${ JSON.stringify( request ) }`
			);
			const response = await handler( request );
			log( `${ schema } response: ${ JSON.stringify( response ) }` );
			return response;
		};

	// Tool definitions
	server.setRequestHandler(
		ListToolsRequestSchema,
		withLogging( 'ListTools', async () => {
			log( 'Processing ListToolsRequest' );
			return {
				tools: features.map( ( tool ) => {
					let properties: {
						[ key: string ]: { type: string; description: string };
					} = {};
					properties = Object.entries(
						tool.input_schema?.properties || {}
					).reduce(
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
							required: tool.input_schema?.required || [],
						},
					};
				} ),
			};
		} )
	);

	// Tool handlers
	server.setRequestHandler(
		CallToolRequestSchema,
		withLogging(
			'CallTool',
			async ( request: { params: { name: string; arguments: any } } ) => {
				const { name, arguments: args } = request.params;
				const feature = features.find(
					( _feature ) => _feature.id === name
				);
				log(
					`Try to run feature: ${ JSON.stringify(
						feature,
						null,
						2
					) }`
				);
				if ( ! feature ) {
					return {
						error: 'Feature not found',
					};
				}

				log(
					`Calling feature: ${ name } with args: ${ JSON.stringify(
						args
					) }`
				);

				const answer = await wpRequest(
					`wp/v2/features/${ name }/run`,
					{
						method: feature.type === 'tool' ? 'POST' : 'GET',
						params: args,
					}
				);

				log(
					`Feature ${ name } returned: ${ JSON.stringify(
						answer,
						null,
						2
					) }`
				);

				return {
					content: [
						{
							type: 'text',
							text: JSON.stringify( answer, null, 2 ),
						},
					],
				};
			}
		)
	);

	// Connect to the transport
	server
		.connect( transport )
		.then( () => {
			log( 'MCP server connected to transport successfully' );
		} )
		.catch( ( error ) => {
			log( `Error starting MCP server: ${ error }` );
			process.exit( 1 );
		} );

	// Log startup message to stderr (not stdout which is used for MCP)
	log( 'Starting MCP feature api server...' );
}
initialize();
