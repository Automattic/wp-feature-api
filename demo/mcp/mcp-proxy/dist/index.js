"use strict";
var __createBinding = (this && this.__createBinding) || (Object.create ? (function(o, m, k, k2) {
    if (k2 === undefined) k2 = k;
    var desc = Object.getOwnPropertyDescriptor(m, k);
    if (!desc || ("get" in desc ? !m.__esModule : desc.writable || desc.configurable)) {
      desc = { enumerable: true, get: function() { return m[k]; } };
    }
    Object.defineProperty(o, k2, desc);
}) : (function(o, m, k, k2) {
    if (k2 === undefined) k2 = k;
    o[k2] = m[k];
}));
var __setModuleDefault = (this && this.__setModuleDefault) || (Object.create ? (function(o, v) {
    Object.defineProperty(o, "default", { enumerable: true, value: v });
}) : function(o, v) {
    o["default"] = v;
});
var __importStar = (this && this.__importStar) || (function () {
    var ownKeys = function(o) {
        ownKeys = Object.getOwnPropertyNames || function (o) {
            var ar = [];
            for (var k in o) if (Object.prototype.hasOwnProperty.call(o, k)) ar[ar.length] = k;
            return ar;
        };
        return ownKeys(o);
    };
    return function (mod) {
        if (mod && mod.__esModule) return mod;
        var result = {};
        if (mod != null) for (var k = ownKeys(mod), i = 0; i < k.length; i++) if (k[i] !== "default") __createBinding(result, mod, k[i]);
        __setModuleDefault(result, mod);
        return result;
    };
})();
Object.defineProperty(exports, "__esModule", { value: true });
/**
 * External dependencies
 */
// @ts-ignore Import errors will be resolved at runtime
const index_js_1 = require("@modelcontextprotocol/sdk/server/index.js");
// @ts-ignore Import errors will be resolved at runtime
const stdio_js_1 = require("@modelcontextprotocol/sdk/server/stdio.js");
const types_js_1 = require("@modelcontextprotocol/sdk/types.js");
/**
 * Internal dependencies
 */
const wordpress_api_1 = require("./wordpress-api");
const fs = __importStar(require("fs"));
const path = __importStar(require("path"));
// Suppress Node.js TLS verification warnings
process.emitWarning = function () { };
const logFile = path.join(__dirname, '../mcp-proxy.log');
function log(message) {
    const timestamp = new Date().toISOString();
    const logMessage = `${timestamp}: ${message}\n`;
    fs.appendFileSync(logFile, logMessage);
    // process.stderr.write(logMessage);
}
async function initialize() {
    log('Starting initialization...');
    const features = (await (0, wordpress_api_1.wpRequest)('wp/v2/Features'));
    log(`Retrieved ${features.length} features from WordPress\n`);
    // Create an MCP server
    const server = new index_js_1.Server({
        name: 'my-website-features-api',
        version: '1.0.1',
    }, {
        capabilities: {
            tools: {},
            resources: {},
            prompts: {},
        },
    });
    // Start receiving messages on stdin and sending messages on stdout
    const transport = new stdio_js_1.StdioServerTransport();
    // Create a wrapper for request handlers that adds logging
    const withLogging = (schema, handler) => async (request) => {
        log(`Received ${schema} request: ${JSON.stringify(request)}`);
        const response = await handler(request);
        log(`${schema} response: ${JSON.stringify(response)}`);
        return response;
    };
    // Tool definitions
    server.setRequestHandler(types_js_1.ListToolsRequestSchema, withLogging('ListTools', async () => {
        log('Processing ListToolsRequest');
        return {
            tools: features.map((tool) => {
                let properties = {};
                properties = Object.entries((tool.input_schema?.properties || {})).reduce((acc, [key, value]) => {
                    acc[key] = {
                        type: value.type,
                        description: value.description,
                    };
                    return acc;
                }, {});
                return {
                    name: tool.id,
                    description: tool.description,
                    inputSchema: {
                        type: 'object',
                        properties,
                        required: tool.input_schema?.required || [],
                    },
                };
            }),
        };
    }));
    // Tool handlers
    server.setRequestHandler(types_js_1.CallToolRequestSchema, withLogging('CallTool', async (request) => {
        const { name, arguments: args } = request.params;
        const feature = features.find((_feature) => _feature.id === name);
        log(`Try to run feature: ${JSON.stringify(feature, null, 2)}`);
        if (!feature) {
            return {
                error: 'Feature not found',
            };
        }
        log(`Calling feature: ${name} with args: ${JSON.stringify(args)}`);
        const answer = await (0, wordpress_api_1.wpRequest)(`wp/v2/features/${name}/run`, {
            method: feature.type === 'tool' ? 'POST' : 'GET',
            params: args,
        });
        log(`Feature ${name} returned: ${JSON.stringify(answer, null, 2)}`);
        return {
            content: [
                {
                    type: 'text',
                    text: JSON.stringify(answer, null, 2),
                },
            ],
        };
    }));
    // Connect to the transport
    server
        .connect(transport)
        .then(() => {
        log('MCP server connected to transport successfully');
    })
        .catch((error) => {
        log(`Error starting MCP server: ${error}`);
        process.exit(1);
    });
    // Log startup message to stderr (not stdout which is used for MCP)
    log('Starting MCP feature api server...');
}
initialize();
