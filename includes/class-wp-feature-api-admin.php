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
				
				// Add JavaScript for feature execution
				echo '<script>
				function executeFeature(featureId, featureType) {
					const url = "' . esc_url( rest_url( 'wp/v2/features/' ) ) . '" + featureId + "/run";
					
					// Use appropriate HTTP method based on feature type
					const method = featureType === "resource" ? "GET" : "POST";
					
					// Create fetch options with proper authentication
					const fetchOptions = {
						method: method,
						headers: {
							"Content-Type": "application/json",
							"X-WP-Nonce": "' . esc_js( wp_create_nonce( 'wp_rest' ) ) . '"
						}
					};
					
					// Execute the feature
					fetch(url, fetchOptions)
					.then(response => {
						if (!response.ok) {
							throw new Error(`HTTP error ${response.status}: ${response.statusText}`);
						}
						return response.json();
					})
					.then(data => {
						// Format the result for better readability
						const formattedResult = JSON.stringify(data, null, 2);
						alert(`Feature executed with ${method}.\n\nResult:\n${formattedResult}`);
					})
					.catch(error => {
						alert(`Error executing feature: ${error.message}\n\nCheck browser console for details.`);
						console.error("Feature execution error:", error);
					});
				}
				</script>';
			}
			
			echo '</div>';
		}
	}
}