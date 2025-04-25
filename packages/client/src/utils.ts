/**
 * Recursively removes null values from an object.
 *
 * @param {any} obj The object to clean
 * @return {any} A new object with null values removed
 */
export function removeNullValues( obj: any ): any {
	if ( obj === null || obj === undefined ) {
		return undefined;
	}

	if ( Array.isArray( obj ) ) {
		return obj.map( ( item ) => removeNullValues( item ) );
	}

	if ( typeof obj === 'object' ) {
		const result: Record< string, any > = {};
		for ( const key in obj ) {
			if ( Object.prototype.hasOwnProperty.call( obj, key ) ) {
				const value = removeNullValues( obj[ key ] );
				if ( value !== undefined ) {
					result[ key ] = value;
				}
			}
		}
		return result;
	}

	return obj;
}
