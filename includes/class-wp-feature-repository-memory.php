<?php
/**
 * WP_Feature_Repository_Memory class file.
 *
 * @package WordPress\Features_API
 */

/**
 * Class WP_Feature_Repository_Memory
 *
 * An in-memory implementation of the WP_Feature_Repository_Interface.
 *
 * @since 0.1.0
 */
class WP_Feature_Repository_Memory implements WP_Feature_Repository_Interface {

	/**
	 * The features stored in memory.
	 *
	 * @since 0.1.0
	 * @var array
	 */
       private $features = array();

       /**
        * Embedding vectors keyed by feature ID.
        *
        * @since 0.1.0
        * @var array
        */
       private $embeddings = array();

	/**
	 * Saves a feature to the repository.
	 *
	 * @since 0.1.0
	 * @param WP_Feature $feature The feature to save.
	 * @return bool True if the feature was saved successfully, false otherwise.
	 */
	public function save( $feature ) {
		$feature = WP_Feature::make( $feature );

		if ( ! $feature ) {
			return false;
		}

		$this->features[ $feature->get_id() ] = $feature;

		return true;
	}

	/**
	 * Deletes a feature from the repository.
	 *
	 * @since 0.1.0
	 * @param string|WP_Feature $feature The feature ID or feature object to delete.
	 * @return bool True if the feature was deleted successfully, false otherwise.
	 */
	public function delete( $feature ) {
		$feature = WP_Feature::make( $feature );

		if ( ! $feature ) {
			return false;
		}

		if ( ! isset( $this->features[ $feature->get_id() ] ) ) {
			return false;
		}

		unset( $this->features[ $feature->get_id() ] );
		return true;
	}

	/**
	 * Finds a feature by its ID.
	 *
	 * @since 0.1.0
	 * @param string $feature_id The feature ID to find.
	 * @return WP_Feature|null The feature if found, null otherwise.
	 */
	public function find( $feature_id ) {
		if ( ! is_string( $feature_id ) ) {
			return null;
		}

		return isset( $this->features[ $feature_id ] ) ? $this->features[ $feature_id ] : null;
	}

	/**
	 * Queries features based on a query.
	 *
	 * @since 0.1.0
	 * @param WP_Feature_Query $query The query to filter features by.
	 * @return array The matching features.
	 */
       public function query( $query ) {
               $args = $query->get_args();

               if ( ! empty( $args['embedding'] ) ) {
                       $limit = isset( $args['limit'] ) ? (int) $args['limit'] : 5;
                       return $this->query_by_embedding( $args['embedding'], $limit );
               }

               $matches = array();
               foreach ( $this->features as $feature ) {
                       if ( $query->matches( $feature ) ) {
                               $matches[] = $feature;
                       }
               }
               return $matches;
       }

	/**
	 * Gets all features in the repository.
	 *
	 * @since 0.1.0
	 * @return array All features.
	 */
	public function get_all() {
		return array_values( $this->features );
	}

	/**
	 * Clears all features from the repository.
	 *
	 * @since 0.1.0
	 * @return void
	 */
       public function clear() {
               $this->features = array();
       }

       /**
        * Uses the native WP_Feature_Query class to filter features.
	 *
	 * @since 0.1.0
	 * @param WP_Feature_Query $query The query to check.
	 * @return bool Whether the repository can handle this query natively.
	 */
       public function supports_native_query( $query ) {
               return false;
       }

       /**
        * Stores an embedding vector for a feature.
        *
        * @since 0.1.0
        * @param string $feature_id The feature ID.
        * @param array  $vector     The embedding vector.
        * @return void
        */
       public function store_embedding( $feature_id, $vector ) {
               if ( is_string( $feature_id ) && is_array( $vector ) ) {
                       $this->embeddings[ $feature_id ] = $vector;
               }
       }

       /**
        * Retrieves the embedding vector for a feature.
        *
        * @since 0.1.0
        * @param string $feature_id The feature ID.
        * @return array|null The embedding vector or null.
        */
       public function get_embedding( $feature_id ) {
               return $this->embeddings[ $feature_id ] ?? null;
       }

       /**
        * Queries features by similarity to an embedding vector.
        *
        * Performs a cosine similarity match when embeddings are present. If no
        * embeddings are stored, falls back to an approximate text search.
        *
        * @since 0.1.0
        * @param array $vector Embedding vector to compare.
        * @param int   $limit  Optional. Max results to return.
        * @return array Matching features ordered by similarity.
        */
       public function query_by_embedding( $vector, $limit = 5 ) {
               if ( empty( $this->embeddings ) ) {
                       // Approximate by returning basic query results.
                       $query = new WP_Feature_Query();
                       $results = $this->query( $query );
                       return array_slice( $results, 0, $limit );
               }

               $scores = array();
               foreach ( $this->features as $feature_id => $feature ) {
                       if ( isset( $this->embeddings[ $feature_id ] ) ) {
                               $scores[ $feature_id ] = $this->cosine_similarity( $vector, $this->embeddings[ $feature_id ] );
                       }
               }

               arsort( $scores );
               $scores = array_slice( $scores, 0, $limit, true );

               $matches = array();
               foreach ( array_keys( $scores ) as $id ) {
                       $matches[] = $this->features[ $id ];
               }

               return $matches;
       }

       /**
        * Calculate cosine similarity between two vectors.
        *
        * @since 0.1.0
        * @param array $a Vector A.
        * @param array $b Vector B.
        * @return float Similarity score.
        */
       private function cosine_similarity( $a, $b ) {
               $dot = 0;
               $norm_a = 0;
               $norm_b = 0;

               $length = min( count( $a ), count( $b ) );
               for ( $i = 0; $i < $length; $i++ ) {
                       $dot     += $a[ $i ] * $b[ $i ];
                       $norm_a  += $a[ $i ] * $a[ $i ];
                       $norm_b  += $b[ $i ] * $b[ $i ];
               }

               if ( 0 === $norm_a || 0 === $norm_b ) {
                       return 0;
               }

               return $dot / ( sqrt( $norm_a ) * sqrt( $norm_b ) );
       }
}
