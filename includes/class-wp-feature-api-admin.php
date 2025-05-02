<?php
/**
 * WordPress Feature API Admin
 *
 * Provides admin interface for the WordPress Feature API
 *
 * @package WP_Feature_API
 */

/**
 * WordPress Feature API Admin Class
 *
 * @since 0.1.0
 */
class WP_Feature_API_Admin {

	/**
	 * Initialize the admin functionality
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function init() {
		add_action( 'admin_menu', array( $this, 'add_admin_pages' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}
	
	/**
	 * Enqueue required scripts for the admin page
	 *
	 * @since 0.1.0
	 * @param string $hook_suffix The current admin page.
	 * @return void
	 */
	public function enqueue_scripts( $hook_suffix ) {
		// Only load on our plugin's admin page
		if ( 'tools_page_wp-feature-api' !== $hook_suffix ) {
			return;
		}
		
		// Enqueue the WordPress script dependencies
		wp_enqueue_script( 'wp-element' );
		wp_enqueue_script( 'wp-components' );
		wp_enqueue_script( 'wp-data' );
		
		// Enqueue the Feature API client script
		wp_enqueue_script(
			'wp-feature-api-client',
			plugins_url( 'build/index.js', dirname( __FILE__ ) ),
			array( 'wp-element', 'wp-components', 'wp-data' ),
			filemtime( WP_FEATURE_API_PLUGIN_DIR . 'build/index.js' ),
			true
		);
	}

	/**
	 * Add admin pages to the WordPress admin
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function add_admin_pages() {
		add_submenu_page(
			'tools.php',
			__( 'Feature API', 'wp-feature-api' ),
			__( 'Feature API', 'wp-feature-api' ),
			'manage_options',
			'wp-feature-api',
			array( $this, 'render_admin_page' )
		);
	}

	/**
	 * Render the admin page
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function render_admin_page() {
		echo '<div class="wrap">';
		echo '<h1>' . esc_html__( 'WordPress Feature API', 'wp-feature-api' ) . '</h1>';
		
		// Use the correct function for getting features
		$features = wp_get_features();
		
		global $wp_rest_server;
		if ( ! $wp_rest_server ) {
			echo '<p>' . esc_html__( 'REST Server not initialized.', 'wp-feature-api' ) . '</p>';
		} else {
			echo '<div style="background-color: #f8f8f8; padding: 15px; border-radius: 5px;">';
			
			// Global endpoints
			echo '<h3>' . esc_html__( 'Global Endpoints', 'wp-feature-api' ) . '</h3>';
			echo '<table class="widefat" style="margin-bottom: 20px;">';
			echo '<thead>';
			echo '<tr>
				<th style="width: 15%;">' . esc_html__( 'Endpoint', 'wp-feature-api' ) . '</th>
				<th style="width: 15%;">' . esc_html__( 'Name', 'wp-feature-api' ) . '</th>
				<th style="width: 30%;">' . esc_html__( 'Description', 'wp-feature-api' ) . '</th>
				<th style="width: 10%;">' . esc_html__( 'Type', 'wp-feature-api' ) . '</th>
				<th style="width: 20%;">' . esc_html__( 'URLs', 'wp-feature-api' ) . '</th>
				<th style="width: 10%;">' . esc_html__( 'Actions', 'wp-feature-api' ) . '</th>
			</tr>';
			echo '</thead>';
			echo '<tbody>';
			
			// All Features endpoint
			echo '<tr>';
			echo '<td><strong>all-features</strong></td>';
			echo '<td>' . esc_html__( 'All Features', 'wp-feature-api' ) . '</td>';
			echo '<td>' . esc_html__( 'Returns a list of all registered features with their metadata. Supports pagination and filtering by type or category.', 'wp-feature-api' ) . '</td>';
			echo '<td>' . esc_html__( 'Collection', 'wp-feature-api' ) . '</td>';
			echo '<td>
				<div><strong>' . esc_html__( 'View Details', 'wp-feature-api' ) . ':</strong><br><code style="font-size: 11px;">' . esc_url( rest_url( 'wp/v2/features' ) ) . '</code> <span class="badge" style="background: #0073aa; color: white; padding: 2px 5px; border-radius: 3px; font-size: 10px;">GET</span></div>
			</td>';
			echo '<td>
				<a href="' . esc_url( rest_url( 'wp/v2/features' ) ) . '" target="_blank" class="button button-small" style="width: 100%; text-align: center;">' . esc_html__( 'View Details', 'wp-feature-api' ) . '</a>
			</td>';
			echo '</tr>';
			
			// Feature By ID endpoint
			echo '<tr>';
			echo '<td><strong>feature-by-id</strong></td>';
			echo '<td>' . esc_html__( 'Feature Metadata', 'wp-feature-api' ) . '</td>';
			echo '<td>' . esc_html__( 'Returns metadata about a specific feature. Replace "{feature-id}" with any feature ID (e.g., "demo/site-info").', 'wp-feature-api' ) . '</td>';
			echo '<td>' . esc_html__( 'Single', 'wp-feature-api' ) . '</td>';
			echo '<td>
				<div><strong>' . esc_html__( 'View Details', 'wp-feature-api' ) . ':</strong><br><code style="font-size: 11px;">' . esc_url( rest_url( 'wp/v2/features' ) ) . '/{feature-id}</code> <span class="badge" style="background: #0073aa; color: white; padding: 2px 5px; border-radius: 3px; font-size: 10px;">GET</span></div>
			</td>';
			echo '<td>
				<em style="font-size: 11px;">' . esc_html__( 'Use specific feature ID', 'wp-feature-api' ) . '</em>
			</td>';
			echo '</tr>';
			
			// Execute Feature endpoint
			echo '<tr>';
			echo '<td><strong>execute-feature</strong></td>';
			echo '<td>' . esc_html__( 'Feature Execution', 'wp-feature-api' ) . '</td>';
			echo '<td>' . esc_html__( 'Executes a specific feature and returns the result. HTTP method depends on feature type (GET for resources, POST for tools).', 'wp-feature-api' ) . '</td>';
			echo '<td>' . esc_html__( 'Action', 'wp-feature-api' ) . '</td>';
			echo '<td>
				<div><strong>' . esc_html__( 'Execute', 'wp-feature-api' ) . ':</strong><br><code style="font-size: 11px;">' . esc_url( rest_url( 'wp/v2/features' ) ) . '/{feature-id}/run</code> <span class="badge" style="background: #0073aa; color: white; padding: 2px 5px; border-radius: 3px; font-size: 10px;">GET/POST</span></div>
			</td>';
			echo '<td>
				<em style="font-size: 11px;">' . esc_html__( 'Use feature-specific buttons below', 'wp-feature-api' ) . '</em>
			</td>';
			echo '</tr>';
			
			// Feature Categories endpoint
			echo '<tr>';
			echo '<td><strong>categories</strong></td>';
			echo '<td>' . esc_html__( 'Feature Categories', 'wp-feature-api' ) . '</td>';
			echo '<td>' . esc_html__( 'Returns all feature categories. Categories help organize features into logical groups.', 'wp-feature-api' ) . '</td>';
			echo '<td>' . esc_html__( 'Collection', 'wp-feature-api' ) . '</td>';
			echo '<td>
				<div><strong>' . esc_html__( 'View Details', 'wp-feature-api' ) . ':</strong><br><code style="font-size: 11px;">' . esc_url( rest_url( 'wp/v2/features/categories' ) ) . '</code> <span class="badge" style="background: #0073aa; color: white; padding: 2px 5px; border-radius: 3px; font-size: 10px;">GET</span></div>
			</td>';
			echo '<td>
				<a href="' . esc_url( rest_url( 'wp/v2/features/categories' ) ) . '" target="_blank" class="button button-small" style="width: 100%; text-align: center;">' . esc_html__( 'View Details', 'wp-feature-api' ) . '</a>
			</td>';
			echo '</tr>';
			
			// Single Category endpoint
			echo '<tr>';
			echo '<td><strong>category-by-id</strong></td>';
			echo '<td>' . esc_html__( 'Category Details', 'wp-feature-api' ) . '</td>';
			echo '<td>' . esc_html__( 'Returns information about a specific category. Replace "{category-id}" with any category ID (e.g., "demo", "site").', 'wp-feature-api' ) . '</td>';
			echo '<td>' . esc_html__( 'Single', 'wp-feature-api' ) . '</td>';
			echo '<td>
				<div><strong>' . esc_html__( 'View Details', 'wp-feature-api' ) . ':</strong><br><code style="font-size: 11px;">' . esc_url( rest_url( 'wp/v2/features/categories' ) ) . '/{category-id}</code> <span class="badge" style="background: #0073aa; color: white; padding: 2px 5px; border-radius: 3px; font-size: 10px;">GET</span></div>
			</td>';
			echo '<td>
				<em style="font-size: 11px;">' . esc_html__( 'Use specific category ID', 'wp-feature-api' ) . '</em>
			</td>';
			echo '</tr>';
			
			echo '</tbody>';
			echo '</table>';
			
			// Feature-specific endpoints
			if ( ! empty( $features ) ) {
				echo '<h3>' . esc_html__( 'Feature-Specific Endpoints', 'wp-feature-api' ) . '</h3>';
				echo '<table class="widefat">';
				echo '<thead>';
				echo '<tr>
					<th style="width: 15%;">' . esc_html__( 'Feature ID', 'wp-feature-api' ) . '</th>
					<th style="width: 15%;">' . esc_html__( 'Name', 'wp-feature-api' ) . '</th>
					<th style="width: 30%;">' . esc_html__( 'Description', 'wp-feature-api' ) . '</th>
					<th style="width: 10%;">' . esc_html__( 'Type', 'wp-feature-api' ) . '</th>
					<th style="width: 20%;">' . esc_html__( 'URLs', 'wp-feature-api' ) . '</th>
					<th style="width: 10%;">' . esc_html__( 'Actions', 'wp-feature-api' ) . '</th>
				</tr>';
				echo '</thead>';
				echo '<tbody>';
				
				foreach ( $features as $feature ) {
					// Get feature info
					$feature_url = rest_url( 'wp/v2/features/' . $feature->get_id() );
					$run_url = rest_url( 'wp/v2/features/' . $feature->get_id() . '/run' );
					$feature_type = $feature->get_type();
					$feature_name = $feature->get_name();
					$feature_description = $feature->get_description();
					$feature_categories = implode( ', ', $feature->get_categories() );
					
					// Single row with all information
					echo '<tr>';
					echo '<td><strong>' . esc_html( $feature->get_id() ) . '</strong></td>';
					echo '<td>' . esc_html( $feature_name ) . '</td>';
					echo '<td>' . esc_html( $feature_description ) . '</td>';
					echo '<td>' . esc_html( $feature_type ) . '<br><small>' . esc_html__( 'Categories', 'wp-feature-api' ) . ': <br>' . esc_html( $feature_categories ) . '</small></td>';
					echo '<td>
						<div><strong>' . esc_html__( 'View Details', 'wp-feature-api' ) . ':</strong><br><code style="font-size: 11px;">' . esc_url( $feature_url ) . '</code> <span class="badge" style="background: #0073aa; color: white; padding: 2px 5px; border-radius: 3px; font-size: 10px;">GET</span></div>
						<div style="margin-top: 5px;"><strong>' . esc_html__( 'Execute', 'wp-feature-api' ) . ':</strong><br><code style="font-size: 11px;">' . esc_url( $run_url ) . '</code>';
					
					// Show appropriate HTTP method based on feature type
					if ( $feature_type === 'resource' ) {
						echo ' <span class="badge" style="background: #0073aa; color: white; padding: 2px 5px; border-radius: 3px; font-size: 10px;">GET</span>';
					} else {
						echo ' <span class="badge" style="background: #d54e21; color: white; padding: 2px 5px; border-radius: 3px; font-size: 10px;">POST</span>';
					}
					
					echo '</div>
					</td>';
					echo '<td>
						<a href="' . esc_url( $feature_url ) . '" target="_blank" class="button button-small" style="margin-bottom: 5px; width: 100%; text-align: center;">' . esc_html__( 'View Details', 'wp-feature-api' ) . '</a>
						<button onclick="executeFeature(\'' . esc_attr( $feature->get_id() ) . '\', \'' . esc_attr( $feature_type ) . '\')" class="button button-small button-primary" style="width: 100%; text-align: center;">' . esc_html__( 'Execute', 'wp-feature-api' ) . '</button>
					</td>';
					echo '</tr>';
				}
				
				echo '</tbody>';
				echo '</table>';
				
				// Add a container for the React modal
				echo '<div id="feature-api-admin-modal-container"></div>';
				
				// Add JavaScript for feature execution
				?>
				<script>
				// Store the base URL and nonce for repeated use - define these first to avoid hoisting issues
				var BASE_URL = "<?php echo esc_url( rest_url( 'wp/v2/features/' ) ); ?>";
				var WP_REST_NONCE = "<?php echo wp_create_nonce( 'wp_rest' ); ?>";
				
				// Initialize when the DOM is ready
				document.addEventListener("DOMContentLoaded", function() {
					console.log("Feature API Admin Page Initializing...");
					
					// Check feature API availability after a small delay to ensure everything is loaded
					setTimeout(function() {
						console.log("wp object available:", typeof window.wp !== "undefined");
						console.log("wp.data available:", typeof window.wp !== "undefined" && !!window.wp.data);
						console.log("wp.featureApi available:", typeof window.wp !== "undefined" && !!window.wp.featureApi);
						console.log("wpFeatureApi available:", typeof window.wpFeatureApi !== "undefined");
					}, 500);
				});
				
				// Handler for modal form submission
				window.wpFeatureApiHandleModalComplete = function(params) {
					console.log("Modal form submitted with params:", params);
					if (params && window.currentFeature) {
						console.log("Executing feature with modal params:", 
							window.currentFeature.id, 
							window.currentFeature.type,
							params
						);
						
						executeFeatureWithParams(
							window.currentFeature.id, 
							window.currentFeature.type, 
							params
						);
					}
				};
				
				// Main function for executing features
				function executeFeature(featureId, featureType) {
					console.log("executeFeature called:", featureId, featureType);
					
					// First check if the feature has an input schema by fetching its metadata
					// We expect featureId to already be in the correct format "type-id"
					console.log("Fetching feature metadata from:", BASE_URL + featureId);
					
					// Fetch the feature metadata using GET
					fetch(BASE_URL + featureId, {
						headers: {
							"X-WP-Nonce": WP_REST_NONCE
						}
					})
					.then(response => {
						if (!response.ok) {
							throw new Error("Failed to get feature details: " + response.status);
						}
						return response.json();
					})
					.then(feature => {
						console.log("Feature metadata received:", feature);
						
						// Check if the feature has input parameters
						var hasInputSchema = feature.input_schema && 
											 feature.input_schema.properties && 
											 Object.keys(feature.input_schema.properties).length > 0;
						
						console.log("Feature has input schema:", hasInputSchema);
						
						if (hasInputSchema) {
							// Store the current feature for use in the callback
							window.currentFeature = {
								id: featureId,
								type: featureType
							};
							
							// Copy needed properties from feature
							for (var key in feature) {
								if (feature.hasOwnProperty(key)) {
									window.currentFeature[key] = feature[key];
								}
							}
							
							console.log("Stored feature for callback:", window.currentFeature);
							
							// Create HTML form for input - using direct DOM creation to avoid escaping issues
							console.log("Showing HTML form for input parameters");
							
							// Clear the modal container
							var modalContainer = document.getElementById("feature-api-admin-modal-container");
							modalContainer.innerHTML = ""; 
							
							// Create the modal elements
							var modalDiv = document.createElement("div");
							modalDiv.className = "feature-api-modal";
							modalDiv.style.cssText = "position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.7); z-index: 999999; display: flex; align-items: center; justify-content: center;";
							
							var modalContent = document.createElement("div");
							modalContent.className = "feature-api-modal-content";
							modalContent.style.cssText = "background: white; padding: 20px; border-radius: 5px; width: 500px; max-width: 90%; max-height: 80vh; overflow-y: auto;";
							
							// Add header
							var title = document.createElement("h2");
							title.style.marginTop = "0";
							title.textContent = feature.name;
							
							var description = document.createElement("p");
							description.textContent = feature.description;
							
							// Create form
							var form = document.createElement("form");
							form.id = "feature-api-input-form";
							
							// Initialize required array if it doesn't exist
							if (!feature.input_schema.required) {
								feature.input_schema.required = [];
							}
							
							// Make sure context is in the required array if it exists in properties
							if (feature.input_schema.properties.context && !feature.input_schema.required.includes("context")) {
								feature.input_schema.required.push("context");
							}
							
							// Always show the required fields note
							var requiredNote = document.createElement("p");
							requiredNote.className = "description";
							requiredNote.style.cssText = "font-style: italic; margin-bottom: 10px;";
							
							var asterisk = document.createElement("span");
							asterisk.style.color = "red";
							asterisk.style.fontWeight = "bold";
							asterisk.textContent = "*";
							
							requiredNote.appendChild(asterisk);
							requiredNote.appendChild(document.createTextNode(" indicates required fields"));
							form.appendChild(requiredNote);
							
							// Build form fields
							for (var key in feature.input_schema.properties) {
								if (feature.input_schema.properties.hasOwnProperty(key)) {
									var prop = feature.input_schema.properties[key];
									var isRequired = feature.input_schema.required && feature.input_schema.required.includes(key);
									
									var fieldDiv = document.createElement("div");
									fieldDiv.style.marginBottom = "15px";
									
									var label = document.createElement("label");
									label.htmlFor = "feature-param-" + key;
									label.style.cssText = "display: block; font-weight: bold; margin-bottom: 5px;";
									label.textContent = key;
									
									// Add asterisk for required fields
									if (isRequired) {
										var requiredMark = document.createElement("span");
										requiredMark.style.color = "red";
										requiredMark.style.fontWeight = "bold";
										requiredMark.textContent = " *";
										label.appendChild(requiredMark);
									}
									
									var input = document.createElement("input");
									input.type = prop.type === "integer" ? "number" : "text";
									input.id = "feature-param-" + key;
									input.name = key;
									input.className = "widefat";
									
									if (prop.description) {
										input.placeholder = prop.description;
										
										var helpText = document.createElement("p");
										helpText.className = "description";
										helpText.style.cssText = "margin: 5px 0 0; font-style: italic;";
										helpText.textContent = prop.description;
										fieldDiv.appendChild(helpText);
									}
									
									if (isRequired) {
										input.required = true;
									}
									
									fieldDiv.appendChild(label);
									fieldDiv.appendChild(input);
									form.appendChild(fieldDiv);
								}
							}
							
							// Add buttons
							var buttonDiv = document.createElement("div");
							buttonDiv.style.cssText = "margin-top: 20px; text-align: right;";
							
							var cancelButton = document.createElement("button");
							cancelButton.type = "button";
							cancelButton.className = "button button-secondary";
							cancelButton.id = "feature-api-modal-cancel";
							cancelButton.style.marginRight = "10px";
							cancelButton.textContent = "Cancel";
							
							var submitButton = document.createElement("button");
							submitButton.type = "submit";
							submitButton.className = "button button-primary";
							submitButton.textContent = "Run Feature";
							
							buttonDiv.appendChild(cancelButton);
							buttonDiv.appendChild(submitButton);
							form.appendChild(buttonDiv);
							
							// Assemble the modal
							modalContent.appendChild(title);
							modalContent.appendChild(description);
							modalContent.appendChild(form);
							modalDiv.appendChild(modalContent);
							
							// Add to container
							modalContainer.appendChild(modalDiv);
							
							// Add form submission handler with validation
							document.getElementById("feature-api-input-form").addEventListener("submit", function(e) {
								e.preventDefault();
								var formData = {};
								var isValid = true;
								var firstInvalidField = null;
								
								// Get form values and validate required fields
								for (var key in feature.input_schema.properties) {
									if (feature.input_schema.properties.hasOwnProperty(key)) {
										var input = document.getElementById("feature-param-" + key);
										var value = input.value;
										var isRequired = feature.input_schema.required && feature.input_schema.required.includes(key);
										
										// Check if required field is empty
										if (isRequired && (!value || value.trim() === "")) {
											isValid = false;
											input.style.borderColor = "red";
											
											// Store the first invalid field to focus on it
											if (!firstInvalidField) {
												firstInvalidField = input;
											}
											
											// Add error message if not already present
											var errorId = "error-" + key;
											if (!document.getElementById(errorId)) {
												var errorMsg = document.createElement("p");
												errorMsg.id = errorId;
												errorMsg.style.color = "red";
												errorMsg.style.margin = "5px 0 0";
												errorMsg.textContent = "This field is required";
												input.parentNode.appendChild(errorMsg);
											}
										} else {
											// Reset validation styling
											input.style.borderColor = "";
											
											// Remove error message if it exists
											var errorEl = document.getElementById("error-" + key);
											if (errorEl) {
												errorEl.remove();
											}
											
											// Convert to correct type
											if (feature.input_schema.properties[key].type === "integer") {
												value = parseInt(value, 10);
											}
											
											formData[key] = value;
										}
									}
								}
								
								if (!isValid) {
									console.log("Form validation failed");
									if (firstInvalidField) {
										firstInvalidField.focus();
									}
									return;
								}
								
								console.log("Form validated and submitted with values:", formData);
								
								// Close the modal
								modalContainer.innerHTML = "";
								
								// Execute the feature with the parameters
								executeFeatureWithParams(featureId, featureType, formData);
							});
							
							// Add cancel button handler
							document.getElementById("feature-api-modal-cancel").addEventListener("click", function() {
								console.log("Form cancelled");
								modalContainer.innerHTML = "";
							});
							
							// Click outside to close
							document.querySelector(".feature-api-modal").addEventListener("click", function(e) {
								if (e.target === this) {
									console.log("Clicked outside modal, closing");
									modalContainer.innerHTML = "";
								}
							});
							
							// Close on ESC key
							var escKeyHandler = function(e) {
								if (e.key === "Escape") {
									console.log("ESC key pressed, closing modal");
									modalContainer.innerHTML = "";
									// Remove this event listener when modal is closed
									document.removeEventListener("keydown", escKeyHandler);
								}
							};
							document.addEventListener("keydown", escKeyHandler);
						} else {
							// No input schema, execute directly
							console.log("No input schema, executing directly");
							executeFeatureWithParams(featureId, featureType, {});
						}
					})
					.catch(error => {
						console.error("Error fetching feature metadata:", error);
						alert("Error fetching feature metadata: " + error.message + "\nCheck browser console for details.");
					});
				}
				
				// Helper function to execute a feature with parameters
				function executeFeatureWithParams(featureId, featureType, params) {
					console.log("executeFeatureWithParams called:", featureId, featureType, params);
					
					var method = featureType === "resource" ? "GET" : "POST";
					
					// For tools, we need to ensure we're using POST correctly
					console.log("Feature Type:", featureType, "Using method:", method);
					
					// Use featureId as is because it's already returned from PHP with the correct prefix
					// The feature ID received is already in the format "type-id"
					console.log("Using feature ID as-is:", featureId);
					
					var url = BASE_URL + featureId + "/run";
					console.log("Constructed URL:", url);
					
					// Create fetch options with proper authentication
					var options = {
						method: method,
						headers: {
							"Content-Type": "application/json",
							"X-WP-Nonce": WP_REST_NONCE
						}
					};
					
					// For GET requests with parameters, append them to the URL
					if (method === "GET" && Object.keys(params).length > 0) {
						var queryParams = new URLSearchParams();
						for (var key in params) {
							if (params.hasOwnProperty(key)) {
								var value = params[key];
								queryParams.append(key, typeof value === "object" ? JSON.stringify(value) : String(value));
							}
						}
						url = url + "?" + queryParams.toString();
						console.log("GET URL with params:", url);
					} 
					// For POST requests with parameters, add them to the body
					else if (method === "POST" && Object.keys(params).length > 0) {
						options.body = JSON.stringify(params);
						console.log("POST body:", options.body);
					}
					
					// Execute the request
					console.log("Fetching " + method + " " + url);
					fetch(url, options)
						.then(response => {
							if (!response.ok) {
								throw new Error("HTTP error " + response.status + ": " + response.statusText);
							}
							return response.json();
						})
						.then(result => {
							// Format the result for better readability
							var formattedResult = JSON.stringify(result, null, 2);
							console.log("Feature execution successful:", result);
							alert("Feature executed successfully.\n\nResult:\n" + formattedResult);
						})
						.catch(error => {
							console.error("Error executing feature:", error);
							alert("Error executing feature: " + error.message + "\nCheck browser console for details.");
						});
				}
				</script>
				<?php
			}
			
			echo '</div>';
		}
		
		echo '</div>';
	}
}