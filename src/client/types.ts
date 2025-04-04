/**
 * Type definitions for WordPress Feature API
 */

/**
 * Valid feature types
 */
export type FeatureType = 'resource' | 'tool';

/**
 * Feature category type
 */
export type FeatureCategory = string;

/**
 * Feature interface with common properties
 */
export interface Feature {
	/**
	 * Unique identifier for the feature
	 */
	id: string;

	/**
	 * Display name of the feature
	 */
	name: string;

	/**
	 * Detailed description of what the feature does
	 */
	description: string;

	/**
	 * Type of the feature (resource or tool)
	 */
	type: FeatureType;

	/**
	 * Categories this feature belongs to
	 */
	categories: FeatureCategory[];

	/**
	 * Optional input schema for the feature
	 */
	input_schema?: Record< string, unknown >;

	/**
	 * Optional output schema for the feature
	 */
	output_schema?: Record< string, unknown >;

	/**
	 * Optional Callback function that implements the feature's functionality
	 * Can return any value or a Promise of any value
	 */
	callback?: () => unknown | Promise< unknown >;
}

/**
 * State shape for features store
 */
export interface FeaturesState {
	featuresById: Record< string, Feature >;
}
