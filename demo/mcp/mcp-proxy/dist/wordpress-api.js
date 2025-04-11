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
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
exports.wpRequest = wpRequest;
/**
 * External dependencies
 */
// @ts-ignore Import errors will be resolved at runtime
const node_fetch_1 = __importDefault(require("node-fetch"));
const fs = __importStar(require("fs"));
const path = __importStar(require("path"));
/**
 * WordPress API request function with basic auth support
 *
 * @param {string} endpoint        - The API endpoint (e.g., 'wp/v2/posts')
 * @param {Object} options         - Request options
 * @param {string} options.method  - HTTP method ('GET' or 'POST')
 * @param {Object} options.params  - Query parameters for GET or body for POST
 * @param {string} options.baseUrl - Base URL for the WordPress site (defaults to env.WP_API_URL)
 * @return {Promise<any>} API response as JSON
 */
const logFile = path.join(__dirname, '../mcp-proxy.log');
function log(message) {
    const timestamp = new Date().toISOString();
    const logMessage = `${timestamp}: ${message}\n`;
    fs.appendFileSync(logFile, logMessage);
    // process.stderr.write(logMessage);
}
async function wpRequest(endpoint, options = {}) {
    const { method = 'GET', params = {}, baseUrl = process.env.WP_API_URL, } = options;
    if (!baseUrl) {
        throw new Error('WordPress API URL not set. Set WP_API_URL environment variable.');
    }
    log(`env: ${JSON.stringify(process.env)}`);
    // Get auth credentials from environment variables
    const username = process.env.WP_API_USERNAME;
    const password = process.env.WP_API_PASSWORD;
    if (!username || !password) {
        throw new Error('WordPress API credentials not set. Set WP_API_USERNAME and WP_API_PASSWORD environment variables.');
    }
    // Prepare authorization header
    const auth = Buffer.from(`${username}:${password}`).toString('base64');
    // Build URL with query params for GET requests
    let url = `${baseUrl.replace(/\/$/, '')}/wp-json/${endpoint.replace(/^\//, '')}`;
    log(`Requesting url: ${url}`);
    const headers = {
        Authorization: `Basic ${auth}`,
        'Content-Type': 'application/json',
    };
    const fetchOptions = {
        method,
        headers,
    };
    log(`Params: ${JSON.stringify(params)}`);
    // Handle GET vs POST requests
    if (method === 'GET' && Object.keys(params).length) {
        const queryString = new URLSearchParams(Object.entries(params).map(([key, value]) => [
            key,
            typeof value === 'object'
                ? JSON.stringify(value)
                : String(value),
        ])).toString();
        url = `${url}?${queryString}`;
    }
    else if (method === 'POST') {
        fetchOptions.body = JSON.stringify(params);
    }
    try {
        const response = await (0, node_fetch_1.default)(url, fetchOptions);
        // Handle error responses
        if (!response.ok) {
            const errorText = await response.text();
            throw new Error(`WordPress API error (${response.status}): ${errorText}`);
        }
        return await response.json();
    }
    catch (error) {
        console.error('WordPress API request failed:', error);
        throw error;
    }
}
