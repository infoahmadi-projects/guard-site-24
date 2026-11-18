<?php
/**
 * Plugin Name: Guard Site 24
 * Description: Modular WordPress security monitoring and basic hardening tools.
 * Version: 1.0.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: infoahmadi-projects
 * Author URI: https://github.com/infoahmadi-projects
 * License: GPL-2.0-or-later
 * Text Domain: guard-site-24
 */

// Prevent direct access to the plugin file.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define shared plugin paths and version values used by the modules.
define( 'GS24_VERSION', '1.0.0' );
define( 'GS24_FILE', __FILE__ );
define( 'GS24_PATH', plugin_dir_path( __FILE__ ) );
define( 'GS24_URL', plugin_dir_url( __FILE__ ) );

// Load each core module from the includes directory.
$gs24_modules = array(
    'class-gs24-helper.php',
    'class-gs24-database.php',
    'class-gs24-settings.php',
    'class-gs24-activity-log.php',
    'class-gs24-login-protection.php',
    'class-gs24-firewall.php',
    'class-gs24-security-scan.php',
);

foreach ( $gs24_modules as $gs24_module ) {
    require_once GS24_PATH . 'includes/' . $gs24_module;
}

// Load the administration interface from its dedicated directory.
require_once GS24_PATH . 'admin/class-gs24-admin.php';

// Create the activity logger after WordPress has loaded its core APIs.
add_action(
    'plugins_loaded',
    function () {
        $logger = new GS24_Activity_Log();

        // Register request-level protection modules.
        ( new GS24_Login_Protection( $logger ) )->register_hooks();
        ( new GS24_Firewall( $logger ) )->register_hooks();

        // Register dashboard pages and administrative actions.
        ( new GS24_Admin( $logger ) )->register_hooks();
    }
);

// Create the custom activity table when the plugin is activated.
register_activation_hook( GS24_FILE, array( 'GS24_Database', 'install' ) );

// Clear plugin-owned scheduled events when the plugin is deactivated.
register_deactivation_hook(
    GS24_FILE,
    function () {
        wp_clear_scheduled_hook( 'gs24_scheduled_scan' );
    }
);
