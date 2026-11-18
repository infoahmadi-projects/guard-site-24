<?php
/**
 * Administrative dashboard, navigation, and protected form handlers.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class GS24_Admin {

    /** @var GS24_Activity_Log */
    private $logger;

    /**
     * Receive the shared logger so admin actions use the same event service.
     *
     * @param GS24_Activity_Log $logger Activity logging service.
     */
    public function __construct( $logger ) {
        $this->logger = $logger;
    }

    /**
     * Register dashboard hooks and administrative form endpoints.
     *
     * @return void
     */
    public function register_hooks() {
        add_action( 'admin_menu', array( $this, 'register_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_action( 'admin_init', array( new GS24_Settings(), 'register' ) );
        add_action( 'admin_post_gs24_run_scan', array( $this, 'handle_scan' ) );
        add_action( 'admin_post_gs24_export_csv', array( $this, 'handle_csv_export' ) );
    }

    /**
     * Add the plugin's top-level menu for administrators.
     *
     * @return void
     */
    public function register_menu() {
        add_menu_page(
            __( 'Guard Site 24', 'guard-site-24' ),
            __( 'Guard Site 24', 'guard-site-24' ),
            'manage_options',
            'guard-site-24',
            array( $this, 'render_dashboard' ),
            'dashicons-shield-alt',
            58
        );
    }

    /**
     * Load styles and scripts only on the plugin dashboard page.
     *
     * @param string $hook Current admin page hook.
     * @return void
     */
    public function enqueue_assets( $hook ) {
        if ( 'toplevel_page_guard-site-24' !== $hook ) {
            return;
        }

        wp_enqueue_style(
            'gs24-admin',
            GS24_URL . 'admin/css/admin.css',
            array(),
            GS24_VERSION
        );

        wp_enqueue_script(
            'gs24-admin',
            GS24_URL . 'admin/js/admin.js',
            array(),
            GS24_VERSION,
            true
        );
    }

    /**
     * Verify permissions and nonce, run the basic scan, and save its findings.
     *
     * @return void
     */
    public function handle_scan() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You are not allowed to perform this action.', 'guard-site-24' ) );
        }

        check_admin_referer( 'gs24_run_scan' );

        $scanner  = new GS24_Security_Scan();
        $findings = $scanner->run();

        update_option( 'gs24_last_scan', $findings, false );
        update_option( 'gs24_last_scan_time', current_time( 'mysql' ), false );

        $this->logger->record( 'manual_scan', 'The administrator ran a basic configuration scan.' );

        wp_safe_redirect( add_query_arg( 'gs24_scan', 'complete', admin_url( 'admin.php?page=guard-site-24' ) ) );
        exit;
    }

    /**
     * Export recent activity as a CSV file after checking administrator access.
     *
     * @return void
     */
    public function handle_csv_export() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You are not allowed to export activity logs.', 'guard-site-24' ) );
        }

        check_admin_referer( 'gs24_export_csv' );

        $rows = $this->logger->get_recent( 500 );

        // Send download headers before writing CSV content.
        nocache_headers();
        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename=guard-site-24-activity.csv' );

        $output = fopen( 'php://output', 'w' );

        // Use a UTF-8 byte-order mark to improve spreadsheet compatibility.
        fwrite( $output, "\xEF\xBB\xBF" );
        fputcsv( $output, array( 'ID', 'Event Type', 'Event Message', 'IP Address', 'Created At (UTC)' ) );

        foreach ( $rows as $row ) {
            fputcsv(
                $output,
                array(
                    absint( $row['id'] ),
                    sanitize_text_field( $row['event_type'] ),
                    sanitize_text_field( $row['event_message'] ),
                    sanitize_text_field( $row['ip_address'] ),
                    sanitize_text_field( $row['created_at'] ),
                )
            );
        }

        fclose( $output );
        exit;
    }

    /**
     * Render the main dashboard and its feature sections.
     *
     * @return void
     */
    public function render_dashboard() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'overview';
        $tabs = array(
            'overview'  => __( 'Overview', 'guard-site-24' ),
            'scanner'   => __( 'Security Checks', 'guard-site-24' ),
            'login'     => __( 'Login Protection', 'guard-site-24' ),
            'firewall'  => __( 'Request Filter', 'guard-site-24' ),
            'activity'  => __( 'Activity Logs', 'guard-site-24' ),
            'settings'  => __( 'Settings', 'guard-site-24' ),
        );

        if ( ! isset( $tabs[ $tab ] ) ) {
            $tab = 'overview';
        }
        ?>
        <div class="wrap gs24-wrap" dir="ltr">
            <header class="gs24-header">
                <div>
                    <p class="gs24-eyebrow"><?php esc_html_e( 'WORDPRESS SECURITY TOOLKIT', 'guard-site-24' ); ?></p>
                    <h1><?php esc_html_e( 'Guard Site 24', 'guard-site-24' ); ?></h1>
                    <p><?php esc_html_e( 'A clear overview of selected security settings and activity.', 'guard-site-24' ); ?></p>
                </div>
                <span class="gs24-version"><?php echo esc_html( 'Version ' . GS24_VERSION ); ?></span>
            </header>

            <nav class="nav-tab-wrapper gs24-tabs" aria-label="<?php esc_attr_e( 'Plugin sections', 'guard-site-24' ); ?>">
                <?php foreach ( $tabs as $key => $label ) : ?>
                    <a class="nav-tab <?php echo $tab === $key ? 'nav-tab-active' : ''; ?>"
                       href="<?php echo esc_url( add_query_arg( array( 'page' => 'guard-site-24', 'tab' => $key ), admin_url( 'admin.php' ) ) ); ?>">
                        <?php echo esc_html( $label ); ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <?php if ( isset( $_GET['gs24_scan'] ) && 'complete' === sanitize_key( wp_unslash( $_GET['gs24_scan'] ) ) ) : ?>
                <div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'The configuration checks have been completed.', 'guard-site-24' ); ?></p></div>
            <?php endif; ?>

            <main class="gs24-content">
                <?php
                switch ( $tab ) {
                    case 'scanner':
                        $this->render_scanner();
                        break;
                    case 'login':
                        $this->render_login_protection();
                        break;
                    case 'firewall':
                        $this->render_request_filter();
                        break;
                    case 'activity':
                        $this->render_activity();
                        break;
                    case 'settings':
                        $this->render_settings();
                        break;
                    default:
                        $this->render_overview();
                        break;
                }
                ?>
            </main>
            <footer class="gs24-footer">
                <?php esc_html_e( 'Guard Site 24 provides basic monitoring features and does not replace a professional security service.', 'guard-site-24' ); ?>
            </footer>
        </div>
        <?php
    }

    /**
     * Display summary cards and the latest scan status.
     *
     * @return void
     */
    private function render_overview() {
        $settings = GS24_Helper::get_settings();
        $scan_time = get_option( 'gs24_last_scan_time', '' );
        $logs = $this->logger->get_recent( 5 );
        ?>
        <section class="gs24-card-grid">
            <article class="gs24-card">
                <span class="gs24-card-label"><?php esc_html_e( 'Login Attempt Limit', 'guard-site-24' ); ?></span>
                <strong><?php echo esc_html( $settings['login_limit'] ); ?></strong>
                <p><?php esc_html_e( 'Failed attempts before temporary lockout.', 'guard-site-24' ); ?></p>
            </article>
            <article class="gs24-card">
                <span class="gs24-card-label"><?php esc_html_e( 'Lockout Duration', 'guard-site-24' ); ?></span>
                <strong><?php echo esc_html( $settings['lockout_minutes'] ); ?> min</strong>
                <p><?php esc_html_e( 'Temporary restriction after the configured threshold.', 'guard-site-24' ); ?></p>
            </article>
            <article class="gs24-card">
                <span class="gs24-card-label"><?php esc_html_e( 'Request Filter Mode', 'guard-site-24' ); ?></span>
                <strong><?php echo esc_html( ucfirst( $settings['firewall_mode'] ) ); ?></strong>
                <p><?php esc_html_e( 'Log mode is recommended before enabling blocking.', 'guard-site-24' ); ?></p>
            </article>
        </section>

        <section class="gs24-panel">
            <h2><?php esc_html_e( 'Configuration Check', 'guard-site-24' ); ?></h2>
            <p>
                <?php
                echo $scan_time
                    ? esc_html( sprintf( 'Last run: %s', $scan_time ) )
                    : esc_html__( 'No configuration check has been run yet.', 'guard-site-24' );
                ?>
            </p>
            <a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=guard-site-24&tab=scanner' ) ); ?>">
                <?php esc_html_e( 'Open Security Checks', 'guard-site-24' ); ?>
            </a>
        </section>

        <section class="gs24-panel">
            <h2><?php esc_html_e( 'Recent Activity', 'guard-site-24' ); ?></h2>
            <?php $this->render_log_table( $logs ); ?>
        </section>
        <?php
    }

    /**
     * Display the basic configuration scan form and its latest findings.
     *
     * @return void
     */
    private function render_scanner() {
        $findings = get_option( 'gs24_last_scan', array() );
        ?>
        <section class="gs24-panel">
            <h2><?php esc_html_e( 'Basic Security Configuration Checks', 'guard-site-24' ); ?></h2>
            <p><?php esc_html_e( 'These checks review selected WordPress configuration values. They do not scan every file or detect all malware.', 'guard-site-24' ); ?></p>
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <input type="hidden" name="action" value="gs24_run_scan">
                <?php wp_nonce_field( 'gs24_run_scan' ); ?>
                <?php submit_button( __( 'Run Configuration Checks', 'guard-site-24' ), 'primary', 'submit', false ); ?>
            </form>
        </section>

        <?php if ( is_array( $findings ) && ! empty( $findings ) ) : ?>
            <section class="gs24-panel">
                <h2><?php esc_html_e( 'Latest Findings', 'guard-site-24' ); ?></h2>
                <div class="gs24-findings">
                    <?php foreach ( $findings as $finding ) : ?>
                        <article class="gs24-finding gs24-status-<?php echo esc_attr( $finding['status'] ); ?>">
                            <strong><?php echo esc_html( $finding['title'] ); ?></strong>
                            <p><?php echo esc_html( $finding['details'] ); ?></p>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
        <?php
    }

    /**
     * Explain the current login protection settings to the administrator.
     *
     * @return void
     */
    private function render_login_protection() {
        $settings = GS24_Helper::get_settings();
        ?>
        <section class="gs24-panel">
            <h2><?php esc_html_e( 'Login Protection', 'guard-site-24' ); ?></h2>
            <p><?php esc_html_e( 'Guard Site 24 counts failed login attempts by visitor IP and temporarily restricts further authentication attempts after the configured limit.', 'guard-site-24' ); ?></p>
            <ul class="gs24-detail-list">
                <li><strong><?php esc_html_e( 'Failed attempt threshold:', 'guard-site-24' ); ?></strong> <?php echo esc_html( $settings['login_limit'] ); ?></li>
                <li><strong><?php esc_html_e( 'Lockout duration:', 'guard-site-24' ); ?></strong> <?php echo esc_html( $settings['lockout_minutes'] ); ?> <?php esc_html_e( 'minutes', 'guard-site-24' ); ?></li>
            </ul>
            <p class="description"><?php esc_html_e( 'IP-based controls can behave differently behind reverse proxies or shared networks. Configure trusted IP addresses carefully.', 'guard-site-24' ); ?></p>
        </section>
        <?php
    }

    /**
     * Explain request inspection behavior and the active firewall mode.
     *
     * @return void
     */
    private function render_request_filter() {
        $settings = GS24_Helper::get_settings();
        ?>
        <section class="gs24-panel">
            <h2><?php esc_html_e( 'Basic Request Pattern Filter', 'guard-site-24' ); ?></h2>
            <p><?php esc_html_e( 'The filter checks selected GET and POST request data for a small set of common suspicious patterns.', 'guard-site-24' ); ?></p>
            <p><strong><?php esc_html_e( 'Current mode:', 'guard-site-24' ); ?></strong> <?php echo esc_html( ucfirst( $settings['firewall_mode'] ) ); ?></p>
            <p class="description"><?php esc_html_e( 'This is not a complete Web Application Firewall. Pattern matching can produce false positives and may not detect sophisticated attacks. Start with Log Only mode.', 'guard-site-24' ); ?></p>
        </section>
        <?php
    }

    /**
     * Display recent activity and the protected CSV export action.
     *
     * @return void
     */
    private function render_activity() {
        ?>
        <section class="gs24-panel">
            <div class="gs24-panel-heading">
                <div>
                    <h2><?php esc_html_e( 'Activity Log', 'guard-site-24' ); ?></h2>
                    <p><?php esc_html_e( 'The latest recorded security-related events.', 'guard-site-24' ); ?></p>
                </div>
                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                    <input type="hidden" name="action" value="gs24_export_csv">
                    <?php wp_nonce_field( 'gs24_export_csv' ); ?>
                    <?php submit_button( __( 'Export CSV', 'guard-site-24' ), 'secondary', 'submit', false ); ?>
                </form>
            </div>
            <?php $this->render_log_table( $this->logger->get_recent( 100 ) ); ?>
        </section>
        <?php
    }

    /**
     * Render the WordPress Settings API form.
     *
     * @return void
     */
    private function render_settings() {
        ?>
        <section class="gs24-panel">
            <h2><?php esc_html_e( 'Plugin Settings', 'guard-site-24' ); ?></h2>
            <form action="options.php" method="post">
                <?php
                settings_fields( 'gs24_settings_group' );
                do_settings_sections( 'guard-site-24-settings' );
                submit_button( __( 'Save Settings', 'guard-site-24' ) );
                ?>
            </form>
        </section>
        <?php
    }

    /**
     * Render an escaped activity table shared by the overview and log screens.
     *
     * @param array $rows Activity records.
     * @return void
     */
    private function render_log_table( $rows ) {
        if ( empty( $rows ) ) {
            echo '<p>' . esc_html__( 'No activity has been recorded yet.', 'guard-site-24' ) . '</p>';
            return;
        }
        ?>
        <div class="gs24-table-wrap">
            <table class="widefat striped gs24-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Event', 'guard-site-24' ); ?></th>
                        <th><?php esc_html_e( 'Description', 'guard-site-24' ); ?></th>
                        <th><?php esc_html_e( 'IP Address', 'guard-site-24' ); ?></th>
                        <th><?php esc_html_e( 'Date (UTC)', 'guard-site-24' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $rows as $row ) : ?>
                        <tr>
                            <td><code><?php echo esc_html( $row['event_type'] ); ?></code></td>
                            <td><?php echo esc_html( $row['event_message'] ); ?></td>
                            <td><?php echo esc_html( $row['ip_address'] ); ?></td>
                            <td><?php echo esc_html( $row['created_at'] ); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
}
