<?php
/**
 * Lightweight request pattern inspection.
 *
 * This module is a basic filter, not a full Web Application Firewall.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class GS24_Firewall {

    /** @var GS24_Activity_Log */
    private $logger;

    /**
     * Inject the activity logger used for matching request events.
     *
     * @param GS24_Activity_Log $logger Activity logging service.
     */
    public function __construct( $logger ) {
        $this->logger = $logger;
    }

    /**
     * Register request inspection early in the WordPress lifecycle.
     *
     * @return void
     */
    public function register_hooks() {
        add_action( 'init', array( $this, 'inspect_request' ), 1 );
    }

    /**
     * Inspect common request inputs for a small set of suspicious patterns.
     *
     * The inspection is intentionally conservative and should be tested
     * against the site's plugins and workflows before enabling block mode.
     *
     * @return void
     */
    public function inspect_request() {
        if ( is_admin() || wp_doing_cron() || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
            return;
        }

        $ip = GS24_Helper::get_visitor_ip();

        if ( ! $ip || GS24_Helper::is_trusted_ip( $ip ) ) {
            return;
        }

        $request_data = array(
            'GET'  => isset( $_GET ) ? wp_unslash( $_GET ) : array(),
            'POST' => isset( $_POST ) ? wp_unslash( $_POST ) : array(),
        );

        $payload = wp_json_encode( $request_data );

        if ( ! is_string( $payload ) || '' === $payload ) {
            return;
        }

        $patterns = array(
            '/(\bunion\b\s+\bselect\b)/i',
            '/<\s*script\b/i',
            '/javascript\s*:/i',
            '/\.\.\/\.\.\//',
        );

        foreach ( $patterns as $pattern ) {
            if ( preg_match( $pattern, $payload ) ) {
                $this->logger->record(
                    'request_match',
                    'A request matched a configured basic inspection pattern.',
                    $ip
                );

                $settings = GS24_Helper::get_settings();

                if ( 'block' === $settings['firewall_mode'] ) {
                    status_header( 403 );
                    nocache_headers();
                    wp_die(
                        esc_html__( 'This request was blocked by Guard Site 24.', 'guard-site-24' ),
                        esc_html__( 'Request Blocked', 'guard-site-24' ),
                        array( 'response' => 403 )
                    );
                }

                // Log mode records the event and allows normal request processing.
                break;
            }
        }
    }
}
