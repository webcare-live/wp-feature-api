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
	// Register a simple resource feature for getting site information.
	wp_register_feature(
		array(
			'id'          => 'demo/site-info',
			'name'        => __( 'Site Information', 'wp-feature-api-demo' ),
			'description' => __( 'Get basic information about the WordPress site.', 'wp-feature-api-demo' ),
			'type'        => WP_Feature::TYPE_RESOURCE,
			'categories'  => array( 'demo', 'site', 'information' ),
			'callback'    => 'wp_feature_api_demo_site_info_callback',
			'permissions' => 'administrator',
		)
	);

	// Register a tool feature for creating a post.
	wp_register_feature(
		array(
			'id'          => 'demo/create-post',
			'name'        => __( 'Create Post', 'wp-feature-api-demo' ),
			'description' => __( 'Create a new post in WordPress.', 'wp-feature-api-demo' ),
			'type'        => WP_Feature::TYPE_TOOL,
			'categories'  => array( 'post', 'create' ),
			'input_schema' => array(
				'type'       => 'object',
				'properties' => array(
					'title'   => array(
						'type'        => 'string',
						'description' => __( 'The title of the post.', 'wp-feature-api-demo' ),
					),
					'content' => array(
						'type'        => 'string',
						'description' => __( 'The content of the post.', 'wp-feature-api-demo' ),
					),
					'status'  => array(
						'type'        => 'string',
						'description' => __( 'The status of the post.', 'wp-feature-api-demo' ),
						'default'     => 'draft',
						'enum'        => array( 'draft', 'publish', 'pending', 'future', 'private' ),
					),
				),
				'required'   => array( 'title', 'content' ),
			),
			'output_schema' => array(
				'type'       => 'object',
				'properties' => array(
					'id'     => array(
						'type'        => 'integer',
						'description' => __( 'The ID of the created post.', 'wp-feature-api-demo' ),
					),
					'url'    => array(
						'type'        => 'string',
						'description' => __( 'The URL of the created post.', 'wp-feature-api-demo' ),
					),
					'status' => array(
						'type'        => 'string',
						'description' => __( 'The status of the created post.', 'wp-feature-api-demo' ),
					),
				),
			),
			'callback'    => 'wp_feature_api_demo_create_post_callback',
			'permissions' => 'publish_posts',
		)
	);

	// Register a resource feature for retrieving user data.
	wp_register_feature(
		array(
			'id'          => 'demo/current-user',
			'name'        => __( 'Current User', 'wp-feature-api-demo' ),
			'description' => __( 'Get information about the current user.', 'wp-feature-api-demo' ),
			'type'        => WP_Feature::TYPE_RESOURCE,
			'categories'  => array( 'user', 'information' ),
			'callback'    => 'wp_feature_api_demo_current_user_callback',
			'permissions' => 'read',
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
	);
}

/**
 * Callback for the 'demo/create-post' feature.
 *
 * @since 0.1.0
 * @param array $input The input data containing title, content, and status.
 * @return array Information about the created post.
 */
function wp_feature_api_demo_create_post_callback( $input ) {
	$post_data = array(
		'post_title'   => sanitize_text_field( $input['title'] ),
		'post_content' => wp_kses_post( $input['content'] ),
		'post_status'  => isset( $input['status'] ) ? sanitize_key( $input['status'] ) : 'draft',
		'post_type'    => 'post',
	);

	$post_id = wp_insert_post( $post_data );

	if ( is_wp_error( $post_id ) ) {
		return new WP_Error(
			'post_creation_failed',
			__( 'Failed to create post.', 'wp-feature-api-demo' ),
			array( 'status' => 500 )
		);
	}

	return array(
		'id'     => $post_id,
		'url'    => get_permalink( $post_id ),
		'status' => get_post_status( $post_id ),
	);
}

/**
 * Callback for the 'demo/current-user' feature.
 *
 * @since 0.1.0
 * @param array $input The input data (not used in this case).
 * @return array Current user information.
 */
function wp_feature_api_demo_current_user_callback( $input ) {
	$current_user = wp_get_current_user();

	if ( ! $current_user->exists() ) {
		return new WP_Error(
			'no_user',
			__( 'No authenticated user found.', 'wp-feature-api-demo' ),
			array( 'status' => 401 )
		);
	}

	return array(
		'id'            => $current_user->ID,
		'username'      => $current_user->user_login,
		'email'         => $current_user->user_email,
		'display_name'  => $current_user->display_name,
		'roles'         => $current_user->roles,
		'first_name'    => $current_user->first_name,
		'last_name'     => $current_user->last_name,
		'registered'    => $current_user->user_registered,
		'capabilities'  => array_keys( array_filter( $current_user->allcaps ) ),
	);
}

// Register demo features.
add_action( 'init', 'wp_feature_api_demo_register_features' );
