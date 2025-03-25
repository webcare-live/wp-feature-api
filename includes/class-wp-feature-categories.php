<?php
/**
 * WP_Feature_Categories class file.
 *
 * @package WordPress\Features_API
 */

/**
 * Class WP_Feature_Categories
 *
 * Manages a collection of feature categories.
 *
 * @since 0.1.0
 */
final class WP_Feature_Categories {

	/**
	 * Array of category objects.
	 *
	 * @since 0.1.0
	 * @var array
	 */
	private $categories = array();

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 */
	public function __construct() {
		$this->register_default_categories();
	}

	/**
	 * Gets all categories.
	 *
	 * @since 0.1.0
	 * @return array Array of WP_Feature_Category objects.
	 */
	public function get_all() {
		return $this->categories;
	}

	/**
	 * Gets a category by slug.
	 *
	 * @since 0.1.0
	 * @param string $slug Category slug.
	 * @return WP_Feature_Category|null Category object or null if not found.
	 */
	public function get( $slug ) {
		return isset( $this->categories[ $slug ] ) ? $this->categories[ $slug ] : null;
	}

	/**
	 * Adds or updates a category.
	 *
	 * @since 0.1.0
	 * @param string|array|WP_Feature_Category $category Category data.
	 * @return WP_Feature_Category|null Category object or null on failure.
	 */
	public function add( $category ) {
		$category_obj = WP_Feature_Category::make( $category );

		if ( ! $category_obj ) {
			return null;
		}

		$slug = $category_obj->get_slug();

		if ( isset( $this->categories[ $slug ] ) ) {
			// Update existing category.
			$existing = $this->categories[ $slug ];
			if ( ! empty( $category_obj->get_name() ) ) {
				$existing->set_name( $category_obj->get_name() );
			}
			if ( ! empty( $category_obj->get_description() ) ) {
				$existing->set_description( $category_obj->get_description() );
			}

			$category_obj = $existing;
		}

		$this->categories[ $slug ] = $category_obj;
		$this->categories[ $slug ]->increment_count();

		return $this->categories[ $slug ];
	}

	/**
	 * Removes a category.
	 *
	 * @since 0.1.0
	 * @param string $slug Category slug.
	 * @return bool True if category was removed, false otherwise.
	 */
	public function remove( $slug ) {
		$category_obj = WP_Feature_Category::make( $slug );
		$slug = $category_obj->get_slug();

		if ( ! isset( $this->categories[ $slug ] ) ) {
			return false;
		}

		$existing = $this->categories[ $slug ];
		$existing->decrement_count();
		if ( 0 === $existing->get_feature_count() ) {
			unset( $this->categories[ $slug ] );
		}

		return true;
	}

	/**
	 * Updates categories for a feature.
	 *
	 * @since 0.1.0
	 * @param WP_Feature $feature Feature object.
	 * @return void
	 */
	public function update_for_feature( $feature ) {
		if ( ! $feature instanceof WP_Feature ) {
			_doing_it_wrong(
				__METHOD__,
				sprintf(
					/* translators: %s: WP_Feature */
					__( 'The feature must be an instance of %s.', 'wp-feature-api' ),
					'WP_Feature'
				),
				'0.1.0'
			);
		}

		$feature_categories = $feature->get_categories();

		if ( empty( $feature_categories ) ) {
			return;
		}

		/**
		 * Filters the categories before they are registered.
		 *
		 * @since 0.1.0
		 * @param array      $feature_categories The categories to be registered.
		 * @param WP_Feature $feature           The feature being registered.
		 */
		$feature_categories = apply_filters( 'wp_feature_pre_register_categories', $feature_categories, $feature );

		foreach ( $feature_categories as $category ) {
			$this->add( $category );
		}
	}

	/**
	 * Removes categories for a feature.
	 *
	 * @since 0.1.0
	 * @param WP_Feature $feature Feature object.
	 * @return void
	 */
	public function remove_for_feature( $feature ) {
		if ( ! $feature instanceof WP_Feature ) {
			_doing_it_wrong(
				__METHOD__,
				sprintf(
					/* translators: %s: WP_Feature */
					__( 'The feature must be an instance of %s.', 'wp-feature-api' ),
					'WP_Feature'
				),
				'0.1.0'
			);
		}

		$feature_categories = $feature->get_categories();

		if ( empty( $feature_categories ) ) {
			return;
		}

		foreach ( $feature_categories as $category ) {
			$this->remove( $category );
		}
	}

	/**
	 * Registers default categories.
	 *
	 * @since 0.1.0
	 */
	private function register_default_categories() {
		/**
		 * Filters the default categories to be registered.
		 *
		 * Expected format:
		 * array(
		 *     'category-slug' => array(
		 *         'name'        => 'Category Name',
		 *         'description' => 'Category Description',
		 *     ),
		 * )
		 *
		 * @since 0.1.0
		 * @param array $default_categories Array of default categories.
		 */
		$default_categories = apply_filters( 'wp_feature_default_categories', array() );

		foreach ( $default_categories as $slug => $category ) {
			$category_data = array_merge(
				array( 'slug' => $slug ),
				$category
			);
			$this->add( $category_data );
		}
	}
}
