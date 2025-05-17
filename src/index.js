/**
 * Internal dependencies
 */
import featureData from './features/features.json';
import { registerFeature } from '../packages/client/src/api';

featureData.filter( ( feature ) => !! feature ).forEach( registerFeature );
