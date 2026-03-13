<?php
/**
 * Health Data Engine for Active Auditor
 */

if (!defined('ABSPATH')) {
    exit;
}

class Active_Auditor_Health_Data {

    /**
     * Get comprehensive health data
     *
     * @return array Health data
     */
    public static function get_health_data() {
        $health_data = array(
            'timestamp' => current_time('mysql'),
            'domain' => parse_url(get_site_url(), PHP_URL_HOST),
            'connected' => true,
            'php' => self::get_php_health(),
            'wordpress' => self::get_wordpress_health(),
            'database' => self::get_database_health(),
            'performance' => self::get_performance_metrics(),
            'security' => self::get_security_info(),
        );
        
        return apply_filters('aa_health_data', $health_data);
    }

    /**
     * Get PHP health information
     *
     * @return array
     */
    private static function get_php_health() {
        $min_php = '7.4.0';
        $current_php = PHP_VERSION;
        $php_ok = version_compare($current_php, $min_php, '>=');
        
        return array(
            'version' => $current_php,
            'minimum_required' => $min_php,
            'status' => aa_get_status($php_ok),
            'memory_usage' => array(
                'current' => round(aa_get_memory_usage(), 2),
                'limit' => round(aa_get_memory_limit(), 2),
                'unit' => 'MB',
                'status' => aa_get_status(aa_get_memory_usage() < (aa_get_memory_limit() * 0.8)),
            ),
            'extensions' => array(
                'curl' => extension_loaded('curl') ? 'yes' : 'no',
                'json' => extension_loaded('json') ? 'yes' : 'no',
                'xml' => extension_loaded('xml') ? 'yes' : 'no',
                'mbstring' => extension_loaded('mbstring') ? 'yes' : 'no',
            ),
        );
    }

    /**
     * Get WordPress health information
     *
     * @return array
     */
    private static function get_wordpress_health() {
        global $wp_version;
        
        $wp_version_info = aa_get_wp_version_info();
        $plugin_updates = self::get_plugin_update_count();
        $theme_updates = aa_has_theme_updates();
        
        return array(
            'version' => array(
                'current' => $wp_version,
                'latest' => $wp_version_info['latest'],
                'status' => aa_get_status($wp_version_info['is_latest']),
            ),
            'updates' => array(
                'plugins' => array(
                    'count' => $plugin_updates,
                    'status' => aa_get_status($plugin_updates === 0, $plugin_updates > 0 && $plugin_updates <= 2),
                ),
                'themes' => array(
                    'available' => $theme_updates ? 'yes' : 'no',
                    'status' => aa_get_status(!$theme_updates),
                ),
            ),
            'multisite' => is_multisite() ? 'yes' : 'no',
            'debug_mode' => (defined('WP_DEBUG') && WP_DEBUG) ? 'enabled' : 'disabled',
        );
    }

    /**
     * Get database health information
     *
     * @return array
     */
    private static function get_database_health() {
        global $wpdb;
        
        // Get database info
        $db_version = $wpdb->db_version();
        $char_set = $wpdb->charset;
        
        return array(
            'version' => $db_version,
            'charset' => $char_set,
            'prefix' => $wpdb->prefix,
            'tables' => array(
                'total' => $wpdb->query("SHOW TABLES"),
                'status' => 'ok',
            ),
            'status' => 'connected',
        );
    }

    /**
     * Get performance metrics
     *
     * @return array
     */
    private static function get_performance_metrics() {
        // Get total posts, pages, etc.
        $posts_count = wp_count_posts();
        $users_count = count_users();
        $comments_count = wp_count_comments();
        
        return array(
            'content' => array(
                'posts' => $posts_count->publish ?? 0,
                'pages' => 0, // Get from posts count
                'custom_types' => count(get_post_types(array('_builtin' => false))),
            ),
            'users' => $users_count['total_users'] ?? 0,
            'comments' => array(
                'total' => $comments_count->total_comments ?? 0,
                'approved' => $comments_count->approved ?? 0,
            ),
            'plugins' => array(
                'active' => count(get_option('active_plugins', array())),
                'total' => count(get_plugins()),
            ),
        );
    }

    /**
     * Get security information
     *
     * @return array
     */
    private static function get_security_info() {
        $ssl_verify = is_ssl() ? 'yes' : 'no';
        $rest_api_enabled = defined('REST_API_ENABLED') && REST_API_ENABLED !== false;
        
        return array(
            'ssl' => array(
                'enabled' => $ssl_verify,
                'status' => aa_get_status(is_ssl()),
            ),
            'rest_api' => $rest_api_enabled ? 'yes' : 'no',
            'rest_api_status' => aa_get_status($rest_api_enabled, !$rest_api_enabled),
        );
    }

    /**
     * Get plugin update count
     *
     * @return int
     */
    public static function get_plugin_update_count() {
        if (!function_exists('get_plugin_updates')) {
            require_once ABSPATH . 'wp-admin/includes/update.php';
        }
        
        $updates = get_plugin_updates();
        return count($updates);
    }

    /**
     * Maybe cache health data
     */
    public static function maybe_cache_health_data() {
        if (get_option('aa_enable_health_caching')) {
            $health_data = self::get_health_data();
            set_transient('aa_health_data_cache', $health_data, get_option('aa_cache_duration'));
        }
    }

    /**
     * Get health data with caching
     *
     * @return array
     */
    public static function get_health_data_cached() {
        if (get_option('aa_enable_health_caching')) {
            $cached = aa_get_cached_health();
            if ($cached !== false) {
                return $cached;
            }
        }
        
        return self::get_health_data();
    }
}