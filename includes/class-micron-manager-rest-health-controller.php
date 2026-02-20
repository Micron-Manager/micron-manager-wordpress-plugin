<?php
/**
 * REST API Health Controller
 *
 * @package MicronManager
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * REST API Health Controller Class.
 */
class Micron_Manager_REST_Health_Controller extends WP_REST_Controller {

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
    protected $rest_base = 'health';

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
                    'callback'            => array( $this, 'get_health' ),
                    'permission_callback' => '__return_true',
                ),
            )
        );
    }

    /**
     * Health check endpoint.
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return WP_REST_Response
     */
    public function get_health( $request ) {
        return new WP_REST_Response( array( 'status' => 'ok' ), 200 );
    }
}
