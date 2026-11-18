<?php
/**
 * Database installation and schema management.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class GS24_Database {

    /**
     * Create or update the activity log table during plugin activation.
     *
     * dbDelta() safely applies compatible schema changes without deleting
     * existing activity records.
     *
     * @return void
     */
    public static function install() {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $table_name      = $wpdb->prefix . 'gs24_activity';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table_name} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            event_type varchar(80) NOT NULL,
            event_message text NOT NULL,
            ip_address varchar(45) NOT NULL DEFAULT '',
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY event_type (event_type),
            KEY created_at (created_at)
        ) {$charset_collate};";

        dbDelta( $sql );

        update_option( 'gs24_db_version', GS24_VERSION );
    }

    /**
     * Return the fully prefixed activity table name.
     *
     * @return string
     */
    public static function table_name() {
        global $wpdb;
        return $wpdb->prefix . 'gs24_activity';
    }
}
