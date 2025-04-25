/**
 * Admin JavaScript for the AI API Proxy.
 *
 * @param {jQuery} $ jQuery object.
 */
( function ( $ ) {
	'use strict';

	/**
	 * Initialize the admin scripts.
	 */
	function init() {
		// Update models when the provider changes.
		$( '#default-provider' ).on( 'change', function () {
			updateModels();
		} );
	}

	/**
	 * Update models for the selected provider.
	 */
	function updateModels() {
		const provider = $( '#default-provider' ).val();
		const modelSelect = $( '#default-model' );
		const providers = window.wpAiApiProxy.providers;

		// Clear the model select.
		modelSelect.empty();

		// If no provider is selected or no models for the provider, return.
		if ( ! provider || ! providers[ provider ] ) {
			return;
		}

		const currentModel = modelSelect.val();

		// Add the models to the select.
		$.each( providers[ provider ], function ( modelId, modelName ) {
			const selected = modelId === currentModel ? ' selected' : '';
			modelSelect.append(
				'<option value="' +
					modelId +
					'"' +
					selected +
					'>' +
					modelName +
					'</option>'
			);
		} );
	}

	// Initialize when the document is ready.
	$( document ).ready( init );
} )( jQuery );
