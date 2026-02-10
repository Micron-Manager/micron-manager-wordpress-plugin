<?php
/**
 * Uninstall Micron Manager
 *
 * @package MicronManager
 */

// If uninstall not called from WordPress, exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Delete plugin options
delete_option( 'micron_manager_options' );

// Delete any transients
delete_transient( 'micron_manager_cache' );
