<?php
/**
 * Activity logging service for security-related events.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class GS24_Activity_Log {

    /**
     * Store a security event in the plugin's custom database table.
     *
     * @param string $type    Short event category.
     * @param string $message Human-readable event description.
     * @param string $ip      Optional IP address associated with the event.
     * @return int|false Inserted row ID, or false on failure.
     */
    public function record( $type, $message, $ip = '' ) {
        global $wpdb;

        $result = $wpdb->insert(
            GS24_Database::table_name(),
            array(
                'event_type'    => sanitize_key( $type ),
                'event_message' => sanitize_textarea_field( $message ),
                'ip_address'    => filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '',
                'created_at'    => current_time( 'mysql', true ),
            ),
            array( '%s', '%s', '%s', '%s' )
        );

        return $result ? (int) $wpdb->insert_id : false;
    }

    /**
     * Retrieve the latest activity records for the administration screen.
     *
     * @param int $limit Maximum number of rows to return.
     * @return array
     */
    public function get_recent( $limit = 100 ) {
        global $wpdb;

        $limit = absint( $limit );
        $limit = min( 500, max( 1, $limit ) );

        $table = GS24_Database::table_name();

        // The limit is converted to an integer before being inserted into SQL.
        return $wpdb->get_results(
            "SELECT * FROM {$table} ORDER BY id DESC LIMIT {$limit}",
            ARRAY_A
        );
    }
}
