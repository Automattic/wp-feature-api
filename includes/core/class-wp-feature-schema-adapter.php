<?php
/**
 * WP_Feature_Schema_Adapter class file.
 *
 * @package WordPress\Features_API
 */

/**
 * Class WP_Feature_Schema_Adapter
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
	 * The transformed schema.
	 *
	 * @since 0.1.0
	 * @var array
	 */
	private $transformed_schema;

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 * @param array $schema The schema to transform.
	 * @param array $rules  The transformation rules to apply.
	 */
	public function __construct( $schema, $rules = array() ) {
		$this->schema = $schema;
		/**
		 * OpenAI Schema Specification.
		 *
		 * @link https://platform.openai.com/docs/guides/structured-outputs?api-mode=responses#supported-schemas
		 */
		$this->rules = wp_parse_args(
			$rules,
			array(
				/**
				 * Array of supported types and their keywords.
				 */
				'supported_types'             => array(
					'any'     => array(
						'type',
						'enum',
						'const',
						'title',
						'description',
						'deprecated',
						'readOnly',
						'writeOnly',
						'examples',
						'properties',
						'items',
						'oneOf',
						// 'default',
					),
					'object'  => array(
						'required',
						'dependentRequired',
					),
					'string'  => array(),
					'number'  => array(),
					'integer' => array(),
					'boolean' => array(),
					'array'   => array(),
					'enum'    => array(),
					'anyOf'   => array(),
					// not included in docs, but it's implied by some examples.
					'null'    => array(),
				),
				/**
				 * Name: Strict object encoding
				 * Description: If the type is object, forces WordPress to encode the object as an empty object instead of an empty array. Also forces enums as arrays of keys rather than objects.
				 *
				 * @link https://core.trac.wordpress.org/ticket/63186
				 */
				'strict_object_encoding'      => true,
				/**
				 * Name: Root objects must not be anyOf
				 * Description: Note that the root level object of a schema must be an object, and not use anyOf.
				 */
				'no_any_of_for_root_objects'  => true,
				/**
				 * Name: All fields must be required
				 * Description: To use Structured Outputs, all fields or function parameters must be specified as required.
				 * Although all fields must be required (and the model will return a value for each parameter), it is possible to emulate an optional parameter by using a union type with null.
				 */
				'all_fields_required'         => false,
				/**
				 * Name: additionalProperties
				 * Description: false must always be set in objects additionalProperties controls whether it is allowable for an object to contain additional keys / values that were not defined in the JSON Schema.
				 * Structured Outputs only supports generating specified keys / values, so we require developers to set additionalProperties: false to opt into Structured Outputs.
				 */
				'additional_properties_false' => true,
				/**
				 * Name: Objects have limitations on nesting depth and size
				 * Description: A schema may have up to 100 object properties total, with up to 5 levels of nesting.
				 */
				'max_properties'              => 100,
				'max_depth'                   => 5,
				/**
				 * Name: Limitations on total string size
				 * Description: In a schema, total string length of all property names, definition names, enum values, and const values cannot exceed 15,000 characters.
				 */
				'max_chars'                   => 15000,
				/**
				 * Name: Limitations on enum size
				 * Description: A schema may have up to 500 enum values across all enum properties.
				 * For a single enum property with string values, the total string length of all enum values cannot exceed 7,500 characters when there are more than 250 enum values.
				 */
				'max_enum_values'             => 500,
			)
		);
	}

	/**
	 * Creates a new WP_Feature_Schema_Adapter instance.
	 *
	 * @since 0.1.0
	 * @param string|null $transformer_class The transformer class to use, must extend WP_Feature_Schema_Adapter.
	 * @param array       $schema The schema to transform.
	 * @param array       $rules The transformation rules to apply.
	 * @return WP_Feature_Schema_Adapter The new transformer instance.
	 */
	public static function make( $transformer_class, $schema, $rules = array() ) {
		if ( null !== $transformer_class ) {
			if ( ! is_string( $transformer_class ) ) {
				_doing_it_wrong(
					__METHOD__,
					sprintf(
						/* translators: %s: Transformer class name */
						__( 'The WP_Feature_Schema_Adapter subclass must be a string. Received: %s', 'wp-feature-api' ),
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
						__( 'The WP_Feature_Schema_Adapter subclass %s does not exist.', 'wp-feature-api' ),
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
						__( 'The WP_Feature_Schema_Adapter subclass %1$s must extend %2$s.', 'wp-feature-api' ),
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

		$this->transformed_schema = $this->schema;

		foreach ( array_keys( $this->rules ) as $rule_name ) {
			$rule_method = 'rule_' . $rule_name;
			// Execute the rule method if it exists and the rule is not explicitly set to false,
			// since rules can be true/false OR a value.
			if ( method_exists( $this, $rule_method ) && false !== $this->rules[ $rule_name ] ) {
				$this->transformed_schema = call_user_func( array( $this, $rule_method ), $rule_name, $this->transformed_schema );
			}
		}

		return $this->transformed_schema;
	}

	/**
	 * Rule methods
	 */
	/**
	 * Strips the unsupported properties from object properties.
	 *
	 * @since 0.1.0
	 * @param  string $rule_name The name of the rule.
	 * @param  array  $schema The data to strip unsupported properties from.
	 * @return array  The data with unsupported properties stripped.
	 */
	private function rule_supported_types( $rule_name, $schema ) {
		$supported_types = $this->rules[ $rule_name ];

		if ( ! is_array( $schema ) || empty( $supported_types ) ) {
			return $schema;
		}

		$type = 'any';
		if ( isset( $schema['type'] ) && is_string( $schema['type'] ) ) {
			$type = $schema['type'];
		}

		$allowed_props = isset( $supported_types[ $type ] )
			? array_unique( array_merge( $supported_types['any'], $supported_types[ $type ] ) )
			: $supported_types['any'];

		$filtered_data = array();
		foreach ( $schema as $key => $value ) {
			if ( 'properties' === $key && is_array( $value ) ) {
				$filtered_data[ $key ] = array();
				foreach ( $value as $prop_key => $prop_value ) {
					$filtered_data[ $key ][ $prop_key ] = $this->rule_supported_types( $rule_name, $prop_value );
				}
				continue;
			}

			if ( 'items' === $key && is_array( $value ) ) {
				$filtered_data[ $key ] = $this->rule_supported_types( $rule_name, $value );
				continue;
			}

			if ( in_array( $key, $allowed_props, true ) ) {
				$filtered_data[ $key ] = $value;
			}
		}

		return $filtered_data;
	}

	/**
	 * Enforces object properties include 'additionalProperties' set to false.
	 *
	 * @since 0.1.0
	 * @param array $rule_name The name of the rule.
	 * @param array $data The data to ensure properties for.
	 * @return array The data with required properties.
	 */
	private function rule_additional_properties_false( $rule_name, $data ) {
		$additional_properties_false = $this->rules[ $rule_name ];
		if ( ! is_array( $data ) || false === $additional_properties_false ) {
			return $data;
		}

		if ( isset( $data['properties'] ) ) {
			$data['additionalProperties'] = false;
		}

		// Recursively process nested properties.
		if ( isset( $data['properties'] ) && is_array( $data['properties'] ) ) {
			foreach ( $data['properties'] as $property_key => $property_value ) {
				$data['properties'][ $property_key ] = $this->rule_additional_properties_false( $rule_name, $property_value );
			}
		}

		// Process array items.
		if ( isset( $data['items'] ) && is_array( $data['items'] ) ) {
			$data['items'] = $this->rule_additional_properties_false( $rule_name, $data['items'] );
		}

		return $data;
	}

	/**
	 * Enforces better compliance with JSON Schema
	 * of some data types.
	 *
	 * @since 0.1.0
	 * @param array $rule_name The rule value.
	 * @param array $data The data to encode.
	 * @return array The encoded data.
	 */
	private function rule_strict_object_encoding( $rule_name, $data ) {
		if ( ! is_array( $data ) || false === $this->rules[ $rule_name ] ) {
			return $data;
		}

		foreach ( $data as $key => $value ) {
			if ( ! is_array( $value ) ) {
				continue;
			}

			/**
			 * Handle oneOf multiple types
			 *
			 * @todo: This is a cheap shortcut to shut-off 'oneOf' for now.
			 */
			if (
				isset( $value['oneOf'] ) &&
				is_array( $value['oneOf'] )
			) {
				$value['type']  = 'array';
				$value['items'] = $value['oneOf'][0];
				unset( $value['oneOf'] );
			}

			/**
			 * Handle enum objects
			 */
			if ( isset( $value['enum'] ) ) {
				$value['enum'] = array_values( $value['enum'] );
			}

			// Handle empty object properties.
			if (
				isset( $value['type'] ) &&
				isset( $value['properties'] ) &&
				'object' === $value['type'] &&
				empty( $value['properties'] )
			) {
				$value['properties'] = new \stdClass();
			} else {
				$value = $this->rule_strict_object_encoding( $rule_name, $value );
			}

			$data[ $key ] = $value;
		}

		return $data;
	}

	/**
	 * Processes and adds required properties to a schema object recursively.
	 *
	 * @since 0.1.0
	 * @param array $rule_name The rule value.
	 * @param array $data The schema data to process.
	 * @return array The processed schema data.
	 */
	private function rule_all_fields_required( $rule_name, $data ) {
		if ( ! is_array( $data ) || false === $rule_name ) {
			return $data;
		}

		if ( isset( $data['type'] ) && isset( $data['properties'] ) && 'object' === $data['type'] ) {
			$required_properties = array();

			foreach ( $data['properties'] as $property_key => $property_value ) {
				// Process nested objects/arrays recursively.
				$data['properties'][ $property_key ] = $this->rule_all_fields_required( $rule_name, $property_value );

				// Handle required flag.
				if ( isset( $property_value['required'] ) ) {
					if ( true === $property_value['required'] ) {
						$required_properties[] = $property_key;
					}
					// Remove the required flag as it's not part of JSON Schema.
					unset( $data['properties'][ $property_key ]['required'] );
				} else {
					// Make non-required properties nullable.
					$current_type                                = isset( $property_value['type'] ) ? $property_value['type'] : 'string';
					$types                                       = is_array( $current_type ) ? $current_type : array( $current_type );
					$types[]                                     = 'null';
					$data['properties'][ $property_key ]['type'] = array_values( array_unique( $types ) );
				}
			}

			$data['required'] = is_array( $data['properties'] ) ? array_keys( $data['properties'] ) : array();
		}

		// Handle arrays.
		if ( isset( $data['type'] ) && 'array' === $data['type'] && isset( $data['items'] ) ) {
			$data['items'] = $this->rule_all_fields_required( $rule_name, $data['items'] );
		}

		return $data;
	}

	/**
	 * Enforces maximum number of object properties.
	 *
	 * @since 0.1.0
	 * @param int   $max_properties Maximum allowed properties.
	 * @param array $data The schema data to process.
	 * @return array The processed schema data.
	 * @throws Exception If max properties limit is exceeded.
	 */
	private function rule_max_properties( $max_properties, $data ) {
		if ( ! is_array( $data ) ) {
			return $data;
		}

		$total_properties = 0;
		$this->count_properties( $data, $total_properties );

		if ( $total_properties > $max_properties ) {
			throw new Exception(
				sprintf(
					/* translators: %d: Maximum number of properties allowed */
					esc_html__( 'Schema exceeds maximum allowed properties (%d)', 'wp-feature-api' ),
					esc_html( $max_properties )
				)
			);
		}

		return $data;
	}

	/**
	 * Enforces maximum nesting depth.
	 *
	 * @since 0.1.0
	 * @param string $rule_name The rule name.
	 * @param array  $data The schema data to process.
	 * @return array The processed schema data.
	 * @throws Exception If max depth limit is exceeded.
	 */
	private function rule_max_depth( $rule_name, $data ) {
		$max_depth = $this->rules[ $rule_name ];
		if ( ! is_array( $data ) ) {
			return $data;
		}

		// Instead of throwing an error, ensure the data has the maximum allowed depth.
		return $this->enforce_max_depth( $data, $max_depth );
	}

	/**
	 * Enforces maximum nesting depth by truncating the data structure.
	 *
	 * @since 0.1.0
	 * @param array $data The schema data to process.
	 * @param int   $max_depth Maximum allowed nesting depth.
	 * @param int   $current_depth Current depth in recursion.
	 * @return array The processed schema data with enforced maximum depth.
	 */
	private function enforce_max_depth( $data, $max_depth, $current_depth = 0 ) {
		if ( ! is_array( $data ) || $current_depth >= $max_depth ) {
			return $data;
		}

		$result = array();
		foreach ( $data as $key => $value ) {
			if ( is_array( $value ) ) {
				$result[ $key ] = $this->enforce_max_depth( $value, $max_depth, $current_depth + 1 );
			} else {
				$result[ $key ] = $value;
			}
		}

		return $result;
	}

	/**
	 * Enforces maximum total string length
	 *
	 * @since 0.1.0
	 * @param  string $rule_name The rule name.
	 * @param  array  $data The schema data to process.
	 * @return array  The processed schema data.
	 * @throws Exception If max characters limit is exceeded.
	 */
	private function rule_max_chars( $rule_name, $data ) {
		$max_chars = $this->rules[ $rule_name ];
		if ( is_int( $max_chars ) ) {
			$length = strlen( wp_json_encode( $this->transformed_schema ) );
			if ( $length > $max_chars ) {
				throw new Exception(
					sprintf(
						/* translators: %d: Maximum characters allowed */
						esc_html__( 'Schema exceeds maximum allowed characters (%d)', 'wp-feature-api' ),
						esc_html( $max_chars )
					)
				);
			}
		}

		return $data;
	}

	/**
	 * Enforces maximum number of enum values.
	 *
	 * @since 0.1.0
	 * @param  string $rule_name The rule name.
	 * @param  array  $data The schema data to process.
	 * @return array  The processed schema data.
	 * @throws Exception If max enum values limit is exceeded.
	 */
	private function rule_max_enum_values( $rule_name, $data ) {
		$max_enum_values = $this->rules[ $rule_name ];
		if ( ! is_array( $data ) ) {
			return $data;
		}

		$total_enum_values = 0;
		$this->count_enum_values( $data, $total_enum_values );

		if ( $total_enum_values > $max_enum_values ) {
			throw new Exception(
				sprintf(
					/* translators: %d: Maximum enum values allowed */
					esc_html__( 'Schema exceeds maximum allowed enum values (%d)', 'wp-feature-api' ),
					esc_html( $max_enum_values )
				)
			);
		}

		// Check individual enum string length limit.
		if ( isset( $data['enum'] ) && count( $data['enum'] ) > 250 ) {
			$total_length = 0;
			foreach ( $data['enum'] as $enum_value ) {
				if ( is_string( $enum_value ) ) {
					$total_length += strlen( $enum_value );
				}
			}

			if ( $total_length > 7500 ) {
				throw new Exception(
					esc_html__( 'Total string length of enum values exceeds 7,500 characters for enums with more than 250 values', 'wp-feature-api' )
				);
			}
		}

		foreach ( $data as $key => $value ) {
			if ( is_array( $value ) ) {
				$data[ $key ] = $this->rule_max_enum_values( $rule_name, $value );
			}
		}

		return $data;
	}

	/**
	 * Ensures root objects are not using anyOf.
	 *
	 * @since 0.1.0
	 * @param bool  $rule_name The rule value.
	 * @param array $data The schema data to process.
	 * @return array The processed schema data.
	 * @throws Exception If root object uses anyOf.
	 */
	private function rule_no_any_of_for_root_objects( $rule_name, $data ) {
		if ( ! is_array( $data ) || ! $this->rules[ $rule_name ] ) {
			return $data;
		}

		if ( isset( $data['anyOf'] ) ) {
			throw new Exception(
				esc_html__( 'Root level objects must not use anyOf', 'wp-feature-api' )
			);
		}

		return $data;
	}

	/**
	 * Utils
	 */

	/**
	 * Helper function to count total enum values.
	 *
	 * @param array $data The schema data.
	 * @param int   &$count Reference to the running count.
	 */
	private function count_enum_values( $data, &$count ) {
		if ( isset( $data['enum'] ) && is_array( $data['enum'] ) ) {
			$count += count( $data['enum'] );
		}

		foreach ( $data as $value ) {
			if ( is_array( $value ) ) {
				$this->count_enum_values( $value, $count );
			}
		}
	}

	/**
	 * Helper function to count total properties in schema.
	 *
	 * @param array $data The schema data.
	 * @param int   &$count Reference to the running count.
	 */
	private function count_properties( $data, &$count ) {
		if ( isset( $data['properties'] ) && is_array( $data['properties'] ) ) {
			$count += count( $data['properties'] );
			foreach ( $data['properties'] as $property ) {
				if ( is_array( $property ) ) {
					$this->count_properties( $property, $count );
				}
			}
		}
	}

	/**
	 * Helper function to get maximum nesting depth of schema.
	 *
	 * @param array $data The schema data.
	 * @param int   $current_depth Current depth in recursion.
	 * @return int The maximum depth found.
	 */
	private function get_max_depth( $data, $current_depth = 0 ) {
		if ( ! is_array( $data ) ) {
			return $current_depth;
		}

		$max_depth = $current_depth;
		foreach ( $data as $value ) {
			if ( is_array( $value ) ) {
				$depth     = $this->get_max_depth( $value, $current_depth + 1 );
				$max_depth = max( $max_depth, $depth );
			}
		}

		return $max_depth;
	}
}
