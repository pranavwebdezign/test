<?php
/**
 * Admin Settings for Active Auditor
 */

if (!defined('ABSPATH')) {
    exit;
}

class Active_Auditor_Admin_Settings {

    /**
     * Initialize admin settings
     */
    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'add_admin_menu'));
        add_action('admin_init', array(__CLASS__, 'register_settings'));
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_admin_scripts'));
    }

    /**
     * Add admin menu
     */
    public static function add_admin_menu() {
        add_menu_page(
            __('Active Auditor', 'active-auditor'),
            __('Active Auditor', 'active-auditor'),
            'manage_options',
            'active-auditor',
            array(__CLASS__, 'render_main_page'),
            'dashicons-chart-line',
            25
        );

        add_submenu_page(
            'active-auditor',
            __('Settings', 'active-auditor'),
            __('Settings', 'active-auditor'),
            'manage_options',
            'active-auditor-settings',
            array(__CLASS__, 'render_settings_page')
        );

        add_submenu_page(
            'active-auditor',
            __('Health Report', 'active-auditor'),
            __('Health Report', 'active-auditor'),
            'manage_options',
            'active-auditor-health',
            array(__CLASS__, 'render_health_page')
        );
    }

    /**
     * Register settings
     */
    public static function register_settings() {
        register_setting('aa_settings', 'aa_api_token');
        register_setting('aa_settings', 'aa_lighthouse_api_key');
        register_setting('aa_settings', 'aa_wordfence_api_key');
        register_setting('aa_settings', 'aa_enable_security_audit');
        register_setting('aa_settings', 'aa_enable_update_tracking');
        register_setting('aa_settings', 'aa_enable_health_caching');
        register_setting('aa_settings', 'aa_cache_duration');
    }

    /**
     * Enqueue admin scripts and styles
     *
     * @param string $hook Current admin page
     */
    public static function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'active-auditor') === false) {
            return;
        }
        
        wp_enqueue_style(
            'active-auditor-admin',
            ACTIVE_AUDITOR_PLUGIN_URL . 'assets/admin-style.css',
            array(),
            ACTIVE_AUDITOR_VERSION
        );
    }

    /**
     * Render main page
     */
    public static function render_main_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to access this page.', 'active-auditor'));
        }

        $health_data = Active_Auditor_Health_Data::get_health_data_cached();
        $security_audit = Active_Auditor_Security_Audit::get_security_audit();
        $updates = Active_Auditor_Update_Tracker::get_updates_tracking();
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Active Auditor Dashboard', 'active-auditor'); ?></h1>
            
            <div class="aa-dashboard-grid">
                <div class="aa-card aa-health-card">
                    <h2><?php esc_html_e('System Health', 'active-auditor'); ?></h2>
                    <div class="aa-health-stat">
                        <strong><?php esc_html_e('PHP Version:', 'active-auditor'); ?></strong>
                        <span><?php echo esc_html($health_data['php']['version']); ?></span>
                    </div>
                    <div class="aa-health-stat">
                        <strong><?php esc_html_e('WordPress Version:', 'active-auditor'); ?></strong>
                        <span><?php echo esc_html($health_data['wordpress']['version']['current']); ?></span>
                    </div>
                </div>

                <div class="aa-card aa-security-card">
                    <h2><?php esc_html_e('Security Status', 'active-auditor'); ?></h2>
                    <div class="aa-vulnerabilities">
                        <?php foreach ($security_audit['vulnerabilities'] as $vuln) : ?>
                            <div class="aa-item aa-status-<?php echo esc_attr($vuln['status']); ?>">
                                <?php echo esc_html($vuln['label']); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="aa-card aa-updates-card">
                    <h2><?php esc_html_e('Updates Available', 'active-auditor'); ?></h2>
                    <div class="aa-update-stat">
                        <strong><?php esc_html_e('Plugins:', 'active-auditor'); ?></strong>
                        <span><?php echo esc_html($updates['plugins']['updates_available']); ?></span>
                    </div>
                    <div class="aa-update-stat">
                        <strong><?php esc_html_e('Themes:', 'active-auditor'); ?></strong>
                        <span><?php echo esc_html($updates['themes']['updates_available']); ?></span>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render settings page
     */
    public static function render_settings_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to access this page.', 'active-auditor'));
        }

        $api_token = get_option('aa_api_token');
        $enable_security = get_option('aa_enable_security_audit');
        $enable_updates = get_option('aa_enable_update_tracking');
        $enable_caching = get_option('aa_enable_health_caching');
        $cache_duration = get_option('aa_cache_duration');
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Active Auditor Settings', 'active-auditor'); ?></h1>
            
            <form method="post" action="options.php">
                <?php settings_fields('aa_settings'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="aa_api_token"><?php esc_html_e('API Token', 'active-auditor'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="aa_api_token" name="aa_api_token" 
                                   value="<?php echo esc_attr($api_token); ?>" 
                                   class="regular-text code" readonly />
                            <p class="description">
                                <?php esc_html_e('This token is used by the Chrome extension to authenticate requests. Generate a new token using the button below.', 'active-auditor'); ?>
                            </p>
                            <button type="button" class="button" onclick="aa_regenerate_token()">
                                <?php esc_html_e('Generate New Token', 'active-auditor'); ?>
                            </button>
                            <button type="button" class="button" onclick="aa_copy_token()">
                                <?php esc_html_e('Copy Token', 'active-auditor'); ?>
                            </button>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="aa_lighthouse_api_key"><?php esc_html_e('Google Lighthouse API Key', 'active-auditor'); ?></label>
                        </th>
                        <td>
                            <input type="password" id="aa_lighthouse_api_key" name="aa_lighthouse_api_key" 
                                   value="<?php echo esc_attr(get_option('aa_lighthouse_api_key')); ?>" 
                                   class="regular-text" />
                            <p class="description">
                                <?php esc_html_e('Get a free API key from Google Cloud Console. Enable PageSpeed Insights API on your project.', 'active-auditor'); ?>
                                <a href="https://console.cloud.google.com/apis/library/pagespeedonline.googleapis.com" target="_blank">
                                    <?php esc_html_e('Get API Key', 'active-auditor'); ?>
                                </a>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="aa_wordfence_api_key"><?php esc_html_e('Wordfence Intelligence API Key', 'active-auditor'); ?></label>
                        </th>
                        <td>
                            <input type="password" id="aa_wordfence_api_key" name="aa_wordfence_api_key" 
                                   value="<?php echo esc_attr(get_option('aa_wordfence_api_key')); ?>" 
                                   class="regular-text" />
                            <p class="description">
                                <?php esc_html_e('Get a free API key from Wordfence Intelligence. Provides real-time vulnerability data for plugins and themes.', 'active-auditor'); ?>
                                <a href="https://www.wordfence.com/intelligence/api/" target="_blank">
                                    <?php esc_html_e('Get API Key', 'active-auditor'); ?>
                                </a>
                                <br />
                                <small><?php esc_html_e('Without an API key, only local vulnerability checks are performed.', 'active-auditor'); ?></small>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="aa_enable_security_audit">
                                <?php esc_html_e('Security Audit', 'active-auditor'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="checkbox" id="aa_enable_security_audit" name="aa_enable_security_audit" 
                                   value="1" <?php checked($enable_security, '1'); ?> />
                            <label for="aa_enable_security_audit">
                                <?php esc_html_e('Enable security vulnerability checks', 'active-auditor'); ?>
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="aa_enable_update_tracking">
                                <?php esc_html_e('Update Tracking', 'active-auditor'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="checkbox" id="aa_enable_update_tracking" name="aa_enable_update_tracking" 
                                   value="1" <?php checked($enable_updates, '1'); ?> />
                            <label for="aa_enable_update_tracking">
                                <?php esc_html_e('Enable plugin, theme, and core update tracking', 'active-auditor'); ?>
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="aa_enable_health_caching">
                                <?php esc_html_e('Health Data Caching', 'active-auditor'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="checkbox" id="aa_enable_health_caching" name="aa_enable_health_caching" 
                                   value="1" <?php checked($enable_caching, '1'); ?> />
                            <label for="aa_enable_health_caching">
                                <?php esc_html_e('Cache health data for improved performance', 'active-auditor'); ?>
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="aa_cache_duration">
                                <?php esc_html_e('Cache Duration', 'active-auditor'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="number" id="aa_cache_duration" name="aa_cache_duration" 
                                   value="<?php echo esc_attr($cache_duration); ?>" class="small-text" /> seconds
                            <p class="description">
                                <?php esc_html_e('How long to cache health data (default: 3600 seconds = 1 hour)', 'active-auditor'); ?>
                            </p>
                        </td>
                    </tr>
                </table>

                <?php submit_button(); ?>
            </form>
        </div>

        <script>
            function aa_copy_token() {
                const token = document.getElementById('aa_api_token').value;
                navigator.clipboard.writeText(token).then(() => {
                    alert('<?php esc_html_e('Token copied to clipboard!', 'active-auditor'); ?>');
                });
            }

            function aa_regenerate_token() {
                if (confirm('<?php esc_html_e('Are you sure you want to generate a new token? Applications using the old token will stop working.', 'active-auditor'); ?>')) {
                    // This would be implemented with AJAX
                    alert('<?php esc_html_e('Token regeneration coming soon!', 'active-auditor'); ?>');
                }
            }
        </script>
        <?php
    }

    /**
     * Render health page
     */
    public static function render_health_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to access this page.', 'active-auditor'));
        }

        $health_data = Active_Auditor_Health_Data::get_health_data();
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Health Report', 'active-auditor'); ?></h1>
            
            <div class="aa-health-report">
                <pre><?php echo esc_html(wp_json_encode($health_data, JSON_PRETTY_PRINT)); ?></pre>
            </div>
        </div>
        <?php
    }
}