/**
 * WordPress dependencies
 */
import { registerPlugin } from '@wordpress/plugins';

/**
 * Internal dependencies
 */
import FeatureAPIInitializationComponent from './initialization-component';

// Register the plugin
registerPlugin( 'feature-api-integration', {
	render: FeatureAPIInitializationComponent,
} );
