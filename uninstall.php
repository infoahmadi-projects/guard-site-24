<?php
/**
 * Remove Guard Site 24 data only when the site owner explicitly opts in.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Preserve activity history and settings by default.
// Site owners may opt in to data removal by defining this constant in wp-config.php.
if ( ! defined( 'GS24_REMOVE_DATA_ON_UNINSTALL' ) || true !== GS24_REMOVE_DATA_ON_UNINSTALL ) {
    return;
}

global $wpdb;

// Remove plugin options when explicit data removal has been enabled.
delete_option( 'gs24_settings' );
delete_option( 'gs24_last_scan' );
delete_option( 'gs24_last_scan_time' );
delete_option( 'gs24_db_version' );

// Drop the custom activity table only after explicit opt-in.
$table_name = $wpdb->prefix . 'gs24_activity';
$wpdb->query( "DROP TABLE IF EXISTS `{$table_name}`" );
