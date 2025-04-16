/**
 * WordPress dependencies
 */
//import { registerPlugin } from '@wordpress/plugins';
import { useState, useMemo } from '@wordpress/element';

import { __ } from '@wordpress/i18n';
import { useCommandLoader } from '@wordpress/commands';
import { useDispatch, useRegistry, useSelect } from '@wordpress/data';
import { brush } from '@wordpress/icons'; // Default icon for commands

// Internal dependencies
/**
 * Internal dependencies
 */
import { store } from '../store';
import { getRegisteredFeatures } from '../store/selectors';
import type { Feature } from '../types';
import MyModalPlugin from './initialization-component';

// Define the icon for the menu item (optional, using a dashicon here)
const MyModalIcon = 'smiley';

// --- Feature Command Loader ---
// This section defines and registers a command loader that makes features
// with callbacks available in the Command Palette (Cmd+K).

/**
 * Custom React hook to load registered features as dynamic commands.
 * It fetches features using getRegisteredFeatures, filters them based on
 * the presence of a callback and the user's search term, and formats them
 * for the Command Palette.
 *
 * @param {Object} options        Hook options passed by the command loader.
 * @param {string} options.search The search term entered by the user in the Command Palette.
 * @return {Object} An object containing the list of commands and the loading state.
 *                   { commands: Array<object>, isLoading: boolean }
 */
function useFeatureCommands( { search } ) {
	// @ts-expect-error - useRegistry types not available
	const { dispatch, select } = useRegistry();
	// Select features and loading state using the imported selector
	const { features, isLoading } = useSelect( ( _select ) => {
		const resolvedFeatures = _select(
			store
		).getRegisteredFeatures() as Feature[];

		// Check if the data resolution has finished using the core/data store
		// Note: Linter might complain here if @types/wordpress__data is missing/incomplete
		// @ts-expect-error - WP Core type, not available on custom store
		const hasFinishedResolution = _select( store ).hasFinishedResolution(
			'getRegisteredFeatures',
			[]
		);

		return {
			features: resolvedFeatures || [],
			isLoading: ! hasFinishedResolution,
		};
	}, [] ); // Dependency array is empty as selectors handle their own memoization
	// Memoize the command generation process to avoid recalculating on every render
	const setFeatureInputInProgress =
		useDispatch( store ).setFeatureInputInProgress;
	const commands = useMemo( () => {
		// Filter features to include only those with a callback function
		const featuresWithCallback = features.filter(
			( feature ) => typeof feature.callback === 'function'
		);

		// Map features to the command object structure required by the Command Palette
		let commandList = featuresWithCallback.map( ( feature ) => ( {
			name: `feature/${ feature.id }`, // Unique command name (prefixing helps avoid conflicts)
			label: feature.name || feature.id, // Human-readable label (fallback to id)
			icon: feature.icon || brush, // Use feature's icon or a default one
			callback: ( { close } ) => {
				if ( ! feature.input_schema ) {
					// Wrapper callback provided by the command palette
					feature.callback?.( {}, { data: { dispatch, select } } ); // Execute the original feature callback safely
					close(); // Close the palette after execution
				} else {
					// Open the modal
					setFeatureInputInProgress( feature.id );
				}
			},
		} ) );

		// Filter commands based on the search term (case-insensitive) if provided
		if ( search ) {
			commandList = commandList.filter( ( command ) =>
				command.label.toLowerCase().includes( search.toLowerCase() )
			);
		}
		return commandList;
	}, [ features, search, dispatch, select, setFeatureInputInProgress ] ); // Recalculate only if features or search term change

	// Return the prepared commands and the loading state
	return { commands, isLoading };
}

export default useFeatureCommands;
