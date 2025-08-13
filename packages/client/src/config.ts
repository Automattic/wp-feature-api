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
	 * Backend to use for server features ('features' or 'abilities')
	 */
	backend: 'features' | 'abilities';

	/**
	 * Entity name for fetching features/abilities
	 */
	entityName: string;
}

declare global {
	interface Window {
		wpFeatureAPIConfig?: {
			backend?: string;
		};
		wp?: any;
	}
}

const backend =
	window.wpFeatureAPIConfig?.backend === 'abilities'
		? 'abilities'
		: 'features';

// Set configuration once at module load
const currentConfig: FeatureAPIConfig = {
	backend,
	entityName: backend === 'abilities' ? 'abilities' : ENTITY_NAME,
};

// Register abilities entity with core-data if using abilities backend
// wp-data should be available since it's a dependency of wp-features
if ( backend === 'abilities' ) {
	registerAbilitiesEntity();
}

function registerAbilitiesEntity() {
	const { dispatch, select } = ( window as any ).wp.data;
	const coreStore = ( window as any ).wp.coreData?.store;

	if ( coreStore && dispatch && select ) {
		const entities = select( coreStore ).getEntitiesConfig( 'root' ) || [];
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
				},
			] );
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
		currentConfig.backend === 'abilities' && id.startsWith( 'ability/' )
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
	// If abilities backend is enabled and the feature is server-side or has ability prefix
	return (
		currentConfig.backend === 'abilities' &&
		( feature?.location === 'server' ||
			feature?.id?.startsWith( 'ability/' ) )
	);
}
