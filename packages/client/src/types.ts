/**
 * WordPress dependencies
 */
import type { StoreDescriptor } from '@wordpress/data';

type SelectFunction = ( storeName: string | StoreDescriptor ) => any;
type DispatchFunction = ( storeName: string | StoreDescriptor ) => any;

export interface Feature {
	id: string;
	name: string;
	description: string;
	type: 'resource' | 'tool';
	meta?: Record< string, any >;
	categories: string[];
	input_schema?: Record< string, any >;
	output_schema?: Record< string, any >;
	location: 'server' | 'client';
	icon?: any;
	is_eligible?: () => boolean;
	callback?: (
		args: any,
		context: {
			data: { dispatch: DispatchFunction; select: SelectFunction };
		}
	) => unknown | Promise< unknown >;
}

export interface FeaturesState {
	featuresById: Record< string, Feature >;
	featureInputInProgressId: string | null;
}

// Declare global variables provided by WordPress
// Currently used for the navigate feature, but we may want to handle this a different way
declare global {
	const ajaxurl: string | undefined;
}
