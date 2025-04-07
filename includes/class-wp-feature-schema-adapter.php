<?php
/**
 * WP_Feature_Schema_Transformer class file.
 *
 * @package WordPress\Features_API
 */

/**
 * Class WP_Feature_Schema_Transformer
 *
 * Handles transformation of JSON schemas into OpenAI-compatible versions.
 * To handle other provider JSON Schema specifications, create a subclass and override the `transform` method.
 *
 * @since 0.1.0
 */
class WP_Feature_Schema_Adapter {
	/**
	 * The schema to transform.
	 *
	 * @since 0.1.0
	 * @var array
	 */
	private $schema;

	/**
	 * The transformation rules to apply.
	 *
	 * @since 0.1.0
	 * @var array
	 */
	private $rules;

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 * @param array $schema The schema to transform.
	 * @param array $rules  The transformation rules to apply.
	 */
	public function __construct( $schema, $rules = array() ) {
		$this->schema = $schema;
		$this->rules = wp_parse_args(
			$rules,
			array(
				'remove_unsupported_properties' => array(
					'format',
					'validate_callback',
					'sanitize_callback',
					'default',
				),
				'add_required_properties' => array( $this, 'process_required_properties' ),
				'strict_object_encoding' => true,
			)
		);
	}

	/**
	 * Creates a new WP_Feature_Schema_Transformer instance.
	 *
	 * @since 0.1.0
	 * @param string|null $transformer_class The transformer class to use, must extend WP_Feature_Schema_Transformer.
	 * @param array       $schema The schema to transform.
	 * @param array       $rules The transformation rules to apply.
	 * @return WP_Feature_Schema_Transformer The new transformer instance.
	 */
	public static function make( $transformer_class, $schema, $rules = array() ) {
		if ( null !== $transformer_class ) {
			if ( ! is_string( $transformer_class ) ) {
				_doing_it_wrong(
					__METHOD__,
					sprintf(
						/* translators: %s: Transformer class name */
						__( 'The WP_Feature_Schema_Transformer subclass must be a string. Received: %s', 'wp-feature-api' ),
						gettype( $transformer_class )
					),
					'0.1.0'
				);
				return new self( $schema, $rules );
			}

			if ( ! class_exists( $transformer_class ) ) {
				_doing_it_wrong(
					__METHOD__,
					sprintf(
						/* translators: %s: Transformer class name */
						__( 'The WP_Feature_Schema_Transformer subclass %s does not exist.', 'wp-feature-api' ),
						$transformer_class
					),
					'0.1.0'
				);
				return new self( $schema, $rules );
			}

			if ( ! is_subclass_of( $transformer_class, __CLASS__ ) ) {
				_doing_it_wrong(
					__METHOD__,
					sprintf(
						/* translators: %1$s: Transformer class name, %2$s: Parent class name */
						__( 'The WP_Feature_Schema_Transformer subclass %1$s must extend %2$s.', 'wp-feature-api' ),
						$transformer_class,
						__CLASS__
					),
					'0.1.0'
				);
				return new self( $schema, $rules );
			}

			return new $transformer_class( $schema, $rules );
		}

		return new self( $schema, $rules );
	}

	/**
	 * Transforms the schema according to the configured rules.
	 *
	 * @since 0.1.0
	 * @return array The transformed schema.
	 */
	public function transform() {
		if ( ! is_array( $this->schema ) ) {
			return $this->schema;
		}

		$transformed = $this->schema;

		if ( $this->rules['remove_unsupported_properties'] ) {
			$transformed = $this->remove_unsupported_properties( $transformed );
		}

		if ( $this->rules['add_required_properties'] ) {
			$transformed = $this->add_required_properties( $transformed );
		}

		if ( $this->rules['strict_object_encoding'] ) {
			$transformed = $this->strict_object_encoding( $transformed );
		}

		return $transformed;
	}

	/**
	 * Strips the unsupported properties from object properties.
	 *
	 * @since 0.1.0
	 * @param array $data The data to strip unsupported properties from.
	 * @return array The data with unsupported properties stripped.
	 */
	private function remove_unsupported_properties( $data ) {
		if ( ! is_array( $data ) ) {
			return $data;
		}

		// Remove unsupported properties from the current object.
		foreach ( $this->rules['remove_unsupported_properties'] as $unsupported_prop ) {
			unset( $data[ $unsupported_prop ] );
		}

		// Recursively process nested structures.
		foreach ( $data as $key => $value ) {
			if ( is_array( $value ) ) {
				$data[ $key ] = $this->remove_unsupported_properties( $value );
			}
		}

		return $data;
	}

	/**
	 * Ensures required object properties include 'additionalProperties' set to false.
	 *
	 * @since 0.1.0
	 * @param array $data The data to ensure properties for.
	 * @return array The data with required properties.
	 */
	private function add_required_properties( $data ) {
		if ( ! is_array( $data ) ) {
			return $data;
		}

		// Add required properties to the current object.
		if ( isset( $data['properties'] ) ) {
			$data = call_user_func( $this->rules['add_required_properties'], $data );
		}

		// Recursively process nested structures.
		foreach ( $data as $key => $value ) {
			if ( is_array( $value ) ) {
				$data[ $key ] = $this->add_required_properties( $value );
			}
		}

		return $data;
	}

	/**
	 * Enforces actual objects for empty arrays of type 'object'.
	 *
	 * @since 0.1.0
	 * @param array $data The data to encode.
	 * @return array The encoded data.
	 */
	private function strict_object_encoding( $data ) {
		if ( ! is_array( $data ) ) {
			return $data;
		}

		foreach ( $data as $key => $value ) {
			if ( ! is_array( $value ) ) {
				continue;
			}

			if (
				isset( $value['type'] ) &&
				isset( $value['properties'] ) &&
				'object' === $value['type'] &&
				empty( $value['properties'] )
			) {
				$value['properties'] = new \stdClass();
			} else {
				$value = $this->strict_object_encoding( $value );
			}

			$data[ $key ] = $value;
		}

		return $data;
	}

	/**
	 * Processes and adds required properties to a schema object.
	 *
	 * @since 0.1.0
	 * @param array $data The schema data to process.
	 * @return array The processed schema data.
	 */
	private function process_required_properties( $data ) {
		if ( ! isset( $data['properties'] ) ) {
			return $data;
		}

		$required = array_keys( $data['properties'] );
		foreach ( $data['properties'] as $property_key => $property_value ) {
			if ( isset( $property_value['required'] ) && ! $property_value['required'] ) {
				unset( $required[ $property_key ] );
			}
			unset( $data['properties'][ $property_key ]['required'] );
		}

		$data['additionalProperties'] = false;
		$data['required'] = $required;

		return $data;
	}
}
