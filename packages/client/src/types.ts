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
	callback?: ( args: any ) => unknown | Promise< unknown >;
}

export interface FeaturesState {
	featuresById: Record< string, Feature >;
}
