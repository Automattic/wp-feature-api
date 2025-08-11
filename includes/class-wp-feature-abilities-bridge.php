<?php
/**
 * WordPress Feature API Abilities Bridge
 *
 * Bridges between the WordPress.org Abilities API and the WP Feature API,
 * allowing abilities to be automatically registered as features.
 *
 * @package WordPress\Feature_API
 * @since 0.1.0
 */

/**
 * Bridge class for converting abilities to features.
 *
 * This class provides a bridge between the WordPress.org Abilities API and the
 * WP Feature API, allowing abilities registered via wp_register_ability() to
 * be automatically available as features through the Feature API.
 *
 * @since 0.1.0
 */
class WP_Feature_Abilities_Bridge {

	/**
	 * The singleton instance.
	 *
	 * @since 0.1.0
	 * @var WP_Feature_Abilities_Bridge|null
	 */
	private static $instance = null;

	/**
	 * Gets the singleton instance of the bridge.
	 *
	 * @since 0.1.0
	 * @return WP_Feature_Abilities_Bridge The bridge instance.
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor to prevent direct instantiation.
	 *
	 * @since 0.1.0
	 */
	private function __construct() {
		// Private constructor for singleton pattern.
	}

	/**
	 * Initializes the bridge if abilities backend is enabled.
	 *
	 * Checks the WP_FEATURE_API_ABILITIES_BACKEND configuration constant
	 * and registers abilities as features if enabled. Logs warnings if
	 * the abilities backend is enabled but the Abilities API is not available.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function init() {
		if ( ! $this->is_abilities_backend_enabled() ) {
			return;
		}

		if ( ! function_exists( 'wp_get_abilities' ) ) {
			error_log( 'WP Feature API: Abilities backend enabled but Abilities API not found. Please install the Abilities API plugin.' );
			return;
		}

		$this->register_abilities_as_features();
	}

	/**
	 * Checks if the abilities backend is enabled via configuration constant.
	 *
	 * @since 0.1.0
	 * @return bool True if abilities backend is enabled, false otherwise.
	 */
	private function is_abilities_backend_enabled() {
		return defined( 'WP_FEATURE_API_ABILITIES_BACKEND' ) && WP_FEATURE_API_ABILITIES_BACKEND;
	}

	/**
	 * Registers all abilities as features.
	 *
	 * Retrieves all registered abilities using wp_get_abilities(), converts
	 * each to feature format using convert_ability_to_feature(), and registers
	 * the converted features using wp_register_feature().
	 *
	 * @since 0.1.0
	 * @return void
	 */
	private function register_abilities_as_features() {
		$abilities = wp_get_abilities();

		foreach ( $abilities as $ability ) {
			try {
				$feature_args = $this->convert_ability_to_feature( $ability );
				if ( null !== $feature_args ) {
					wp_register_feature( $feature_args );
				}
			} catch ( Exception $e ) {
				error_log( 'WP Feature API: Failed to convert ability "' . $ability->get_name() . '" to feature: ' . $e->getMessage() );
				continue;
			}
		}
	}

	/**
	 * Converts an ability to feature format.
	 *
	 * Maps ability properties to feature properties according to the conversion schema:
	 * - name (ability) → id (feature) with 'ability/' prefix
	 * - label (ability) → name (feature)
	 * - description, input_schema, output_schema → direct mapping
	 * - execute_callback → callback (wrapped to call ability's execute method)
	 * - permission_callback → permission_callback (wrapped)
	 * - meta.type → type (default: 'tool')
	 * - meta.location → location (default: 'server')
	 * - meta.category → categories (converted to array)
	 * - meta → meta (direct mapping)
	 *
	 * @since 0.1.0
	 * @param WP_Ability $ability The ability to convert.
	 * @return array|null Feature arguments array, or null on conversion failure.
	 */
	private function convert_ability_to_feature( WP_Ability $ability ) {
		try {
			$meta = $ability->get_meta();

			$callback = function( $args ) use ( $ability ) {
				return $ability->execute( $args );
			};

			$permission_callback = function( $args ) use ( $ability ) {
				return $ability->has_permission( $args );
			};

			// Extract categories from meta - handle both string and array
			$categories = array();
			if ( isset( $meta['category'] ) ) {
				$categories = is_array( $meta['category'] ) ? $meta['category'] : array( $meta['category'] );
			} elseif ( isset( $meta['categories'] ) ) {
				$categories = is_array( $meta['categories'] ) ? $meta['categories'] : array( $meta['categories'] );
			}

			return array(
				'id'                  => 'ability/' . $ability->get_name(),
				'name'                => $ability->get_label(),
				'description'         => $ability->get_description(),
				'type'                => isset( $meta['type'] ) ? $meta['type'] : 'tool',
				'location'            => isset( $meta['location'] ) ? $meta['location'] : 'server',
				'categories'          => $categories,
				'callback'            => $callback,
				'permission_callback' => $permission_callback,
				'input_schema'        => $ability->get_input_schema(),
				'output_schema'       => $ability->get_output_schema(),
				'meta'                => $meta,
			);
		} catch ( Exception $e ) {
			error_log( 'WP Feature API: Failed to convert ability "' . $ability->get_name() . '" to feature: ' . $e->getMessage() );
			return null;
		}
	}

	/**
	 * Prevents cloning of the singleton instance.
	 *
	 * @since 0.1.0
	 */
	private function __clone() {
		// Prevent cloning.
	}

	/**
	 * Prevents unserialization of the singleton instance.
	 *
	 * @since 0.1.0
	 */
	public function __wakeup() {
		throw new Exception( 'Cannot unserialize singleton' );
	}
}
