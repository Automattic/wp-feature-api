/**
 * External dependencies
 */
import * as fs from 'fs';
import * as path from 'path';

const logFile = path.join( __dirname, '../mcp-proxy.log' );
export function log( message: string ) {
	const timestamp = new Date().toISOString();
	const logMessage = `${ timestamp }: ${ message }\n`;
	fs.appendFileSync( logFile, logMessage );
	// process.stderr.write(logMessage);
}
