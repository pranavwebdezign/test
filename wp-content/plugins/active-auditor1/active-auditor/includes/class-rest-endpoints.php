<?php
/**
 * REST API Endpoints for Active Auditor
 */

if (!defined('ABSPATH')) {
    exit;
}

class Active_Auditor_REST_Endpoints {

    const NAMESPACE = 'active-auditor/v1';

    /**
     * Register all REST routes
     */
    public static function register_routes() {
        register_rest_route(self::NAMESPACE, '/health', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'get_health_endpoint'),
            'permission_callback' => array(__CLASS__, 'check_token_permission'),
            'args' => array(
                'token' => array(
                    'required' => true,
                    'type' => 'string',
                    'validate_callback' => function ($token) {
                        return is_string($token);
                    },
                ),
            ),
        ));

        register_rest_route(self::NAMESPACE, '/security-audit', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'get_security_audit_endpoint'),
            'permission_callback' => array(__CLASS__, 'check_token_permission'),
            'args' => array(
                'token' => array(
                    'required' => true,
                    'type' => 'string',
                ),
            ),
        ));

        register_rest_route(self::NAMESPACE, '/updates', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'get_updates_endpoint'),
            'permission_callback' => array(__CLASS__, 'check_token_permission'),
            'args' => array(
                'token' => array(
                    'required' => true,
                    'type' => 'string',
                ),
            ),
        ));

        register_rest_route(self::NAMESPACE, '/performance', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'get_performance_endpoint'),
            'permission_callback' => array(__CLASS__, 'check_token_permission'),
            'args' => array(
                'token' => array(
                    'required' => true,
                    'type' => 'string',
                ),
            ),
        ));

        register_rest_route(self::NAMESPACE, '/full-report', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'get_full_report_endpoint'),
            'permission_callback' => array(__CLASS__, 'check_token_permission'),
            'args' => array(
                'token' => array(
                    'required' => true,
                    'type' => 'string',
                ),
            ),
        ));

        register_rest_route(self::NAMESPACE, '/status', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'get_status_endpoint'),
            'permission_callback' => '__return_true',
        ));

        // Public guest endpoints (no token required)
        register_rest_route(self::NAMESPACE, '/guest/lighthouse', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'get_lighthouse_endpoint'),
            'permission_callback' => '__return_true',
        ));

        register_rest_route(self::NAMESPACE, '/guest/seo', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'get_seo_endpoint'),
            'permission_callback' => '__return_true',
        ));

        register_rest_route(self::NAMESPACE, '/guest/google-services', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'get_google_services_endpoint'),
            'permission_callback' => '__return_true',
        ));

        // Authenticated endpoints (token required)
        register_rest_route(self::NAMESPACE, '/wordfence', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'get_wordfence_endpoint'),
            'permission_callback' => array(__CLASS__, 'check_token_permission'),
            'args' => array(
                'token' => array(
                    'required' => true,
                    'type' => 'string',
                ),
            ),
        ));

        register_rest_route(self::NAMESPACE, '/lighthouse', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'get_lighthouse_authenticated_endpoint'),
            'permission_callback' => array(__CLASS__, 'check_token_permission'),
            'args' => array(
                'token' => array(
                    'required' => true,
                    'type' => 'string',
                ),
            ),
        ));

        register_rest_route(self::NAMESPACE, '/seo-analysis', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'get_seo_analysis_endpoint'),
            'permission_callback' => array(__CLASS__, 'check_token_permission'),
            'args' => array(
                'token' => array(
                    'required' => true,
                    'type' => 'string',
                ),
            ),
        ));

        // ── POST endpoints for remote update management ───────────────────────

        register_rest_route(self::NAMESPACE, '/update-plugin', array(
            'methods'             => 'POST',
            'callback'            => array(__CLASS__, 'post_update_plugin'),
            'permission_callback' => array(__CLASS__, 'check_token_permission'),
            'args'                => array(
                'token'  => array('required' => true, 'type' => 'string'),
                'plugin' => array('required' => true, 'type' => 'string',
                    'description' => 'Plugin file path, e.g. woocommerce/woocommerce.php'),
            ),
        ));

        register_rest_route(self::NAMESPACE, '/update-theme', array(
            'methods'             => 'POST',
            'callback'            => array(__CLASS__, 'post_update_theme'),
            'permission_callback' => array(__CLASS__, 'check_token_permission'),
            'args'                => array(
                'token' => array('required' => true, 'type' => 'string'),
                'theme' => array('required' => true, 'type' => 'string',
                    'description' => 'Theme stylesheet slug, e.g. twentytwentythree'),
            ),
        ));

        register_rest_route(self::NAMESPACE, '/update-core', array(
            'methods'             => 'POST',
            'callback'            => array(__CLASS__, 'post_update_core'),
            'permission_callback' => array(__CLASS__, 'check_token_permission'),
            'args'                => array(
                'token' => array('required' => true, 'type' => 'string'),
            ),
        ));

        register_rest_route(self::NAMESPACE, '/configure-wordfence-key', array(
            'methods'             => 'POST',
            'callback'            => array(__CLASS__, 'post_configure_wordfence_key'),
            'permission_callback' => array(__CLASS__, 'check_token_permission'),
            'args'                => array(
                'token'   => array('required' => true, 'type' => 'string'),
                'api_key' => array('required' => true, 'type' => 'string'),
            ),
        ));

        register_rest_route(self::NAMESPACE, '/configure-lighthouse-key', array(
            'methods'             => 'POST',
            'callback'            => array(__CLASS__, 'post_configure_lighthouse_key'),
            'permission_callback' => array(__CLASS__, 'check_token_permission'),
            'args'                => array(
                'token'   => array('required' => true, 'type' => 'string'),
                'api_key' => array('required' => true, 'type' => 'string'),
            ),
        ));
    }

    /**
     * Check token permission callback
     *
     * @param WP_REST_Request $request Request object
     * @return bool
     */
    public static function check_token_permission($request) {
        $token = $request->get_param('token');
        
        if (empty($token)) {
            return new WP_Error(
                'missing_token',
                __('API token is required', 'active-auditor'),
                array('status' => 401)
            );
        }
        
        if (!Active_Auditor_Authentication::validate_token($token)) {
            Active_Auditor_Authentication::log_event('invalid_token_attempt', array(
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'token_preview' => substr($token, 0, 4) . '***',
            ));
            
            return new WP_Error(
                'invalid_token',
                __('Invalid API token', 'active-auditor'),
                array('status' => 403)
            );
        }
        
        return true;
    }

    /**
     * Get health endpoint
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public static function get_health_endpoint($request) {
        $health_data = Active_Auditor_Health_Data::get_health_data_cached();
        
        return rest_ensure_response(array(
            'success' => true,
            'data' => $health_data,
            'timestamp' => current_time('mysql'),
        ));
    }

    /**
     * Get security audit endpoint
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public static function get_security_audit_endpoint($request) {
        $audit_data = Active_Auditor_Security_Audit::get_security_audit();
        
        return rest_ensure_response(array(
            'success' => true,
            'data' => $audit_data,
            'timestamp' => current_time('mysql'),
        ));
    }

    /**
     * Get updates endpoint
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public static function get_updates_endpoint($request) {
        $updates_data = Active_Auditor_Update_Tracker::get_updates_tracking();
        
        return rest_ensure_response(array(
            'success' => true,
            'data' => $updates_data,
            'timestamp' => current_time('mysql'),
        ));
    }

    /**
     * Get performance endpoint
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public static function get_performance_endpoint($request) {
        $health_data = Active_Auditor_Health_Data::get_health_data_cached();
        
        $performance = array(
            'database' => $health_data['database'] ?? array(),
            'performance' => $health_data['performance'] ?? array(),
            'php' => $health_data['php'] ?? array(),
        );
        
        return rest_ensure_response(array(
            'success' => true,
            'data' => $performance,
            'timestamp' => current_time('mysql'),
        ));
    }

    /**
     * Get full report endpoint
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public static function get_full_report_endpoint($request) {
        $full_report = array(
            'health' => Active_Auditor_Health_Data::get_health_data_cached(),
            'security' => Active_Auditor_Security_Audit::get_security_audit(),
            'updates' => Active_Auditor_Update_Tracker::get_updates_tracking(),
            'generated_at' => current_time('mysql'),
            'generated_at_gmt' => current_time('mysql', true),
        );
        
        return rest_ensure_response(array(
            'success' => true,
            'data' => $full_report,
        ));
    }

    /**
     * Get status endpoint (no token required)
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public static function get_status_endpoint($request) {
        $has_token = !empty(get_option('aa_api_token'));
        
        return rest_ensure_response(array(
            'success' => true,
            'status' => 'active',
            'version' => ACTIVE_AUDITOR_VERSION,
            'has_token' => $has_token,
            'site_url' => site_url(),
            'domain' => parse_url(get_site_url(), PHP_URL_HOST),
        ));
    }

    /**
     * Get Lighthouse endpoint (public/guest - no token required)
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public static function get_lighthouse_endpoint($request) {
        $lighthouse_data = Active_Auditor_Lighthouse::get_summary();
        
        return rest_ensure_response(array(
            'success' => true,
            'data' => $lighthouse_data,
            'timestamp' => current_time('mysql'),
            'public' => true,
        ));
    }

    /**
     * Get SEO endpoint (public/guest - no token required)
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public static function get_seo_endpoint($request) {
        $url = $request->get_param('url');
        $seo_data = Active_Auditor_SEO_Scanner::scan_page($url);
        
        // For public endpoint, return only the summary
        $public_data = array(
            'url' => isset($seo_data['url']) ? $seo_data['url'] : home_url(),
            'overall_score' => isset($seo_data['overall_score']) ? $seo_data['overall_score'] : 75,
            'overall_status' => isset($seo_data['overall_status']) ? $seo_data['overall_status'] : 'amber',
            'page_title' => isset($seo_data['page_title']) ? $seo_data['page_title'] : array(),
            'meta_description' => isset($seo_data['meta_description']) ? $seo_data['meta_description'] : array(),
            'h1_tags' => isset($seo_data['h1_tags']) ? $seo_data['h1_tags'] : array(),
            'images' => isset($seo_data['images']) ? $seo_data['images'] : array(),
            'links' => isset($seo_data['links']) ? $seo_data['links'] : array(),
        );
        
        return rest_ensure_response(array(
            'success' => true,
            'data' => $public_data,
            'timestamp' => current_time('mysql'),
            'public' => true,
        ));
    }

    /**
     * Get Google Services endpoint (public/guest - no token required)
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public static function get_google_services_endpoint($request) {
        $url = $request->get_param('url');
        $services = Active_Auditor_Google_Services::detect_services($url);
        
        return rest_ensure_response(array(
            'success' => true,
            'data' => $services,
            'timestamp' => current_time('mysql'),
            'public' => true,
        ));
    }

    /**
     * Get Wordfence vulnerabilities endpoint (authenticated)
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public static function get_wordfence_endpoint($request) {
        $summary = Active_Auditor_Wordfence::get_security_summary();
        
        return rest_ensure_response(array(
            'success' => true,
            'data' => $summary,
            'timestamp' => current_time('mysql'),
            'authenticated' => true,
        ));
    }

    /**
     * Get Lighthouse endpoint (authenticated - returns full data)
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public static function get_lighthouse_authenticated_endpoint($request) {
        $url = $request->get_param('url');
        $lighthouse_data = Active_Auditor_Lighthouse::run_audit($url);
        
        return rest_ensure_response(array(
            'success' => true,
            'data' => $lighthouse_data,
            'timestamp' => current_time('mysql'),
            'authenticated' => true,
        ));
    }

    /**
     * Get SEO analysis endpoint (authenticated - returns full analysis)
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public static function get_seo_analysis_endpoint($request) {
        $url = $request->get_param('url');
        $seo_data = Active_Auditor_SEO_Scanner::scan_page($url);

        return rest_ensure_response(array(
            'success' => true,
            'data' => $seo_data,
            'timestamp' => current_time('mysql'),
            'authenticated' => true,
        ));
    }

    // ───────────────────────────────────────────────────────────────────────────
    // Helper: bootstrap admin context + WP_Filesystem for upgrades
    // ───────────────────────────────────────────────────────────────────────────
    private static function bootstrap_upgrade_context() {
        global $wp_filesystem;

        // Set the current user to the first administrator so WP capability checks pass
        $admins = get_users([ 'role' => 'administrator', 'number' => 1, 'fields' => 'ID' ]);
        if (!empty($admins)) {
            wp_set_current_user($admins[0]);
        }

        // Load required files
        if (!class_exists('WP_Upgrader')) {
            require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        }
        if (!function_exists('request_filesystem_credentials')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }

        // Initialize WP_Filesystem in direct mode (no FTP credentials needed)
        add_filter('filesystem_method', function() { return 'direct'; });
        if (empty($wp_filesystem)) {
            WP_Filesystem();
        }
    }

    // ───────────────────────────────────────────────────────────────────────────
    // POST callback: Update a specific plugin
    // ───────────────────────────────────────────────────────────────────────────
    public static function post_update_plugin(WP_REST_Request $request) {
        $plugin_file = sanitize_text_field($request->get_param('plugin'));
        if (empty($plugin_file)) {
            return new WP_Error('missing_plugin', 'plugin parameter is required.', array('status' => 400));
        }

        self::bootstrap_upgrade_context();
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
        require_once ABSPATH . 'wp-admin/includes/update.php';

        $skin     = new WP_Ajax_Upgrader_Skin();
        $upgrader = new Plugin_Upgrader($skin);
        $result   = $upgrader->upgrade($plugin_file);

        if (is_wp_error($result)) {
            return new WP_Error('upgrade_failed', $result->get_error_message(), array('status' => 500));
        }

        if ($result === false) {
            $msgs = $skin->get_upgrade_messages();
            $errorMsg = !empty($msgs) ? implode(', ', $msgs) : 'Plugin upgrade returned false — it may already be at the latest version.';
            // If it says "no update" treat it as success
            if (stripos($errorMsg, 'no update') !== false || stripos($errorMsg, 'latest') !== false) {
                return rest_ensure_response(array(
                    'success' => true,
                    'plugin'  => $plugin_file,
                    'message' => 'Plugin is already at the latest version.',
                ));
            }
            return new WP_Error('upgrade_failed', $errorMsg, array('status' => 500));
        }

        return rest_ensure_response(array(
            'success' => true,
            'plugin'  => $plugin_file,
            'message' => 'Plugin updated successfully.',
        ));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST callback: Update a specific theme
    // ─────────────────────────────────────────────────────────────────────────
    public static function post_update_theme(WP_REST_Request $request) {
        $theme_slug = sanitize_text_field($request->get_param('theme'));
        if (empty($theme_slug)) {
            return new WP_Error('missing_theme', 'theme parameter is required.', array('status' => 400));
        }

        self::bootstrap_upgrade_context();
        require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        require_once ABSPATH . 'wp-admin/includes/theme.php';
        require_once ABSPATH . 'wp-admin/includes/update.php';

        $skin     = new WP_Ajax_Upgrader_Skin();
        $upgrader = new Theme_Upgrader($skin);
        $result   = $upgrader->upgrade($theme_slug);

        if (is_wp_error($result)) {
            return new WP_Error('upgrade_failed', $result->get_error_message(), array('status' => 500));
        }

        if ($result === false) {
            $msgs = $skin->get_upgrade_messages();
            $errorMsg = !empty($msgs) ? implode(', ', $msgs) : 'Theme upgrade returned false — it may already be at the latest version.';
            // If it says "no update" treat it as success
            if (stripos($errorMsg, 'no update') !== false || stripos($errorMsg, 'latest') !== false) {
                return rest_ensure_response(array(
                    'success' => true,
                    'theme'   => $theme_slug,
                    'message' => 'Theme is already at the latest version.',
                ));
            }
            return new WP_Error('upgrade_failed', $errorMsg, array('status' => 500));
        }

        return rest_ensure_response(array(
            'success' => true,
            'theme'   => $theme_slug,
            'message' => 'Theme updated successfully.',
        ));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST callback: Update WordPress core
    // ─────────────────────────────────────────────────────────────────────────
    public static function post_update_core(WP_REST_Request $request) {
        self::bootstrap_upgrade_context();
        require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        require_once ABSPATH . 'wp-admin/includes/update.php';

        // Force a fresh update check
        wp_version_check(array(), true);
        $updates = get_preferred_from_update_core();

        if (!isset($updates->response) || $updates->response !== 'upgrade') {
            return rest_ensure_response(array(
                'success' => true,
                'message' => 'WordPress core is already up to date.',
            ));
        }

        $skin     = new WP_Ajax_Upgrader_Skin();
        $upgrader = new Core_Upgrader($skin);
        $result   = $upgrader->upgrade($updates->packages->full ?? $updates);

        if (is_wp_error($result)) {
            return new WP_Error('upgrade_failed', $result->get_error_message(), array('status' => 500));
        }

        return rest_ensure_response(array(
            'success'     => true,
            'new_version' => $updates->current ?? 'unknown',
            'message'     => 'WordPress core updated successfully.',
        ));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST callback: Store Wordfence API key
    // ─────────────────────────────────────────────────────────────────────────
    public static function post_configure_wordfence_key(WP_REST_Request $request) {
        $api_key = sanitize_text_field($request->get_param('api_key'));
        if (empty($api_key)) {
            return new WP_Error('missing_key', 'api_key is required.', array('status' => 400));
        }
        update_option('aa_wordfence_api_key', $api_key, false);

        return rest_ensure_response(array(
            'success' => true,
            'message' => 'Wordfence API key stored.',
        ));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST callback: Store Lighthouse (PageSpeed) API key
    // ─────────────────────────────────────────────────────────────────────────
    public static function post_configure_lighthouse_key(WP_REST_Request $request) {
        $api_key = sanitize_text_field($request->get_param('api_key'));
        if (empty($api_key)) {
            return new WP_Error('missing_key', 'api_key is required.', array('status' => 400));
        }
        update_option('aa_lighthouse_api_key', $api_key, false);

        return rest_ensure_response(array(
            'success' => true,
            'message' => 'Lighthouse API key stored.',
        ));
    }
}