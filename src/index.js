/**
 * Internal dependencies
 */
import { coreFeatures } from '../packages/client-features/src';
import { registerFeature } from '../packages/client/src/api';
import { configure } from '../packages/client/src';

// Configure to use Abilities API backend if enabled
if ( window.wpFeatureAPIConfig?.useAbilitiesBackend ) {
	configure( { useAbilitiesBackend: true } );
}

coreFeatures.filter( ( feature ) => !! feature ).forEach( registerFeature );
