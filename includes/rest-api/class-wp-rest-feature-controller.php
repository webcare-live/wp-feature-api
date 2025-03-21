<?php
/**
 * REST API controller for features.
 *
 * @package WP_Feature_API
 */

/**
 * Controller class for the Features API REST endpoints.
 *
 * @since 0.1.0
 */
class WP_REST_Feature_Controller extends WP_REST_Controller {

	/**
	 * Default fields to include on feature responses.
	 *
	 * @since 0.1.0
	 * @var array
	 */
	private $default_fields = array( 'id', 'name', 'description', 'type', 'categories', 'metadata' );

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 */
	public function __construct() {
		$this->namespace = 'wp/v2';
		$this->rest_base = 'features';

		add_filter( 'rest_authentication_errors', array( $this, 'authenticate_cookie' ) );
	}

	/**
	 * Authenticate using cookies.
	 *
	 * @param WP_Error|null|bool $result Error from another authentication handler,
	 *                                   null if we should handle it, or another value if not.
	 * @return WP_Error|null|bool
	 */
	public function authenticate_cookie( $result ) {
		if ( ! empty( $result ) ) {
			return $result;
		}

		if ( is_user_logged_in() ) {
			return true;
		}

		return $result;
	}

	/**
	 * Registers the routes for the Features API.
	 *
	 * @since 0.1.0
	 */
	public function register_routes() {
		$resource_base = '/' . $this->rest_base . '/(?P<id>' . WP_Feature::ID_PATTERN . ')';

		// Register GET endpoint for retrieving all features with pagination.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
				'schema' => array( $this, 'get_items_schema' ),
			)
		);

		// Register GET endpoint for retrieving a specific feature by ID.
		register_rest_route(
			$this->namespace,
			$resource_base,
			array(
				'args'   => array(
					'id' => array(
						'description' => __( 'Unique identifier for the feature.', 'wp-feature-api' ),
						'type'        => 'string',
						'required'    => true,
						'pattern'     => WP_Feature::ID_PATTERN,
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
				),
				'schema' => array( $this, 'get_item_schema' ),
			)
		);

		// Register POST endpoint for executing tool features.
		register_rest_route(
			$this->namespace,
			$resource_base . '/run',
			array(
				'args'   => array(
					'id' => array(
						'description' => __( 'Unique identifier for the feature.', 'wp-feature-api' ),
						'type'        => 'string',
						'required'    => true,
						'pattern'     => WP_Feature::ID_PATTERN,
					),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'run_item' ),
					'permission_callback' => array( $this, 'run_item_permissions_check' ),
					'args'                => array(
						'metadata' => array(
							'description' => __( 'Metadata for executing the feature.', 'wp-feature-api' ),
							'type'        => 'object',
							'properties'  => array(
								'client_features' => array(
									'type'        => 'array',
									'items'       => $this->get_item_schema(),
								),
							),
						),
						'context' => array(
							'description' => __( 'Context for executing the feature.', 'wp-feature-api' ),
							'type'        => 'object',
							'default'     => array(),
						),
					),
				),
				'schema' => array( $this, 'get_item_schema' ),
			)
		);

		// Register GET endpoint for querying features.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/query',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'query_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => $this->get_query_params(),
				),
				'schema' => array( $this, 'get_item_schema' ),
			)
		);
	}

	/**
	 * Retrieves a collection of features.
	 *
	 * @since 0.1.0
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_items( $request ) {
		$features = wp_feature_registry()->get();

		// Handle pagination.
		$page     = $request['page'] ?? 1;
		$per_page = $request['per_page'] ?? 10;
		$offset   = ( $page - 1 ) * $per_page;

		$total_features = count( $features );
		$max_pages      = ceil( $total_features / $per_page );

		// Apply pagination.
		$features = array_slice( $features, $offset, $per_page );

		$data = array();
		foreach ( $features as $feature ) {
			$item   = $this->prepare_item_for_response( $feature, $request );
			$data[] = $this->prepare_response_for_collection( $item );
		}

		$response = rest_ensure_response( $data );

		// Add pagination headers.
		$response->header( 'X-WP-Total', $total_features );
		$response->header( 'X-WP-TotalPages', $max_pages );

		return $response;
	}

	/**
	 * Retrieves a single feature.
	 *
	 * @since 0.1.0
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_item( $request ) {
		$id = $request['id'];

		$registry = WP_Feature_Registry::get_instance();
		$feature  = $registry->find( $id );

		if ( ! $feature ) {
			return new WP_Error(
				'rest_feature_not_found',
				__( 'Feature not found.', 'wp-feature-api' ),
				array( 'status' => 404 )
			);
		}

		// If type is 'tool', return error that POST is required for tool features.
		if ( WP_Feature::TYPE_TOOL === $feature->get_type() ) {
			return new WP_Error(
				'rest_feature_invalid_method',
				__( 'Tool features must be accessed via POST to the /run endpoint.', 'wp-feature-api' ),
				array( 'status' => 405 )
			);
		}

		$data     = $this->prepare_item_for_response( $feature, $request );
		$response = rest_ensure_response( $data );

		return $response;
	}

	/**
	 * Runs a tool feature.
	 *
	 * @since 0.1.0
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function run_item( $request ) {
		$id      = $request['id'];
		$context = $request->get_param( 'context' );

		$registry = WP_Feature_Registry::get_instance();
		$feature  = $registry->find( $id );

		if ( ! $feature ) {
			return new WP_Error(
				'rest_feature_not_found',
				__( 'Feature not found.', 'wp-feature-api' ),
				array( 'status' => 404 )
			);
		}

		// Return error if feature is not a tool.
		if ( WP_Feature::TYPE_TOOL !== $feature->get_type() ) {
			return new WP_Error(
				'rest_feature_invalid_type',
				__( 'Only tool features can be rund.', 'wp-feature-api' ),
				array( 'status' => 400 )
			);
		}

		// Execute the feature.
		$result = $feature->run( $context );

		// Check if the result is an error.
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$response = rest_ensure_response( $result );
		return $response;
	}

	/**
	 * Queries features based on criteria.
	 *
	 * @since 0.1.0
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function query_items( $request ) {
		$registry = WP_Feature_Registry::get_instance();

		// Build query args from request parameters.
		$query_args = array();

		// Filter by type.
		if ( ! empty( $request['type'] ) ) {
			$query_args['type'] = $request['type'];
		}

		// Filter by category.
		if ( ! empty( $request['category'] ) ) {
			$query_args['categories'] = array( $request['category'] );
		}

		// Filter by location.
		if ( ! empty( $request['location'] ) ) {
			$query_args['location'] = $request['location'];
		}

		// Create query object.
		$query = new WP_Feature_Query( $query_args );

		// Get features based on query.
		$features = $registry->get( $query );

		// Handle pagination.
		$page     = $request['page'] ?? 1;
		$per_page = $request['per_page'] ?? 10;
		$offset   = ( $page - 1 ) * $per_page;

		$total_features = count( $features );
		$max_pages      = ceil( $total_features / $per_page );

		// Apply pagination.
		$features = array_slice( $features, $offset, $per_page );

		$data = array();
		foreach ( $features as $feature ) {
			$item   = $this->prepare_item_for_response( $feature, $request );
			$data[] = $this->prepare_response_for_collection( $item );
		}

		$response = rest_ensure_response( $data );

		// Add pagination headers.
		$response->header( 'X-WP-Total', $total_features );
		$response->header( 'X-WP-TotalPages', $max_pages );

		return $response;
	}

	/**
	 * Checks if a given request has access to read features.
	 *
	 * @since 0.1.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error True if the request has read access, WP_Error object otherwise.
	 */
	public function get_items_permissions_check( $request ) {
		return current_user_can( 'read' );
	}

	/**
	 * Checks if a given request has access to read a feature.
	 *
	 * @since 0.1.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error True if the request has read access, WP_Error object otherwise.
	 */
	public function get_item_permissions_check( $request ) {
		$id = $request['id'];

		$registry = WP_Feature_Registry::get_instance();
		$feature  = $registry->find( $id );

		if ( ! $feature ) {
			return true; // Let the callback handle the 404.
		}

		// Get permissions from feature.
		$permissions = $feature->get_permissions();

		// If permissions is a callable, call it with the feature and request.
		if ( is_callable( $permissions ) ) {
			$result = call_user_func( $permissions, $feature, $request );
			if ( is_wp_error( $result ) ) {
				return $result;
			}
			return true;
		}

		// If permissions is a string or array, check capabilities.
		if ( is_string( $permissions ) && ! empty( $permissions ) ) {
			if ( ! current_user_can( $permissions ) ) {
				return new WP_Error(
					'rest_forbidden',
					__( 'Sorry, you are not allowed to view this feature.', 'wp-feature-api' ),
					array( 'status' => rest_authorization_required_code() )
				);
			}
			return true;
		}

		if ( is_array( $permissions ) && ! empty( $permissions ) ) {
			foreach ( $permissions as $capability ) {
				if ( ! current_user_can( $capability ) ) {
					return new WP_Error(
						'rest_forbidden',
						__( 'Sorry, you are not allowed to view this feature.', 'wp-feature-api' ),
						array( 'status' => rest_authorization_required_code() )
					);
				}
			}
			return true;
		}

		// Default to requiring read permission.
		return current_user_can( 'read' );
	}

	/**
	 * Checks if a given request has access to run a feature.
	 *
	 * @since 0.1.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error True if the request has read access, WP_Error object otherwise.
	 */
	public function run_item_permissions_check( $request ) {
		$feature  = wp_feature_registry()->find( $request['id'] );

		if ( ! $feature ) {
			return true; // Let the callback handle the 404.
		}

		// Get permissions from feature.
		$permissions = $feature->get_permissions();

		// If permissions is a callable, call it with the feature and request.
		if ( is_callable( $permissions ) ) {
			$result = call_user_func( $permissions, $feature, $request );
			if ( is_wp_error( $result ) ) {
				return $result;
			}
			return true;
		}

		// If permissions is a string or array, check capabilities.
		if ( is_string( $permissions ) && ! empty( $permissions ) ) {
			if ( ! current_user_can( $permissions ) ) {
				return new WP_Error(
					'rest_forbidden',
					__( 'Sorry, you are not allowed to run this feature.', 'wp-feature-api' ),
					array( 'status' => rest_authorization_required_code() )
				);
			}
			return true;
		}

		if ( is_array( $permissions ) && ! empty( $permissions ) ) {
			foreach ( $permissions as $capability ) {
				if ( ! current_user_can( $capability ) ) {
					return new WP_Error(
						'rest_forbidden',
						__( 'Sorry, you are not allowed to run this feature.', 'wp-feature-api' ),
						array( 'status' => rest_authorization_required_code() )
					);
				}
			}
			return true;
		}

		// Default to requiring edit permission for executing tools.
		return current_user_can( 'edit_posts' );
	}

	/**
	 * Prepares a feature for response.
	 *
	 * @since 0.1.0
	 *
	 * @param WP_Feature      $feature The feature object.
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 */
	public function prepare_item_for_response( $feature, $request ) {
		$data = $this->transform_feature_data( $feature, $request );

		// Add _links for REST API discoverability.
		$links = array(
			'self' => array(
				'href' => rest_url( sprintf( '%s/%s/%s', $this->namespace, $this->rest_base, $feature->get_id() ) ),
			),
		);

		// Add run link for tool features.
		if ( WP_Feature::TYPE_TOOL === $feature->get_type() ) {
			$links['run'] = array(
				'href'   => rest_url( sprintf( '%s/%s/%s/run', $this->namespace, $this->rest_base, $feature->get_id() ) ),
				'method' => WP_REST_Server::CREATABLE,
			);
		}

		$response = rest_ensure_response( $data );
		$response->add_links( $links );

		return $response;
	}

	/**
	 * Retrieves the query params for collections.
	 *
	 * @since 0.1.0
	 *
	 * @return array Collection parameters.
	 */
	public function get_collection_params() {
		return array(
			'page'     => array(
				'description'       => __( 'Current page of the collection.', 'wp-feature-api' ),
				'type'              => 'integer',
				'default'           => 1,
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
				'minimum'           => 1,
			),
			'per_page' => array(
				'description'       => __( 'Maximum number of items to be returned in result set.', 'wp-feature-api' ),
				'type'              => 'integer',
				'default'           => 10,
				'minimum'           => 1,
				'maximum'           => 100,
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'fields' => array(
				'description'       => __( 'Limit response to specific fields. Defaults to all fields.', 'wp-feature-api' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			),
		);
	}

	/**
	 * Retrieves the query params for feature queries.
	 *
	 * @since 0.1.0
	 *
	 * @return array Query parameters.
	 */
	public function get_query_params() {
		return array_merge(
			$this->get_collection_params(),
			array(
				'type'     => array(
					'description'       => __( 'Limit results to features of a specific type.', 'wp-feature-api' ),
					'type'              => 'string',
					'enum'              => array( 'resource', 'tool' ),
					'sanitize_callback' => 'sanitize_text_field',
					'validate_callback' => 'rest_validate_request_arg',
				),
				'category' => array(
					'description'       => __( 'Limit results to features in a specific category.', 'wp-feature-api' ),
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
					'validate_callback' => 'rest_validate_request_arg',
				),
				'location' => array(
					'description'       => __( 'Limit results to features with a specific location.', 'wp-feature-api' ),
					'type'              => 'string',
					'enum'              => array( 'server', 'client' ),
					'sanitize_callback' => 'sanitize_text_field',
					'validate_callback' => 'rest_validate_request_arg',
				),
			)
		);
	}

	/**
	 * Retrieves the schema for a single feature item.
	 *
	 * @since 0.1.0
	 *
	 * @return array Item schema data.
	 */
	public function get_item_schema() {
		if ( $this->schema ) {
			return $this->schema;
		}

		$this->schema = array(
			'type'       => 'object',
			'properties' => array(
				'id' => array(
					'description' => __( 'Unique identifier for the feature.', 'wp-feature-api' ),
					'type'        => 'string',
				),
				'name' => array(
					'description' => __( 'The name of the feature.', 'wp-feature-api' ),
					'type'        => 'string',
				),
				'description' => array(
					'description' => __( 'The description of the feature.', 'wp-feature-api' ),
					'type'        => 'string',
				),
				'type' => array(
					'description' => __( 'The type of the feature (resource or tool).', 'wp-feature-api' ),
					'type'        => 'string',
					'enum'        => array( 'resource', 'tool' ),
				),
				'categories' => array(
					'description' => __( 'The categories that the feature belongs to.', 'wp-feature-api' ),
					'type'        => 'array',
					'items'       => array(
						'type' => 'string',
					),
				),
				'location' => array(
					'description' => __( 'Where the feature is executed (server or client).', 'wp-feature-api' ),
					'type'        => 'string',
					'enum'        => array( 'server', 'client' ),
				),
				'input_schema' => array(
					'description' => __( 'JSON Schema defining the input parameters for the feature.', 'wp-feature-api' ),
					'type'        => 'object',
				),
				'output_schema' => array(
					'description' => __( 'JSON Schema defining the output format of the feature.', 'wp-feature-api' ),
					'type'        => 'object',
				),
				'meta' => array(
					'description' => __( 'Additional metadata associated with the feature.', 'wp-feature-api' ),
					'type'        => 'object',
					'properties'  => array(),
				),
			),
			'required' => array( 'id', 'name', 'description', 'type' ),
		);

		return $this->schema;
	}

	/**
	 * Retrieves the schema for features collection.
	 *
	 * @since 0.1.0
	 *
	 * @return array Collection schema data.
	 */
	public function get_items_schema() {
		$schema = $this->get_item_schema();

		return array(
			'type'       => 'array',
			'items'      => $schema,
			'properties' => array(
				'_links' => array(
					'type'        => 'object',
					'description' => __( 'Links related to the collection.', 'wp-feature-api' ),
					'context'     => array( 'view', 'edit', 'embed' ),
					'readonly'    => true,
				),
			),
		);
	}

	/**
	 * Transforms feature data for the response.
	 *
	 * @since 0.1.0
	 *
	 * @param WP_Feature      $feature The feature object.
	 * @param WP_REST_Request $request The request object.
	 * @return array Transformed feature data.
	 */
	private function transform_feature_data( $feature, $request ) {
		$data = array_filter( $feature->to_array() );

		$fields = $this->default_fields;
		if ( ! empty( $request['fields'] ) ) {
			$requested_fields = array_map( 'trim', explode( ',', $request['fields'] ) );
			$fields = array_unique( $requested_fields );
		}

		return array_intersect_key( $data, array_flip( $fields ) );
	}
}
