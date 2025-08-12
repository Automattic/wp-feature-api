/**
 * Configuration for Feature API client
 *
 * @since 0.1.0
 */

/**
 * Internal dependencies
 */
import { ENTITY_NAME } from './store/constants';

export interface FeatureAPIConfig {
	/**
	 * Whether to use the Abilities API backend for server features
	 */
	useAbilitiesBackend?: boolean;

	/**
	 * Entity name for fetching features/abilities
	 */
	entityName?: string;
}

// Default configuration
const defaultConfig: FeatureAPIConfig = {
	useAbilitiesBackend: false,
	entityName: ENTITY_NAME,
};

// Current configuration
let currentConfig: FeatureAPIConfig = { ...defaultConfig };

/**
 * Configure the Feature API client
 *
 * @param config Configuration options
 */
export function configure( config: Partial< FeatureAPIConfig > ): void {
	currentConfig = {
		...defaultConfig,
		...config,
	};

	// If using abilities backend, update entity name and paths
	if ( config.useAbilitiesBackend ) {
		currentConfig.entityName = 'abilities';

		// Register the abilities entity with core-data if not already registered
		const { dispatch, select } = ( window as any ).wp.data;
		const coreStore = ( window as any ).wp.coreData?.store;

		if ( coreStore && dispatch && select ) {
			// Check if abilities entity is already registered
			const entities =
				select( coreStore ).getEntitiesConfig( 'root' ) || [];
			const abilitiesEntityExists = entities.some(
				( entity: any ) => entity.name === 'abilities'
			);

			if ( ! abilitiesEntityExists ) {
				dispatch( coreStore ).addEntities( [
					{
						name: 'abilities',
						kind: 'root',
						baseURL: '/wp/v2/abilities',
						baseURLParams: { context: 'edit' },
						plural: 'abilities',
						label: 'Abilities',
						transientEdits: {
							callback: true,
						},
					},
				] );
			}
		}
	}
}

/**
 * Get current configuration
 *
 * @return Current configuration
 */
export function getConfig(): FeatureAPIConfig {
	return { ...currentConfig };
}

/**
 * Get the entity name for API calls
 *
 * @return Entity name (features or abilities)
 */
export function getEntityName(): string {
	return currentConfig.entityName || ENTITY_NAME;
}

/**
 * Get the run endpoint path for a feature/ability
 *
 * @param id Feature or ability ID
 * @return REST endpoint path
 */
export function getRunEndpoint( id: string ): string {
	const entityName = getEntityName();
	const basePath = '/wp/v2';

	// For abilities, strip the 'ability/' prefix if present
	const cleanId =
		currentConfig.useAbilitiesBackend && id.startsWith( 'ability/' )
			? id.replace( 'ability/', '' )
			: id;

	return `${ basePath }/${ entityName }/${ cleanId }/run`;
}

/**
 * Check if a feature should use the abilities backend
 *
 * @param feature Feature object
 * @return Whether to use abilities backend
 */
export function shouldUseAbilitiesBackend( feature: any ): boolean {
	// If abilities backend is enabled and the feature is server-side
	return (
		currentConfig.useAbilitiesBackend === true &&
		feature?.location === 'server'
	);
}
