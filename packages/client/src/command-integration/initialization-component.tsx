/**
 * WordPress dependencies
 */
import { useCommandLoader } from '@wordpress/commands';

/**
 * Internal dependencies
 */
import useFeatureCommands from './use-featured-comments';
import { store } from '../store';
import { useSelect } from '@wordpress/data';
import InputModal from './input-modal';

function useFeatureCommandLoader() {
	return useCommandLoader( {
		name: 'my-plugin/feature-command-loader', // Unique name for this loader
		// eslint-disable-next-line react-compiler/react-compiler
		hook: useFeatureCommands, // The custom hook defined above
	} );
}

// --- Original Modal Plugin ---
// (Keep the original modal plugin code if it's still needed)
// Create the component for our plugin
const FeatureAPIInitializationComponent = () => {
	useFeatureCommandLoader();
	const featureInputInProgress = useSelect(
		( select ) => select( store ).getFeatureInputInProgress(),
		[]
	);

	return (
		featureInputInProgress && (
			<InputModal featureId={ featureInputInProgress } />
		)
	);
};
export default FeatureAPIInitializationComponent;
