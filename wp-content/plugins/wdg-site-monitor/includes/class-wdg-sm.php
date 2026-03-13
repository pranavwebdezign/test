<?php
if (!defined('ABSPATH')) { exit; }

class WDG_SM {
            /**
             * REST API endpoint: GET /wp-json/wdg-site-monitor/v1/status
             * Returns full site health, update, and security data as JSON.
             * Requires Authorization: Bearer <token> header (token is generated per site and shown in plugin settings).
             *
             * REST API endpoint: POST /wp-json/wdg-site-monitor/v1/finding-status
             * Body: { key: string, status: string }
             * Allows marking a finding as acknowledged, in progress, done, or not applicable.
             * Status values: open, acknowledged, in_progress, done, not_applicable
             * Requires Authorization: Bearer <token> header.
             *
             * Security: Only requests with the correct token in the Authorization header are allowed.
             * The token is unique per site and can be rotated in the plugin settings.
             */
        // Set or get finding status (by key)
        public static function set_finding_status($key, $status) {
            $statuses = get_option('wdg_sm_finding_status', array());
            $statuses[$key] = $status;
            update_option('wdg_sm_finding_status', $statuses, false);
        }

        public static function get_finding_status($key) {
            $statuses = get_option('wdg_sm_finding_status', array());
            return isset($statuses[$key]) ? $statuses[$key] : 'open';
        }


    const CRON_HOOK = 'wdg_sm_send_heartbeat';

    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'admin_menu'));
        add_action('admin_init', array(__CLASS__, 'maybe_register_settings'));

        add_action(self::CRON_HOOK, array(__CLASS__, 'send_heartbeat'));

        // Manual ping action from admin UI
        add_action('admin_post_wdg_sm_manual_ping', array(__CLASS__, 'handle_manual_ping'));

        // Ensure cron scheduled
        self::ensure_cron_scheduled();

        // Register REST API
        add_action('rest_api_init', array('WDG_SM_REST', 'register'));
    }

    public static function activate() {
        $opts = self::get_options();
        $changed = false;

        if (empty($opts['token'])) {
            $opts['token'] = self::generate_token();
            $changed = true;
        }
        if (empty($opts['portal_url'])) {
            $opts['portal_url'] = 'https://ai-agent-wp.lovable.app/api';
            $changed = true;
        }
        if ($changed) {
            update_option(WDG_SM_OPT, $opts, false);
        }

        self::ensure_cron_scheduled(true);
    }

    public static function deactivate() {
        $timestamp = wp_next_scheduled(self::CRON_HOOK);
        if ($timestamp) {
            wp_unschedule_event($timestamp, self::CRON_HOOK);
        }
    }

    private static function ensure_cron_scheduled($force = false) {
        $opts = self::get_options();
        $interval = isset($opts['interval']) ? sanitize_text_field($opts['interval']) : 'hourly';
        if (!in_array($interval, array('hourly', 'twicedaily', 'daily'), true)) {
            $interval = 'hourly';
        }

        $timestamp = wp_next_scheduled(self::CRON_HOOK);
        if ($timestamp && !$force) { return; }

        if ($timestamp) {
            wp_unschedule_event($timestamp, self::CRON_HOOK);
        }
        wp_schedule_event(time() + 60, $interval, self::CRON_HOOK);
    }

    public static function admin_menu() {
        add_options_page(
            'WDG Site Monitor',
            'WDG Site Monitor',
            'manage_options',
            WDG_SM_SLUG,
            array(__CLASS__, 'render_settings_page')
        );
    }

    public static function maybe_register_settings() {
        register_setting('wdg_sm_settings', WDG_SM_OPT, array(__CLASS__, 'sanitize_options'));
    }

    public static function sanitize_options($input) {
        $opts = self::get_options();

        $opts['portal_url'] = isset($input['portal_url']) ? esc_url_raw(trim($input['portal_url'])) : $opts['portal_url'];
        $opts['site_id']    = isset($input['site_id']) ? sanitize_text_field(trim($input['site_id'])) : $opts['site_id'];
        $opts['interval']   = isset($input['interval']) ? sanitize_text_field($input['interval']) : $opts['interval'];

        // token is not set from a normal settings save, only via rotate button below.
        return $opts;
    }

    public static function render_settings_page() {
        if (!current_user_can('manage_options')) { return; }
        $opts = self::get_options();

        $last = get_option('wdg_sm_last_result', array());
        $last_msg = isset($last['message']) ? esc_html($last['message']) : '—';
        $last_time = isset($last['time']) ? esc_html(date_i18n(get_option('date_format').' '.get_option('time_format'), intval($last['time']))) : '—';
        $last_code = isset($last['code']) ? intval($last['code']) : 0;

        $portal_url = esc_attr($opts['portal_url']);
        $site_id = esc_attr($opts['site_id']);
        $token_masked = self::mask_token($opts['token']);
        $interval = esc_attr($opts['interval']);
        $admin_url = esc_url(admin_url('admin-post.php'));
        $nonce = wp_create_nonce('wdg_sm_actions');

        ?>
        <div class="wrap">
            <h1>WDG Site Monitor</h1>
            <p>MVP plugin: generates a site token and sends periodic health + updates data to your portal API.</p>

            <h2>Status</h2>
            <table class="widefat striped" style="max-width: 900px;">
                <tbody>
                    <tr><th style="width:240px;">Site URL</th><td><?php echo esc_html(home_url('/')); ?></td></tr>
                    <tr><th>Token</th><td><code><?php echo esc_html($token_masked); ?></code></td></tr>
                    <tr><th>Site ID (from portal)</th><td><code><?php echo $site_id ? esc_html($site_id) : 'Not registered yet'; ?></code></td></tr>
                    <tr><th>Last heartbeat</th><td><?php echo $last_time; ?> <?php echo $last_code ? "(HTTP {$last_code})" : ""; ?> — <?php echo $last_msg; ?></td></tr>
                </tbody>
            </table>

            <h2>Settings</h2>
            <form method="post" action="options.php" style="max-width: 900px;">
                <?php settings_fields('wdg_sm_settings'); ?>
                <?php $opt_name = WDG_SM_OPT; ?>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="wdg_sm_portal_url">Portal API Base URL</label></th>
                        <td>
                            <input id="wdg_sm_portal_url" name="<?php echo esc_attr($opt_name); ?>[portal_url]" type="url" class="regular-text" value="<?php echo $portal_url; ?>" />
                            <p class="description">Example: https://ai-agent-wp.lovable.app/api</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="wdg_sm_site_id">Site ID</label></th>
                        <td>
                            <input id="wdg_sm_site_id" name="<?php echo esc_attr($opt_name); ?>[site_id]" type="text" class="regular-text" value="<?php echo $site_id; ?>" />
                            <p class="description">Returned by your portal after registration. Leave blank until you register.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="wdg_sm_interval">Heartbeat Interval</label></th>
                        <td>
                            <select id="wdg_sm_interval" name="<?php echo esc_attr($opt_name); ?>[interval]">
                                <option value="hourly" <?php selected($interval, 'hourly'); ?>>Hourly</option>
                                <option value="twicedaily" <?php selected($interval, 'twicedaily'); ?>>Twice Daily</option>
                                <option value="daily" <?php selected($interval, 'daily'); ?>>Daily</option>
                            </select>
                            <p class="description">WP-Cron depends on site traffic. For production, use a real server cron.</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>

            <h2>Actions</h2>
            <form method="post" action="<?php echo $admin_url; ?>" style="display:flex; gap:12px; align-items:center;">
                <input type="hidden" name="action" value="wdg_sm_manual_ping" />
                <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce); ?>" />
                <button class="button button-primary">Send Heartbeat Now</button>
            </form>

            <form method="post" action="<?php echo $admin_url; ?>" style="margin-top: 12px;">
                <input type="hidden" name="action" value="wdg_sm_manual_ping" />
                <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce); ?>" />
                <input type="hidden" name="rotate_token" value="1" />
                <button class="button">Rotate Token</button>
                <p class="description">Rotating token invalidates old token immediately (portal must accept the new one).</p>
            </form>

            <hr />
            <h2>Portal API endpoints expected</h2>
            <ul>
                <li><code>POST {portal_url}/sites/register</code> → returns <code>{"site_id":"..."}</code></li>
                <li><code>POST {portal_url}/sites/{site_id}/heartbeat</code> → accepts heartbeat payload</li>
            </ul>
        </div>
        <?php
    }

    public static function handle_manual_ping() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        check_admin_referer('wdg_sm_actions');

        if (isset($_POST['rotate_token']) && $_POST['rotate_token'] === '1') {
            $opts = self::get_options();
            $opts['token'] = self::generate_token();
            update_option(WDG_SM_OPT, $opts, false);
        }

        // Send heartbeat immediately (and auto-register if site_id missing)
        self::send_heartbeat(true);

        wp_safe_redirect(admin_url('options-general.php?page=' . WDG_SM_SLUG));
        exit;
    }

    private static function generate_token() {
        // 64 hex chars = 32 bytes
        return bin2hex(random_bytes(32));
    }

    private static function mask_token($token) {
        if (!$token) return '';
        $len = strlen($token);
        if ($len <= 8) return str_repeat('•', $len);
        return substr($token, 0, 4) . str_repeat('•', max(0, $len - 8)) . substr($token, -4);
    }

    private static function get_options() {
        $defaults = array(
            'portal_url' => 'https://ai-agent-wp.lovable.app/api',
            'site_id' => '',
            'token' => '',
            'interval' => 'hourly',
        );
        $opts = get_option(WDG_SM_OPT, array());
        if (!is_array($opts)) { $opts = array(); }
        return array_merge($defaults, $opts);
    }

    public static function send_heartbeat($force = false) {
        // Avoid running too often unless forced
        if (!$force) {
            $last = get_transient('wdg_sm_last_sent_at');
            if ($last && (time() - intval($last)) < 300) { // 5 min
                return;
            }
        }
        set_transient('wdg_sm_last_sent_at', time(), 10 * MINUTE_IN_SECONDS);

        $opts = self::get_options();
        $portal = rtrim($opts['portal_url'], '/');
        $token = $opts['token'];

        if (empty($portal) || empty($token)) {
            update_option('wdg_sm_last_result', array('time'=>time(), 'code'=>0, 'message'=>'Missing portal_url or token'), false);
            return;
        }

        // 1) Auto-register if no site_id yet
        if (empty($opts['site_id'])) {
            $register_url = $portal . '/sites/register';
            $payload = self::build_register_payload();

            $res = self::post_json($register_url, $payload, $token);
            if (is_wp_error($res)) {
                update_option('wdg_sm_last_result', array('time'=>time(), 'code'=>0, 'message'=>$res->get_error_message()), false);
                return;
            }

            $code = wp_remote_retrieve_response_code($res);
            $body = wp_remote_retrieve_body($res);
            $decoded = json_decode($body, true);

            if ($code >= 200 && $code < 300 && is_array($decoded) && !empty($decoded['site_id'])) {
                $opts['site_id'] = sanitize_text_field($decoded['site_id']);
                update_option(WDG_SM_OPT, $opts, false);
                update_option('wdg_sm_last_result', array('time'=>time(), 'code'=>$code, 'message'=>'Registered site_id: '.$opts['site_id']), false);
            } else {
                update_option('wdg_sm_last_result', array('time'=>time(), 'code'=>$code, 'message'=>'Register failed: '.$body), false);
                return;
            }
        }

        // 2) Send heartbeat
        $heartbeat_url = $portal . '/sites/' . rawurlencode($opts['site_id']) . '/heartbeat';
        $payload = self::build_heartbeat_payload();

        $res = self::post_json($heartbeat_url, $payload, $token);
        if (is_wp_error($res)) {
            update_option('wdg_sm_last_result', array('time'=>time(), 'code'=>0, 'message'=>$res->get_error_message()), false);
            return;
        }
        $code = wp_remote_retrieve_response_code($res);
        $body = wp_remote_retrieve_body($res);

        $msg = ($code >= 200 && $code < 300) ? 'Heartbeat sent OK' : ('Heartbeat failed: ' . $body);
        update_option('wdg_sm_last_result', array('time'=>time(), 'code'=>$code, 'message'=>$msg), false);

        // Re-schedule if interval changed
        self::ensure_cron_scheduled();
    }

    private static function post_json($url, $payload, $token) {
        $args = array(
            'timeout' => 10,
            'headers' => array(
                'Content-Type' => 'application/json; charset=utf-8',
                'Authorization' => 'Bearer ' . $token,
                'X-WDG-SM-Version' => WDG_SM_VERSION,
            ),
            'body' => wp_json_encode($payload),
        );
        return wp_remote_post($url, $args);
    }

    private static function build_register_payload() {
        return array(
            'site_url' => home_url('/'),
            'site_name' => get_bloginfo('name'),
            'wp_version' => get_bloginfo('version'),
            'php_version' => PHP_VERSION,
            'sent_at' => time(),
        );
    }

    private static function build_heartbeat_payload() {
        require_once ABSPATH . 'wp-admin/includes/update.php';
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
        require_once ABSPATH . 'wp-admin/includes/theme.php';

        // Refresh update info (safe)
        wp_version_check();
        wp_update_plugins();
        wp_update_themes();

        $core_updates = get_core_updates();
        $core_update_available = false;
        $core_latest = null;
        if (is_array($core_updates)) {
            foreach ($core_updates as $u) {
                if (isset($u->response) && $u->response === 'upgrade') {
                    $core_update_available = true;
                    $core_latest = isset($u->current) ? $u->current : null;
                    break;
                }
            }
        }

        $plugins = get_plugins();
        $plugin_updates = get_site_transient('update_plugins');
        $plugin_payload = array();
        $update_count = 0;

        foreach ($plugins as $path => $data) {
            $installed = isset($data['Version']) ? $data['Version'] : '';
            $name = isset($data['Name']) ? $data['Name'] : $path;
            $active = is_plugin_active($path);

            $latest = $installed;
            $needs_update = false;

            if (isset($plugin_updates->response[$path]) && isset($plugin_updates->response[$path]->new_version)) {
                $latest = $plugin_updates->response[$path]->new_version;
                $needs_update = version_compare($latest, $installed, '>');
                if ($needs_update) { $update_count++; }
            }

            $plugin_payload[] = array(
                'name' => $name,
                'slug' => dirname($path),
                'path' => $path,
                'active' => (bool)$active,
                'installed' => $installed,
                'latest' => $latest,
                'update_available' => (bool)$needs_update,
            );
        }

        $theme_updates = get_site_transient('update_themes');
        $themes = wp_get_themes();
        $theme_payload = array();
        $theme_update_count = 0;

        foreach ($themes as $stylesheet => $theme) {
            $installed = $theme->get('Version');
            $name = $theme->get('Name');

            $latest = $installed;
            $needs_update = false;
            if (isset($theme_updates->response[$stylesheet]['new_version'])) {
                $latest = $theme_updates->response[$stylesheet]['new_version'];
                $needs_update = version_compare($latest, $installed, '>');
                if ($needs_update) { $theme_update_count++; }
            }

            $theme_payload[] = array(
                'name' => $name,
                'slug' => $stylesheet,
                'installed' => $installed,
                'latest' => $latest,
                'update_available' => (bool)$needs_update,
            );
        }

        // Simple security checklist (MVP)
        $security = self::security_checklist();

        return array(
            'site_url' => home_url('/'),
            'sent_at' => time(),
            'health' => array(
                'status' => 'healthy', // you can compute based on rules later
                'wp_version' => get_bloginfo('version'),
                'php_version' => PHP_VERSION,
                'core_update_available' => $core_update_available,
                'core_latest' => $core_latest,
                'plugin_update_count' => $update_count,
                'theme_update_count' => $theme_update_count,
            ),
            'plugins' => $plugin_payload,
            'themes' => $theme_payload,
            'security' => $security,
        );
    }

    private static function security_checklist() {
        $checks = array();

        // 1) Disallow file edits
        $checks[] = array(
            'key' => 'DISALLOW_FILE_EDIT',
            'label' => 'Disable theme/plugin editor',
            'ok' => defined('DISALLOW_FILE_EDIT') && DISALLOW_FILE_EDIT,
            'severity' => 'medium',
        );

        // 2) XML-RPC
        $xmlrpc_enabled = apply_filters('xmlrpc_enabled', true);
        $checks[] = array(
            'key' => 'xmlrpc',
            'label' => 'XML-RPC disabled (recommended unless needed)',
            'ok' => !$xmlrpc_enabled,
            'severity' => 'low',
        );

        // 3) Default admin username (heuristic)
        $admin_user = get_user_by('login', 'admin');
        $checks[] = array(
            'key' => 'default_admin_username',
            'label' => 'No "admin" username exists',
            'ok' => !$admin_user,
            'severity' => 'high',
        );

        // 4) Debug mode
        $checks[] = array(
            'key' => 'WP_DEBUG',
            'label' => 'WP_DEBUG disabled',
            'ok' => !(defined('WP_DEBUG') && WP_DEBUG),
            'severity' => 'medium',
        );

        // 5) File permissions quick checks (best-effort)
        $wp_config = ABSPATH . 'wp-config.php';
        $config_ok = true;
        if (file_exists($wp_config)) {
            $perms = substr(sprintf('%o', @fileperms($wp_config)), -4);
            // common safe perms 0640/0644; flag if world-writable
            if ($perms === '0666' || $perms === '0777' || $perms === '0664') {
                $config_ok = false;
            }
        }
        $checks[] = array(
            'key' => 'wp_config_perms',
            'label' => 'wp-config.php not world-writable',
            'ok' => $config_ok,
            'severity' => 'high',
        );

        // summarize counts
        $counts = array('high'=>0,'medium'=>0,'low'=>0);
        foreach ($checks as $c) {
            if (empty($c['ok'])) {
                $sev = isset($c['severity']) ? $c['severity'] : 'low';
                if (isset($counts[$sev])) $counts[$sev]++;
            }
        }

        return array(
            'high' => $counts['high'],
            'medium' => $counts['medium'],
            'low' => $counts['low'],
            'checks' => $checks,
        );
    }
}
