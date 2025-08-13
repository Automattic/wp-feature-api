/**
 * Internal dependencies
 */
import { coreFeatures } from '../packages/client-features/src';
import { registerFeature } from '../packages/client/src/api';

coreFeatures.filter( ( feature ) => !! feature ).forEach( registerFeature );
