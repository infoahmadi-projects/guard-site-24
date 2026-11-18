<?php
/**
 * Shared utility methods used by Guard Site 24 modules.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class GS24_Helper {

    /**
     * Return the visitor IP address using the direct remote address only.
     *
     * Proxy headers are intentionally not trusted automatically because
     * they can be spoofed unless a trusted proxy is configured by the site.
     *
     * @return string Sanitized IP address, or an empty string if unavailable.
     */
    public static function get_visitor_ip() {
        $ip = isset( $_SERVER['REMOTE_ADDR'] )
            ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) )
            : '';

        return filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '';
    }

    /**
     * Check whether an IP address is included in the configured trusted list.
     *
     * @param string $ip IP address to check.
     * @return bool
     */
    public static function is_trusted_ip( $ip ) {
        $settings = get_option( 'gs24_settings', array() );
        $trusted  = isset( $settings['trusted_ips'] ) ? $settings['trusted_ips'] : '';
        $items    = preg_split( '/[\r\n,]+/', (string) $trusted );

        foreach ( $items as $item ) {
            $item = trim( $item );

            if ( $item && filter_var( $item, FILTER_VALIDATE_IP ) && $item === $ip ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Return the plugin settings merged with safe defaults.
     *
     * @return array
     */
    public static function get_settings() {
        $defaults = array(
            'login_limit'       => 5,
            'lockout_minutes'   => 15,
            'firewall_mode'     => 'log',
            'trusted_ips'       => '',
            'email_alerts'      => 0,
        );

        $saved = get_option( 'gs24_settings', array() );

        return wp_parse_args( is_array( $saved ) ? $saved : array(), $defaults );
    }
}
