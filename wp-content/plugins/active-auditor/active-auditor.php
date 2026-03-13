<?php
/**
 * Plugin Name: Active Auditor - Health Monitor
 * Plugin URI: https://example.com/active-auditor
 * Description: Comprehensive WordPress health auditor with Chrome extension integration. Data Engine with Zero Front-End Impact.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: active-auditor
 * Domain Path: /languages
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('ACTIVE_AUDITOR_VERSION', '1.0.0');
define('ACTIVE_AUDITOR_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ACTIVE_AUDITOR_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ACTIVE_AUDITOR_INCLUDES_DIR', ACTIVE_AUDITOR_PLUGIN_DIR . 'includes/');
define('ACTIVE_AUDITOR_LIB_DIR', ACTIVE_AUDITOR_PLUGIN_DIR . 'lib/');

// Load required files
require_once ACTIVE_AUDITOR_LIB_DIR . 'utility-functions.php';
require_once ACTIVE_AUDITOR_INCLUDES_DIR . 'class-authentication.php';
require_once ACTIVE_AUDITOR_INCLUDES_DIR . 'class-health-data.php';
require_once ACTIVE_AUDITOR_INCLUDES_DIR . 'class-security-audit.php';
require_once ACTIVE_AUDITOR_INCLUDES_DIR . 'class-update-tracker.php';
require_once ACTIVE_AUDITOR_INCLUDES_DIR . 'class-wordfence-integration.php';
require_once ACTIVE_AUDITOR_INCLUDES_DIR . 'class-lighthouse-integration.php';
require_once ACTIVE_AUDITOR_INCLUDES_DIR . 'class-seo-scanner.php';
require_once ACTIVE_AUDITOR_INCLUDES_DIR . 'class-google-services.php';
require_once ACTIVE_AUDITOR_INCLUDES_DIR . 'class-rest-endpoints.php';
require_once ACTIVE_AUDITOR_INCLUDES_DIR . 'class-admin-settings.php';

/**
 * Initialize the plugin
 */
function active_auditor_init() {
    // Initialize admin settings - only on admin
    if (is_admin()) {
        Active_Auditor_Admin_Settings::init();
        add_action('wp_dashboard_setup', array('Active_Auditor_Health_Data', 'maybe_cache_health_data'));
        add_action('admin_notices', 'active_auditor_admin_notices');
    }
}

/**
 * Display admin notices for plugin configuration
 */
function active_auditor_admin_notices() {
    if (!current_user_can('manage_options')) {
        return;
    }

    $screen = get_current_screen();
    
    // Only show on Active Auditor pages and dashboard
    if ($screen && strpos($screen->id, 'active-auditor') === false && 'dashboard' !== $screen->id) {
        return;
    }

    // Check if Wordfence API key is configured
    $wordfence_key = get_option('aa_wordfence_api_key');
    if (empty($wordfence_key)) {
        ?>
        <div class="notice notice-warning is-dismissible">
            <p>
                <strong>Active Auditor:</strong> 
                <?php esc_html_e('To enable real-time vulnerability detection with Wordfence Intelligence API, add your API key in ', 'active-auditor'); ?>
                <a href="<?php echo esc_url(admin_url('admin.php?page=active-auditor-settings')); ?>">
                    <?php esc_html_e('Settings', 'active-auditor'); ?>
                </a>.
                <a href="https://www.wordfence.com/intelligence/api/" target="_blank" rel="noopener noreferrer">
                    <?php esc_html_e('Get free API key →', 'active-auditor'); ?>
                </a>
            </p>
        </div>
        <?php
    }

    // Check if Lighthouse API key is configured
    $lighthouse_key = get_option('aa_lighthouse_api_key');
    if (empty($lighthouse_key)) {
        ?>
        <div class="notice notice-warning is-dismissible">
            <p>
                <strong>Active Auditor:</strong> 
                <?php esc_html_e('To enable Lighthouse performance scoring, add your Google PageSpeed Insights API key in ', 'active-auditor'); ?>
                <a href="<?php echo esc_url(admin_url('admin.php?page=active-auditor-settings')); ?>">
                    <?php esc_html_e('Settings', 'active-auditor'); ?>
                </a>.
                <a href="https://console.cloud.google.com/apis/library/pagespeedonline.googleapis.com" target="_blank" rel="noopener noreferrer">
                    <?php esc_html_e('Get API key →', 'active-auditor'); ?>
                </a>
            </p>
        </div>
        <?php
    }
}
add_action('plugins_loaded', 'active_auditor_init');

/**
 * Register REST API routes
 */
function active_auditor_register_rest_routes() {
    Active_Auditor_REST_Endpoints::register_routes();
}
add_action('rest_api_init', 'active_auditor_register_rest_routes');

/**
 * Activation hook
 */
register_activation_hook(__FILE__, 'active_auditor_activate');
function active_auditor_activate() {
    // Generate API token
    if (!get_option('aa_api_token')) {
        add_option('aa_api_token', Active_Auditor_Authentication::generate_token());
    }
    
    // Set default options
    add_option('aa_enable_security_audit', '1');
    add_option('aa_enable_update_tracking', '1');
    add_option('aa_enable_health_caching', '1');
    add_option('aa_cache_duration', 3600); // 1 hour
    
    // Schedule caching
    if (!wp_next_scheduled('active_auditor_cache_health_data')) {
        wp_schedule_event(time(), 'hourly', 'active_auditor_cache_health_data');
    }
}

/**
 * Deactivation hook
 */
register_deactivation_hook(__FILE__, 'active_auditor_deactivate');
function active_auditor_deactivate() {
    // Clear scheduled hooks
    wp_clear_scheduled_hook('active_auditor_cache_health_data');
    
    // Clear transient cache
    delete_transient('aa_health_data_cache');
}

/**
 * Uninstall hook
 */
register_uninstall_hook(__FILE__, 'active_auditor_uninstall');
function active_auditor_uninstall() {
    // Delete all plugin options
    delete_option('aa_api_token');
    delete_option('aa_enable_security_audit');
    delete_option('aa_enable_update_tracking');
    delete_option('aa_enable_health_caching');
    delete_option('aa_cache_duration');
    delete_transient('aa_health_data_cache');
}

/**
 * Load plugin text domain for localization
 */
add_action('init', 'active_auditor_load_textdomain');
function active_auditor_load_textdomain() {
    load_plugin_textdomain(
        'active-auditor',
        false,
        dirname(plugin_basename(__FILE__)) . '/languages'
    );
}

/**
 * Cache health data periodically
 */
add_action('active_auditor_cache_health_data', 'active_auditor_update_cache');
function active_auditor_update_cache() {
    if (get_option('aa_enable_health_caching')) {
        $health_data = Active_Auditor_Health_Data::get_health_data();
        set_transient('aa_health_data_cache', $health_data, get_option('aa_cache_duration'));
    }
}