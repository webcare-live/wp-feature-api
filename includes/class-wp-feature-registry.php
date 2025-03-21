<?php
/**
 * WP_Feature_Registry class file.
 *
 * @package WordPress\Features_API
 */

/**
 * Class WP_Feature_Registry
 *
 * A singleton registry for WordPress features.
 *
 * @since 0.1.0
 */
class WP_Feature_Registry {

	/**
	 * The singleton instance of the registry.
	 *
	 * @since 0.1.0
	 * @var WP_Feature_Registry
	 */
	private static $instance = null;

	/**
	 * The feature repository.
	 *
	 * @since 0.1.0
	 * @var WP_Feature_Repository_Interface
	 */
	private $repository = null;

	/**
	 * In-memory cache of fetched features.
	 * Caches IDs only. Features are fetched from the repository when needed.
	 *
	 * @since 0.1.0
	 * @var array
	 */
	private $feature_cache = array();

	/**
	 * Private constructor to prevent direct instantiation.
	 * Sets the repository to use for the registry.
	 *
	 * @since 0.1.0
	 */
	private function __construct() {
		require_once WP_FEATURE_API_PLUGIN_DIR . 'includes/interface-wp-feature-repository.php';
		require_once WP_FEATURE_API_PLUGIN_DIR . 'includes/class-wp-feature-repository-memory.php';

		$default_repository = new WP_Feature_Repository_Memory();
		$repository = apply_filters( 'wp_feature_repository', $default_repository );

		if ( ! $repository instanceof WP_Feature_Repository_Interface ) {
			_doing_it_wrong(
				__FUNCTION__,
				sprintf(
					/* translators: %s: WP_Feature_Repository_Interface */
					__( 'The repository must implement %s. Falling back to default repository.', 'wp-feature-api' ),
					'WP_Feature_Repository_Interface'
				),
				'0.1.0'
			);
			$repository = $default_repository;
		}

		$this->repository = $repository;
	}

	/**
	 * Gets the singleton instance of the registry.
	 *
	 * @since 0.1.0
	 * @return WP_Feature_Registry The singleton instance.
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Registers a feature.
	 *
	 * @since 0.1.0
	 * @param WP_Feature|array $feature The feature to register.
	 * @return bool True if the feature was registered, false otherwise.
	 */
	public function register( $feature ) {

		// Convert array to WP_Feature if necessary.
		if ( is_array( $feature ) ) {
			if ( ! isset( $feature['id'] ) ) {
				return false;
			}

			$feature = new WP_Feature( $feature['id'], $feature );
		}

		// Ensure the feature is a WP_Feature instance.
		if ( ! $feature instanceof WP_Feature ) {
			return false;
		}

		// Get the feature ID.
		$feature_id = $feature->get_id();

		// Check if the feature is already registered in the repository.
		if ( $this->repository->find( $feature_id ) ) {
			return false;
		}

		// Save the feature to the repository.
		$saved = $this->repository->save( $feature );

		if ( ! $saved ) {
			return false;
		}

		// Update the cache if we're successful.
		if ( ! in_array( $feature_id, $this->feature_cache, true ) ) {
			$this->feature_cache[] = $feature_id;
		}

		/**
		 * Fires after a feature is registered.
		 *
		 * @since 0.1.0
		 * @param WP_Feature $feature The registered feature.
		 */
		do_action( 'wp_feature_registered', $feature );

		return true;
	}

	/**
	 * Unregisters a feature.
	 *
	 * @since 0.1.0
	 * @param string|WP_Feature $feature The feature ID or feature object to unregister.
	 * @return bool True if the feature was unregistered, false otherwise.
	 */
	public function unregister( $feature ) {
		// Get the feature ID.
		$feature_id = $feature instanceof WP_Feature ? $feature->get_id() : $feature;

		// Check if the feature exists in the repository.
		$feature_obj = $this->repository->find( $feature_id );

		if ( ! $feature_obj ) {
			return false;
		}

		// Remove the feature from the repository.
		$deleted = $this->repository->delete( $feature_id );

		if ( ! $deleted ) {
			return false;
		}

		// Clear the cache entry.
		if ( isset( $this->feature_cache[ $feature_id ] ) ) {
			unset( $this->feature_cache[ $feature_id ] );
		}

		/**
		 * Fires after a feature is unregistered.
		 *
		 * @since 0.1.0
		 * @param string    $feature_id The feature ID.
		 * @param WP_Feature $feature    The unregistered feature.
		 */
		do_action( 'wp_feature_unregistered', $feature_id, $feature_obj );

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
		// Check if the feature is in the cache.
		if ( isset( $this->feature_cache[ $feature_id ] ) ) {
			return $this->feature_cache[ $feature_id ];
		}

		// Check the repository.
		$feature = $this->repository->find( $feature_id );

		if ( $feature ) {
			// Cache the feature.
			$this->feature_cache[ $feature_id ] = $feature;
		}

		return $feature;
	}

	/**
	 * Gets features based on a query.
	 *
	 * @since 0.1.0
	 * @param WP_Feature_Query|array|null $query The query to filter features by, or null to get all features.
	 * @return array The matching features.
	 */
	public function get( $query = null ) {
		// If no query is provided, get all features from the repository.
		if ( null === $query ) {
			$features = $this->repository->get_all();

			// Update the cache with all features.
			foreach ( $features as $feature ) {
				$this->feature_cache[ $feature->get_id() ] = $feature;
			}

			return $features;
		}

		// Convert array to WP_Feature_Query if necessary.
		if ( is_array( $query ) ) {
			$query = new WP_Feature_Query( $query );
		}

		// Ensure the query is a WP_Feature_Query instance.
		if ( ! $query instanceof WP_Feature_Query ) {
			return array();
		}

		// Query the repository.
		$features = $this->repository->query( $query );

		// Update the cache with the results.
		foreach ( $features as $feature ) {
			$this->feature_cache[ $feature->get_id() ] = $feature;
		}

		return $features;
	}

	/**
	 * Clears the feature cache.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function clear_cache() {
		$this->feature_cache = array();
	}
}
