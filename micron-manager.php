<?php
/**
 * Micron Manager
 *
 * @package           MicronManager
 * @author            Lorenzo Quinti
 * @copyright         2026 Lorenzo Quinti
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       Micron Manager
 * Plugin URI:        https://github.com/lorenzoquinti/micron-manager-wordpress-plugin
 * Description:       Exposes custom REST API endpoints for external applications.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            Lorenzo Quinti
 * Author URI:        https://github.com/lorenzoquinti
 * Text Domain:       micron-manager
 * Domain Path:       /languages
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Plugin constants
define( 'MICRON_MANAGER_VERSION', '1.0.0' );
define( 'MICRON_MANAGER_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'MICRON_MANAGER_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'MICRON_MANAGER_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Plugin activation hook.
 */
function micron_manager_activate() {
    // Activation tasks
}
register_activation_hook( __FILE__, 'micron_manager_activate' );

/**
 * Plugin deactivation hook.
 */
function micron_manager_deactivate() {
    // Deactivation tasks
}
register_deactivation_hook( __FILE__, 'micron_manager_deactivate' );

/**
 * Load plugin files.
 */
function micron_manager_includes() {
    require_once MICRON_MANAGER_PLUGIN_DIR . 'includes/class-micron-manager-rest-customers-controller.php';
    require_once MICRON_MANAGER_PLUGIN_DIR . 'includes/class-micron-manager-rest-health-controller.php';
}
add_action( 'plugins_loaded', 'micron_manager_includes' );

/**
 * Register REST API routes.
 */
function micron_manager_register_rest_routes() {
    $customers_controller = new Micron_Manager_REST_Customers_Controller();
    $customers_controller->register_routes();

    $health_controller = new Micron_Manager_REST_Health_Controller();
    $health_controller->register_routes();
}
add_action( 'rest_api_init', 'micron_manager_register_rest_routes' );
