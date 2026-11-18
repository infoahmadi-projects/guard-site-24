<?php
/**
 * Basic configuration checks for a WordPress installation.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class GS24_Security_Scan {

    /**
     * Run non-invasive checks against selected WordPress configuration values.
     *
     * This is a configuration review, not a file-integrity or malware scan.
     *
     * @return array List of scan findings.
     */
    public function run() {
        $findings = array();

        // Check whether the site is served over HTTPS.
        if ( is_ssl() ) {
            $findings[] = array(
                'status'  => 'good',
                'title'   => 'HTTPS is enabled',
                'details' => 'WordPress detected a secure HTTPS request.',
            );
        } else {
            $findings[] = array(
                'status'  => 'warning',
                'title'   => 'HTTPS was not detected',
                'details' => 'Review the hosting SSL certificate and WordPress site URLs.',
            );
        }

        // Check whether WordPress debugging is exposed on the live site.
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            $findings[] = array(
                'status'  => 'warning',
                'title'   => 'WP_DEBUG is enabled',
                'details' => 'Disable public debugging on production websites unless required for controlled troubleshooting.',
            );
        } else {
            $findings[] = array(
                'status'  => 'good',
                'title'   => 'WP_DEBUG is not enabled',
                'details' => 'The standard WordPress debug constant is disabled or undefined.',
            );
        }

        // Check whether the built-in file editor is disabled.
        if ( defined( 'DISALLOW_FILE_EDIT' ) && DISALLOW_FILE_EDIT ) {
            $findings[] = array(
                'status'  => 'good',
                'title'   => 'Dashboard file editor is disabled',
                'details' => 'DISALLOW_FILE_EDIT is enabled in the WordPress configuration.',
            );
        } else {
            $findings[] = array(
                'status'  => 'info',
                'title'   => 'Dashboard file editor is available',
                'details' => 'Consider disabling the built-in theme and plugin editor on production sites.',
            );
        }

        // Check whether WordPress reports available core updates.
        $updates = get_core_updates( array( 'dismissed' => false ) );
        $has_update = false;

        if ( is_array( $updates ) ) {
            foreach ( $updates as $update ) {
                if ( isset( $update->response ) && 'upgrade' === $update->response ) {
                    $has_update = true;
                    break;
                }
            }
        }

        $findings[] = $has_update
            ? array(
                'status'  => 'warning',
                'title'   => 'A WordPress core update is available',
                'details' => 'Review the available update and create a backup before applying it.',
            )
            : array(
                'status'  => 'good',
                'title'   => 'No pending WordPress core update was detected',
                'details' => 'WordPress did not report an available core update.',
            );

        return $findings;
    }
}
