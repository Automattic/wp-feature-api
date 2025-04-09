/**
 * External dependencies
 */
const resolverNode = require( 'eslint-import-resolver-node' );
exports.interfaceVersion = 2;

exports.resolve = function ( source, file, config ) {
	const resolve = ( sourcePath ) =>
		resolverNode.resolve( sourcePath, file, {
			...config,
			extensions: [ '.tsx', '.ts', '.mjs', '.js', '.json', '.node' ],
		} );

	return resolve( source );
};
