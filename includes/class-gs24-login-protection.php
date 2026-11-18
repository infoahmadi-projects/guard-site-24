<?php
/**
 * Basic brute-force mitigation for WordPress authentication attempts.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class GS24_Login_Protection {

    /** @var GS24_Activity_Log */
    private $logger;

    /**
     * Inject the shared activity logger.
     *
     * @param GS24_Activity_Log $logger Activity logging service.
     */
    public function __construct( $logger ) {
        $this->logger = $logger;
    }

    /**
     * Attach login failure, successful login, and authentication checks.
     *
     * @return void
     */
    public function register_hooks() {
        add_action( 'wp_login_failed', array( $this, 'record_failed_login' ) );
        add_action( 'wp_login', array( $this, 'record_successful_login' ), 10, 2 );
        add_filter( 'authenticate', array( $this, 'check_lockout' ), 30, 3 );
    }

    /**
     * Record a failed attempt and apply a temporary lockout at the threshold.
     *
     * @param string $username Submitted username.
     * @return void
     */
    public function record_failed_login( $username ) {
        $ip       = GS24_Helper::get_visitor_ip();
        $settings = GS24_Helper::get_settings();

        if ( ! $ip || GS24_Helper::is_trusted_ip( $ip ) ) {
            return;
        }

        $key   = 'gs24_login_' . md5( $ip );
        $count = (int) get_transient( $key ) + 1;

        set_transient( $key, $count, HOUR_IN_SECONDS );

        $this->logger->record(
            'login_failed',
            sprintf( 'Failed login attempt for username: %s', sanitize_user( $username ) ),
            $ip
        );

        if ( $count >= absint( $settings['login_limit'] ) ) {
            $lock_key = 'gs24_lock_' . md5( $ip );
            set_transient( $lock_key, 1, absint( $settings['lockout_minutes'] ) * MINUTE_IN_SECONDS );

            $this->logger->record( 'login_lockout', 'Temporary login lockout activated.', $ip );
        }
    }

    /**
     * Record a successful login and reset the IP-based failure counter.
     *
     * @param string  $username Authenticated username.
     * @param WP_User $user     Authenticated user object.
     * @return void
     */
    public function record_successful_login( $username, $user ) {
        $ip = GS24_Helper::get_visitor_ip();

        if ( $ip ) {
            delete_transient( 'gs24_login_' . md5( $ip ) );
        }

        $this->logger->record(
            'login_success',
            sprintf( 'Successful login for user: %s', sanitize_user( $username ) ),
            $ip
        );
    }

    /**
     * Reject authentication attempts from an IP with an active lockout.
     *
     * @param WP_User|WP_Error|null $user     Authentication result so far.
     * @param string                $username Submitted username.
     * @param string                $password Submitted password.
     * @return WP_User|WP_Error|null
     */
    public function check_lockout( $user, $username, $password ) {
        $ip = GS24_Helper::get_visitor_ip();

        if ( ! $ip || GS24_Helper::is_trusted_ip( $ip ) ) {
            return $user;
        }

        if ( get_transient( 'gs24_lock_' . md5( $ip ) ) ) {
            return new WP_Error(
                'gs24_login_locked',
                __( 'Login is temporarily unavailable from this IP address. Please try again later.', 'guard-site-24' )
            );
        }

        return $user;
    }
}
