/**
 * External dependencies
 */
import '@wp-feature-api/client';
import { registerCoreFeatures } from '@wp-feature-api/client-features';
import { dispatch, select, subscribe } from '@wordpress/data';
import { store as commandsStore } from '@wordpress/commands';
import { store as featureStore } from '@wp-feature-api/client'; // Assuming the store name is exported
import { starFilled } from '@wordpress/icons'; // Import a default icon
import { store as coreDataStore } from '@wordpress/core-data'; // May not be needed directly here

registerCoreFeatures();

/**
 * Initializes the synchronization between features and commands.
 * This function encapsulates the state and logic to keep commands
 * in sync with registered features that have callbacks.
 */
const initializeFeatureCommandSync = () => {
	// Store previous selector results to compare against
	let previousFeatures = null;
	let previousCommands = null;

	const syncFeatureCommands = () => {
		// Get current state from the stores
		const currentFeatures = select( featureStore ).getRegisteredFeatures();
		const currentCommands = select( commandsStore ).getCommands(); // Returns an array
		const hasExistingCommands =
			currentCommands && currentCommands.length > 0; // Check array length

		if ( ! currentFeatures || currentFeatures.length === 0 ) {
			// No features to process
			return;
		}

		// Only sync if other commands exist
		if ( hasExistingCommands ) {
			currentFeatures.forEach( ( feature ) => {
				const commandName = `feature-command/${ feature.id }`;

				// Check if feature has a callback AND a command with this name doesn't already exist in the array
				const commandExists = currentCommands.some(
					( command ) => command.name === commandName
				);

				if ( typeof feature.callback === 'function' && ! commandExists ) {
					const command = {
						name: commandName,
						label: feature.name || feature.id,
						icon: feature.meta?.icon || starFilled,
						callback: ( context ) => {
							// Dispatch an action to open a modal
							// ASSUMPTION: A data store (e.g., 'my-plugin/modal') exists
							// with an `openModal` action.
							dispatch( 'my-plugin/modal' ).openModal( {
								title: `Feature: ${ feature.name || feature.id }`,
								body: 'Hello World',
							} );

							// Close the command palette if the context provides the function
							if ( context?.close ) {
								context.close();
							}
						},
						// context: feature.context || 'global',
					};

					// Register the command
					try {
						dispatch( commandsStore ).registerCommand( command );
					} catch ( error ) {
						// Avoid logging errors repeatedly if registration fails persistently
						// or if the command somehow got registered externally.
						console.error(
							`Failed to register command for feature ${ feature.id } (${ commandName }): `,
							error
						);
					}
				}
			} );
		}
		// Optional: Consider adding logic here to *unregister* commands if `hasExistingCommands` becomes false,
		// or if a feature is unregistered from the featureStore.
		// This would involve iterating `currentCommands` and checking if the corresponding feature still exists and warrants a command.
	};

	// Subscribe to changes, but check if relevant data has changed before syncing
	const unsubscribe = subscribe( () => {
		const currentFeatures = select( featureStore ).getRegisteredFeatures();
		const currentCommands = select( commandsStore ).getCommands();

		const featuresChanged = currentFeatures !== previousFeatures;
		const commandsChanged = currentCommands !== previousCommands;

		if ( featuresChanged || commandsChanged ) {
			// console.log( 'Relevant store state changed, syncing feature commands...' ); // Debugging log
			syncFeatureCommands();
		}

		// Update previous state for the next comparison
		previousFeatures = currentFeatures;
		previousCommands = currentCommands;
	} );

	// Run initially to perform the first sync and set the initial previous state
	// console.log( 'Initial sync check...' );
	syncFeatureCommands();
	// Initialize previous state *after* the first sync.
	previousFeatures = select( featureStore ).getRegisteredFeatures();
	previousCommands = select( commandsStore ).getCommands();

	// Return the unsubscribe function in case the caller wants to manage cleanup
	return unsubscribe;
};

// Initialize the feature command synchronization
const featureCommandUnsubscribe = initializeFeatureCommandSync();

// Note: If this script/plugin can be unloaded, ensure 'featureCommandUnsubscribe()' is called
// during the cleanup process to prevent memory leaks.
