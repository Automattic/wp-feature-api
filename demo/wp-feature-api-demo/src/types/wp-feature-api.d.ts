declare module '@wp-feature-api/client' {
  export interface Feature {
    id: string;
    name: string;
    description: string;
    type: 'resource' | 'tool';
    meta?: Record<string, any>;
    categories: string[];
    input_schema: Record<string, any>;
    output_schema?: Record<string, any>;
    location: 'server' | 'client';
    callback?: (args?: any) => unknown | Promise<unknown>;
  }

  export interface FeaturesState {
    featuresById: Record<string, Feature>;
  }

  export function registerFeature(feature: Feature): void;
  export function unregisterFeature(featureId: string): void;
  export function executeFeature(featureId: string, args: any): Promise<unknown>;

  export const store: any;
}

declare module '@wp-feature-api/client-features' {
  export function registerCoreFeatures(): void;
  export const coreFeatures: any[];
}