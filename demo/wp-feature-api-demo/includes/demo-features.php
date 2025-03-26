<?php
/**
 * WordPress Feature API Demo features.
 *
 * @package WordPress\Feature_API_Demo
 */

/**
 * Register demo features.
 *
 * @since 0.1.0
 * @return void
 */
function wp_feature_api_demo_register_features() {
	wp_register_feature(
		array(
			'id'          => 'demo/site-info',
			'name'        => __( 'Site Information', 'wp-feature-api-demo' ),
			'description' => __( 'Get basic information about the WordPress site.', 'wp-feature-api-demo' ),
			'type'        => WP_Feature::TYPE_RESOURCE,
			'categories'  => array( 'demo', 'site', 'information' ),
			'callback'    => 'wp_feature_api_demo_site_info_callback',
		)
	);
	wp_register_feature(
		array(
			'id'          => 'demo/woocommerce-info',
			'name'        => __( 'WooCommerce Information', 'wp-feature-api-demo' ),
			'description' => __( 'Get basic information about the WooCommerce site.', 'wp-feature-api-demo' ),
			'type'        => WP_Feature::TYPE_RESOURCE,
			'categories'  => array( 'demo', 'woocommerce', 'information' ),
			'callback'    => 'wp_feature_api_demo_woocommerce_info_callback',
			'is_eligible' => function () {
				return function_exists( 'WC' );
			},
		)
	);
}

/**
 * Callback for the 'demo/site-info' feature.
 *
 * @since 0.1.0
 * @param array $input The input data (not used in this case).
 * @return array Site information.
 */
function wp_feature_api_demo_site_info_callback( $input ) {
	return array(
		'name'        => get_bloginfo( 'name' ),
		'description' => get_bloginfo( 'description' ),
		'url'         => home_url(),
		'version'     => get_bloginfo( 'version' ),
		'language'    => get_bloginfo( 'language' ),
		'timezone'    => wp_timezone_string(),
		'date_format' => get_option( 'date_format' ),
		'time_format' => get_option( 'time_format' ),
		'active_plugins' => get_option( 'active_plugins' ),
		'active_theme' => get_option( 'stylesheet' ),
	);
}

/**
 * Callback for the 'demo/woocommerce-info' feature.
 *
 * @since 0.1.0
 * @param array $input The input data (not used in this case).
 * @return array WooCommerce information.
 */
function wp_feature_api_demo_woocommerce_info_callback( $input ) {
	return array(
		'name' => 'WooCommerce',
		'version' => WC()->version,
		'currency' => get_woocommerce_currency(),
		'country' => get_woocommerce_country(),
		'language' => get_woocommerce_language(),
		'timezone' => wp_timezone_string(),
		'date_format' => get_option( 'date_format' ),
	);
}

add_action( 'init', 'wp_feature_api_demo_register_features' );
