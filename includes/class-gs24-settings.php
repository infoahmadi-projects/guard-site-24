<?php
/**
 * WordPress Settings API integration and input validation.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class GS24_Settings {

    /**
     * Register the plugin settings, section, and individual fields.
     *
     * @return void
     */
    public function register() {
        register_setting(
            'gs24_settings_group',
            'gs24_settings',
            array( $this, 'sanitize' )
        );

        add_settings_section(
            'gs24_general_section',
            __( 'General Protection Settings', 'guard-site-24' ),
            array( $this, 'render_section_description' ),
            'guard-site-24-settings'
        );

        add_settings_field(
            'login_limit',
            __( 'Failed Login Limit', 'guard-site-24' ),
            array( $this, 'render_login_limit' ),
            'guard-site-24-settings',
            'gs24_general_section'
        );

        add_settings_field(
            'lockout_minutes',
            __( 'Lockout Duration (Minutes)', 'guard-site-24' ),
            array( $this, 'render_lockout_duration' ),
            'guard-site-24-settings',
            'gs24_general_section'
        );

        add_settings_field(
            'firewall_mode',
            __( 'Firewall Mode', 'guard-site-24' ),
            array( $this, 'render_firewall_mode' ),
            'guard-site-24-settings',
            'gs24_general_section'
        );

        add_settings_field(
            'trusted_ips',
            __( 'Trusted IP Addresses', 'guard-site-24' ),
            array( $this, 'render_trusted_ips' ),
            'guard-site-24-settings',
            'gs24_general_section'
        );
    }

    /**
     * Sanitize submitted settings and constrain values to supported options.
     *
     * @param array $input Raw settings submitted by an administrator.
     * @return array
     */
    public function sanitize( $input ) {
        $input = is_array( $input ) ? $input : array();

        $mode = isset( $input['firewall_mode'] )
            ? sanitize_key( $input['firewall_mode'] )
            : 'log';

        return array(
            'login_limit'     => isset( $input['login_limit'] )
                ? min( 20, max( 3, absint( $input['login_limit'] ) ) )
                : 5,
            'lockout_minutes' => isset( $input['lockout_minutes'] )
                ? min( 1440, max( 1, absint( $input['lockout_minutes'] ) ) )
                : 15,
            'firewall_mode'   => in_array( $mode, array( 'log', 'block' ), true ) ? $mode : 'log',
            'trusted_ips'     => isset( $input['trusted_ips'] )
                ? sanitize_textarea_field( $input['trusted_ips'] )
                : '',
            'email_alerts'    => 0,
        );
    }

    /**
     * Display contextual guidance above the settings fields.
     *
     * @return void
     */
    public function render_section_description() {
        echo '<p>' . esc_html__( 'Configure basic login protection and request inspection behavior.', 'guard-site-24' ) . '</p>';
    }

    /** Render the failed login threshold field. */
    public function render_login_limit() {
        $settings = GS24_Helper::get_settings();
        printf(
            '<input type="number" min="3" max="20" name="gs24_settings[login_limit]" value="%1$d" class="small-text">',
            absint( $settings['login_limit'] )
        );
    }

    /** Render the temporary lockout duration field. */
    public function render_lockout_duration() {
        $settings = GS24_Helper::get_settings();
        printf(
            '<input type="number" min="1" max="1440" name="gs24_settings[lockout_minutes]" value="%1$d" class="small-text">',
            absint( $settings['lockout_minutes'] )
        );
    }

    /** Render the firewall action selector. */
    public function render_firewall_mode() {
        $settings = GS24_Helper::get_settings();
        ?>
        <select name="gs24_settings[firewall_mode]">
            <option value="log" <?php selected( $settings['firewall_mode'], 'log' ); ?>>
                <?php esc_html_e( 'Log Only (Recommended)', 'guard-site-24' ); ?>
            </option>
            <option value="block" <?php selected( $settings['firewall_mode'], 'block' ); ?>>
                <?php esc_html_e( 'Block Matching Requests', 'guard-site-24' ); ?>
            </option>
        </select>
        <?php
    }

    /** Render the trusted IP list field and explain its accepted format. */
    public function render_trusted_ips() {
        $settings = GS24_Helper::get_settings();
        printf(
            '<textarea name="gs24_settings[trusted_ips]" rows="4" class="large-text code">%1$s</textarea><p class="description">%2$s</p>',
            esc_textarea( $settings['trusted_ips'] ),
            esc_html__( 'Enter one IP address per line. Only valid IP addresses are accepted.', 'guard-site-24' )
        );
    }
}
