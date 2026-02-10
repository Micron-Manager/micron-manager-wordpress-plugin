<?php
/**
 * REST API Customers Controller
 *
 * @package MicronManager
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * REST API Customers Controller Class.
 */
class Micron_Manager_REST_Customers_Controller extends WP_REST_Controller {

    /**
     * Namespace.
     *
     * @var string
     */
    protected $namespace = 'micron-manager/v1';

    /**
     * Route base.
     *
     * @var string
     */
    protected $rest_base = 'customers';

    /**
     * Default search fields.
     *
     * @var array
     */
    protected $default_search_fields = array( 'email', 'first_name', 'last_name', 'company', 'username' );

    /**
     * Register routes.
     */
    public function register_routes() {
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
                'schema' => array( $this, 'get_public_item_schema' ),
            )
        );
    }

    /**
     * Check if a given request has access to read customers.
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return WP_Error|boolean
     */
    public function get_items_permissions_check( $request ) {
        if ( ! current_user_can( 'list_users' ) ) {
            return new WP_Error(
                'micron_manager_rest_cannot_view',
                __( 'Sorry, you cannot list resources.', 'micron-manager' ),
                array( 'status' => rest_authorization_required_code() )
            );
        }

        return true;
    }

    /**
     * Get all customers.
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return WP_Error|WP_REST_Response
     */
    public function get_items( $request ) {
        $prepared_args = array(
            'role__in' => array( 'customer', 'subscriber' ),
            'orderby'  => $request['orderby'],
            'order'    => $request['order'],
            'number'   => $request['per_page'],
            'offset'   => ( $request['page'] - 1 ) * $request['per_page'],
        );

        // Handle role filter
        if ( ! empty( $request['role'] ) ) {
            $prepared_args['role__in'] = array( $request['role'] );
        }

        // Handle search with custom fields
        $search = $request['search'];
        if ( ! empty( $search ) ) {
            $search_fields = $this->get_search_fields( $request );
            $prepared_args = $this->apply_search_filter( $prepared_args, $search, $search_fields );
        }

        // Handle email filter (exact match)
        if ( ! empty( $request['email'] ) ) {
            $prepared_args['search'] = $request['email'];
            $prepared_args['search_columns'] = array( 'user_email' );
        }

        // Execute query
        $query = new WP_User_Query( $prepared_args );

        $users = array();
        foreach ( $query->get_results() as $user ) {
            $data    = $this->prepare_item_for_response( $user, $request );
            $users[] = $this->prepare_response_for_collection( $data );
        }

        $response = rest_ensure_response( $users );

        // Add pagination headers
        $total    = $query->get_total();
        $max_pages = ceil( $total / $request['per_page'] );

        $response->header( 'X-WP-Total', (int) $total );
        $response->header( 'X-WP-TotalPages', (int) $max_pages );

        return $response;
    }

    /**
     * Get search fields from request.
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return array
     */
    protected function get_search_fields( $request ) {
        $search_fields_param = $request->get_param( '_searchFields' );

        if ( empty( $search_fields_param ) ) {
            return $this->default_search_fields;
        }

        // Parse comma-separated string or array
        if ( is_string( $search_fields_param ) ) {
            $search_fields_param = array_map( 'trim', explode( ',', $search_fields_param ) );
        }

        // Validate fields
        $valid_fields = array( 'email', 'first_name', 'last_name', 'company', 'username' );
        $search_fields = array_intersect( $search_fields_param, $valid_fields );

        return ! empty( $search_fields ) ? $search_fields : $this->default_search_fields;
    }

    /**
     * Apply search filter to query args.
     *
     * @param array  $args          Query arguments.
     * @param string $search        Search term.
     * @param array  $search_fields Fields to search in.
     * @return array
     */
    protected function apply_search_filter( $args, $search, $search_fields ) {
        global $wpdb;

        $meta_query = array( 'relation' => 'OR' );
        $search_columns = array();

        foreach ( $search_fields as $field ) {
            switch ( $field ) {
                case 'email':
                    $search_columns[] = 'user_email';
                    break;
                case 'username':
                    $search_columns[] = 'user_login';
                    $search_columns[] = 'user_nicename';
                    break;
                case 'first_name':
                    $meta_query[] = array(
                        'key'     => 'first_name',
                        'value'   => $search,
                        'compare' => 'LIKE',
                    );
                    break;
                case 'last_name':
                    $meta_query[] = array(
                        'key'     => 'last_name',
                        'value'   => $search,
                        'compare' => 'LIKE',
                    );
                    break;
                case 'company':
                    $meta_query[] = array(
                        'key'     => 'billing_company',
                        'value'   => $search,
                        'compare' => 'LIKE',
                    );
                    break;
            }
        }

        // Apply search columns if any
        if ( ! empty( $search_columns ) ) {
            $args['search'] = '*' . $search . '*';
            $args['search_columns'] = array_unique( $search_columns );
        }

        // Apply meta query if there are meta field searches
        if ( count( $meta_query ) > 1 ) {
            // If we also have search columns, we need a custom approach
            if ( ! empty( $search_columns ) ) {
                // Store for later use in pre_user_query filter
                $args['_micron_meta_search'] = $meta_query;
                $args['_micron_search_term'] = $search;
                add_action( 'pre_user_query', array( $this, 'modify_user_query_for_search' ) );
            } else {
                $args['meta_query'] = $meta_query;
            }
        }

        return $args;
    }

    /**
     * Modify user query to include meta search with OR relation to column search.
     *
     * @param WP_User_Query $query User query object.
     */
    public function modify_user_query_for_search( $query ) {
        global $wpdb;

        if ( empty( $query->query_vars['_micron_meta_search'] ) ) {
            return;
        }

        $meta_query = $query->query_vars['_micron_meta_search'];
        $search_term = $query->query_vars['_micron_search_term'];

        // Build meta conditions
        $meta_conditions = array();
        foreach ( $meta_query as $key => $meta ) {
            if ( $key === 'relation' ) {
                continue;
            }
            $meta_conditions[] = $wpdb->prepare(
                "EXISTS (SELECT 1 FROM {$wpdb->usermeta} WHERE user_id = {$wpdb->users}.ID AND meta_key = %s AND meta_value LIKE %s)",
                $meta['key'],
                '%' . $wpdb->esc_like( $search_term ) . '%'
            );
        }

        if ( ! empty( $meta_conditions ) ) {
            // Modify the WHERE clause to add OR conditions for meta
            $meta_sql = ' OR (' . implode( ' OR ', $meta_conditions ) . ')';
            $query->query_where = preg_replace(
                '/(\s+AND\s+\()([^)]+user_login[^)]+\))/i',
                '$1$2' . $meta_sql,
                $query->query_where
            );
        }

        // Remove filter to avoid affecting other queries
        remove_action( 'pre_user_query', array( $this, 'modify_user_query_for_search' ) );
    }

    /**
     * Prepare a single customer for response.
     *
     * @param WP_User         $user    User object.
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public function prepare_item_for_response( $user, $request ) {
        $data = array(
            'id'                 => $user->ID,
            'date_created'       => wc_rest_prepare_date_response( $user->user_registered ),
            'date_created_gmt'   => wc_rest_prepare_date_response( $user->user_registered, true ),
            'date_modified'      => null,
            'date_modified_gmt'  => null,
            'email'              => $user->user_email,
            'first_name'         => get_user_meta( $user->ID, 'first_name', true ),
            'last_name'          => get_user_meta( $user->ID, 'last_name', true ),
            'role'               => ! empty( $user->roles ) ? $user->roles[0] : 'customer',
            'username'           => $user->user_login,
            'billing'            => $this->get_billing_address( $user ),
            'shipping'           => $this->get_shipping_address( $user ),
            'is_paying_customer' => (bool) get_user_meta( $user->ID, 'paying_customer', true ),
            'avatar_url'         => get_avatar_url( $user->ID ),
            'meta_data'          => array(),
        );

        $context = ! empty( $request['context'] ) ? $request['context'] : 'view';
        $data    = $this->add_additional_fields_to_object( $data, $request );
        $data    = $this->filter_response_by_context( $data, $context );

        $response = rest_ensure_response( $data );
        $response->add_links( $this->prepare_links( $user ) );

        return $response;
    }

    /**
     * Get billing address.
     *
     * @param WP_User $user User object.
     * @return array
     */
    protected function get_billing_address( $user ) {
        return array(
            'first_name' => get_user_meta( $user->ID, 'billing_first_name', true ),
            'last_name'  => get_user_meta( $user->ID, 'billing_last_name', true ),
            'company'    => get_user_meta( $user->ID, 'billing_company', true ),
            'address_1'  => get_user_meta( $user->ID, 'billing_address_1', true ),
            'address_2'  => get_user_meta( $user->ID, 'billing_address_2', true ),
            'city'       => get_user_meta( $user->ID, 'billing_city', true ),
            'state'      => get_user_meta( $user->ID, 'billing_state', true ),
            'postcode'   => get_user_meta( $user->ID, 'billing_postcode', true ),
            'country'    => get_user_meta( $user->ID, 'billing_country', true ),
            'email'      => get_user_meta( $user->ID, 'billing_email', true ),
            'phone'      => get_user_meta( $user->ID, 'billing_phone', true ),
        );
    }

    /**
     * Get shipping address.
     *
     * @param WP_User $user User object.
     * @return array
     */
    protected function get_shipping_address( $user ) {
        return array(
            'first_name' => get_user_meta( $user->ID, 'shipping_first_name', true ),
            'last_name'  => get_user_meta( $user->ID, 'shipping_last_name', true ),
            'company'    => get_user_meta( $user->ID, 'shipping_company', true ),
            'address_1'  => get_user_meta( $user->ID, 'shipping_address_1', true ),
            'address_2'  => get_user_meta( $user->ID, 'shipping_address_2', true ),
            'city'       => get_user_meta( $user->ID, 'shipping_city', true ),
            'state'      => get_user_meta( $user->ID, 'shipping_state', true ),
            'postcode'   => get_user_meta( $user->ID, 'shipping_postcode', true ),
            'country'    => get_user_meta( $user->ID, 'shipping_country', true ),
            'phone'      => get_user_meta( $user->ID, 'shipping_phone', true ),
        );
    }

    /**
     * Prepare links for the response.
     *
     * @param WP_User $user User object.
     * @return array
     */
    protected function prepare_links( $user ) {
        $links = array(
            'self'       => array(
                'href' => rest_url( sprintf( '/%s/%s/%d', $this->namespace, $this->rest_base, $user->ID ) ),
            ),
            'collection' => array(
                'href' => rest_url( sprintf( '/%s/%s', $this->namespace, $this->rest_base ) ),
            ),
        );

        return $links;
    }

    /**
     * Get the query params for collections.
     *
     * @return array
     */
    public function get_collection_params() {
        $params = parent::get_collection_params();

        $params['context'] = array(
            'default'     => 'view',
            'description' => __( 'Scope under which the request is made.', 'micron-manager' ),
            'type'        => 'string',
            'enum'        => array( 'view', 'edit' ),
        );

        $params['page'] = array(
            'description'       => __( 'Current page of the collection.', 'micron-manager' ),
            'type'              => 'integer',
            'default'           => 1,
            'sanitize_callback' => 'absint',
            'validate_callback' => 'rest_validate_request_arg',
            'minimum'           => 1,
        );

        $params['per_page'] = array(
            'description'       => __( 'Maximum number of items to be returned in result set.', 'micron-manager' ),
            'type'              => 'integer',
            'default'           => 10,
            'minimum'           => 1,
            'maximum'           => 100,
            'sanitize_callback' => 'absint',
            'validate_callback' => 'rest_validate_request_arg',
        );

        $params['search'] = array(
            'description'       => __( 'Limit results to those matching a string.', 'micron-manager' ),
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'validate_callback' => 'rest_validate_request_arg',
        );

        $params['_searchFields'] = array(
            'description'       => __( 'Comma-separated list of fields to search in. Valid values: email, first_name, last_name, company, username.', 'micron-manager' ),
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'validate_callback' => 'rest_validate_request_arg',
        );

        $params['email'] = array(
            'description'       => __( 'Limit results to those matching a specific email.', 'micron-manager' ),
            'type'              => 'string',
            'format'            => 'email',
            'sanitize_callback' => 'sanitize_email',
            'validate_callback' => 'rest_validate_request_arg',
        );

        $params['role'] = array(
            'description'       => __( 'Limit results to those matching a specific role.', 'micron-manager' ),
            'type'              => 'string',
            'enum'              => array_merge( array( 'all' ), array_keys( wp_roles()->roles ) ),
            'sanitize_callback' => 'sanitize_text_field',
            'validate_callback' => 'rest_validate_request_arg',
        );

        $params['orderby'] = array(
            'description'       => __( 'Sort collection by attribute.', 'micron-manager' ),
            'type'              => 'string',
            'default'           => 'registered',
            'enum'              => array( 'id', 'include', 'name', 'registered', 'email' ),
            'sanitize_callback' => 'sanitize_text_field',
            'validate_callback' => 'rest_validate_request_arg',
        );

        $params['order'] = array(
            'description'       => __( 'Order sort attribute ascending or descending.', 'micron-manager' ),
            'type'              => 'string',
            'default'           => 'desc',
            'enum'              => array( 'asc', 'desc' ),
            'sanitize_callback' => 'sanitize_text_field',
            'validate_callback' => 'rest_validate_request_arg',
        );

        return $params;
    }

    /**
     * Get the Customer's schema, conforming to JSON Schema.
     *
     * @return array
     */
    public function get_item_schema() {
        $schema = array(
            '$schema'    => 'http://json-schema.org/draft-04/schema#',
            'title'      => 'customer',
            'type'       => 'object',
            'properties' => array(
                'id'                 => array(
                    'description' => __( 'Unique identifier for the resource.', 'micron-manager' ),
                    'type'        => 'integer',
                    'context'     => array( 'view', 'edit' ),
                    'readonly'    => true,
                ),
                'date_created'       => array(
                    'description' => __( 'The date the customer was created.', 'micron-manager' ),
                    'type'        => 'string',
                    'format'      => 'date-time',
                    'context'     => array( 'view', 'edit' ),
                    'readonly'    => true,
                ),
                'email'              => array(
                    'description' => __( 'The email address for the customer.', 'micron-manager' ),
                    'type'        => 'string',
                    'format'      => 'email',
                    'context'     => array( 'view', 'edit' ),
                ),
                'first_name'         => array(
                    'description' => __( 'Customer first name.', 'micron-manager' ),
                    'type'        => 'string',
                    'context'     => array( 'view', 'edit' ),
                ),
                'last_name'          => array(
                    'description' => __( 'Customer last name.', 'micron-manager' ),
                    'type'        => 'string',
                    'context'     => array( 'view', 'edit' ),
                ),
                'role'               => array(
                    'description' => __( 'Customer role.', 'micron-manager' ),
                    'type'        => 'string',
                    'context'     => array( 'view', 'edit' ),
                    'readonly'    => true,
                ),
                'username'           => array(
                    'description' => __( 'Customer login name.', 'micron-manager' ),
                    'type'        => 'string',
                    'context'     => array( 'view', 'edit' ),
                    'readonly'    => true,
                ),
                'billing'            => array(
                    'description' => __( 'List of billing address data.', 'micron-manager' ),
                    'type'        => 'object',
                    'context'     => array( 'view', 'edit' ),
                ),
                'shipping'           => array(
                    'description' => __( 'List of shipping address data.', 'micron-manager' ),
                    'type'        => 'object',
                    'context'     => array( 'view', 'edit' ),
                ),
                'is_paying_customer' => array(
                    'description' => __( 'Is the customer a paying customer?', 'micron-manager' ),
                    'type'        => 'boolean',
                    'context'     => array( 'view', 'edit' ),
                    'readonly'    => true,
                ),
                'avatar_url'         => array(
                    'description' => __( 'Avatar URL.', 'micron-manager' ),
                    'type'        => 'string',
                    'context'     => array( 'view', 'edit' ),
                    'readonly'    => true,
                ),
            ),
        );

        return $this->add_additional_fields_schema( $schema );
    }
}
