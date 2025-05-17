<?php
/**
 * WP_Feature_Group class file.
 *
 * @package WordPress\Features_API
 */

/**
 * Class WP_Feature_Group
 *
 * Represents a set of features in the WordPress Feature API.
 *
 * @since 0.1.0
 */
class WP_Feature_Group {

    /**
     * Group slug.
     *
     * @since 0.1.0
     * @var string
     */
    private $slug;

    /**
     * Group name.
     *
     * @since 0.1.0
     * @var string
     */
    private $name;

    /**
     * Group description.
     *
     * @since 0.1.0
     * @var string
     */
    private $description = '';

    /**
     * Feature IDs that belong to this group.
     *
     * @since 0.1.0
     * @var array
     */
    private $features = array();

    /**
     * Constructor.
     *
     * @since 0.1.0
     * @param string|array $group Group data.
     */
    public function __construct( $group ) {
        if ( is_string( $group ) ) {
            $this->from_string( $group );
        } elseif ( is_array( $group ) ) {
            $this->from_array( $group );
        }
    }

    /**
     * Creates a group instance.
     *
     * @since 0.1.0
     * @param string|array|WP_Feature_Group $group Group data.
     * @return WP_Feature_Group|null Group instance or null on failure.
     */
    public static function make( $group ) {
        if ( $group instanceof WP_Feature_Group ) {
            return $group;
        }

        if ( is_string( $group ) || is_array( $group ) ) {
            return new self( $group );
        }

        return null;
    }

    /**
     * Gets the group slug.
     *
     * @since 0.1.0
     * @return string
     */
    public function get_slug() {
        return $this->slug;
    }

    /**
     * Gets the group name.
     *
     * @since 0.1.0
     * @return string
     */
    public function get_name() {
        return $this->name;
    }

    /**
     * Gets the group description.
     *
     * @since 0.1.0
     * @return string
     */
    public function get_description() {
        return $this->description;
    }

    /**
     * Gets feature IDs for this group.
     *
     * @since 0.1.0
     * @return array
     */
    public function get_features() {
        return $this->features;
    }

    /**
     * Converts the group to an array.
     *
     * @since 0.1.0
     * @return array
     */
    public function to_array() {
        return array(
            'slug'        => $this->slug,
            'name'        => $this->name,
            'description' => $this->description,
            'features'    => $this->features,
        );
    }

    /**
     * Creates a group instance from a string.
     *
     * @since 0.1.0
     * @param string $group Group slug.
     */
    private function from_string( $group ) {
        $this->slug        = sanitize_key( $group );
        $this->name        = ucwords( str_replace( array( '-', '_' ), ' ', $this->slug ) );
        $this->description = '';
        $this->features    = array();
    }

    /**
     * Creates a group instance from an array.
     *
     * @since 0.1.0
     * @param array $group Group data.
     */
    private function from_array( $group ) {
        if ( ! isset( $group['id'] ) ) {
            return;
        }

        $this->slug        = sanitize_key( $group['id'] );
        $this->name        = isset( $group['name'] ) ? sanitize_text_field( $group['name'] ) : ucwords( str_replace( array( '-', '_' ), ' ', $this->slug ) );
        $this->description = isset( $group['description'] ) ? sanitize_text_field( $group['description'] ) : '';
        $this->features    = isset( $group['features'] ) && is_array( $group['features'] ) ? array_values( $group['features'] ) : array();
    }
}
