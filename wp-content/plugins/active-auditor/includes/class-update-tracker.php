<?php
/**
 * Update Tracker for Active Auditor
 */

if (!defined('ABSPATH')) {
    exit;
}

class Active_Auditor_Update_Tracker {

    /**
     * Get all updates information
     *
     * @return array Update tracking data
     */
    public static function get_updates_tracking() {
        $tracking = array(
            'timestamp' => current_time('mysql'),
            'wordpress' => self::track_wordpress_updates(),
            'plugins' => self::track_plugins_updates(),
            'themes' => self::track_themes_updates(),
        );
        
        return apply_filters('aa_update_tracking', $tracking);
    }

    /**
     * Track WordPress core updates
     *
     * @return array WordPress update info
     */
    private static function track_wordpress_updates() {
        global $wp_version;
        
        $wp_version_info = aa_get_wp_version_info();
        
        return array(
            'current' => $wp_version,
            'latest' => $wp_version_info['latest'],
            'needs_update' => !$wp_version_info['is_latest'],
            'check_date' => wp_date('c', strtotime('-24 hours')),
            'manual_update_required' => !$wp_version_info['is_latest'],
        );
    }

    /**
     * Track plugin updates
     *
     * @return array Plugin update info
     */
    private static function track_plugins_updates() {
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        
        if (!function_exists('get_plugin_updates')) {
            require_once ABSPATH . 'wp-admin/includes/update.php';
        }
        
        $all_plugins = get_plugins();
        $updates = get_plugin_updates();
        
        $total = count($all_plugins);
        $available_updates = count($updates);
        
        $plugin_list = array();
        
        foreach ($all_plugins as $plugin_file => $plugin_data) {
            $has_update = isset($updates[$plugin_file]);
            
            $plugin_list[] = array(
                'name'             => $plugin_data['Name'],
                'slug'             => dirname($plugin_file), // folder name e.g. 'woocommerce'
                'path'             => $plugin_file,          // full file path e.g. 'woocommerce/woocommerce.php'
                'version'          => $plugin_data['Version'],
                'active'           => is_plugin_active($plugin_file),
                'update_available' => $has_update,
                'new_version'      => $has_update ? $updates[$plugin_file]->update->new_version : null,
                'last_checked'     => wp_date('c'),
            );
        }
        
        return array(
            'total' => $total,
            'updates_available' => $available_updates,
            'auto_update_enabled' => defined('AUTOMATIC_UPDATER_DISABLED') ? !AUTOMATIC_UPDATER_DISABLED : true,
            'plugins' => $plugin_list,
        );
    }

    /**
     * Track theme updates
     *
     * @return array Theme update info
     */
    private static function track_themes_updates() {
        if (!function_exists('wp_get_themes')) {
            require_once ABSPATH . 'wp-admin/includes/theme.php';
        }
        
        $themes = wp_get_themes();
        $current_theme = wp_get_theme();
        $update_themes = get_site_transient('update_themes');
        
        $theme_list = array();
        $available_updates = 0;
        
        foreach ($themes as $theme) {
            // Use get_stylesheet() (the theme's own folder name) — NOT get('Template') which
            // is the parent theme folder and causes child themes to inherit the parent's update flag.
            $stylesheet = $theme->get_stylesheet();
            $has_update = isset($update_themes->response[$stylesheet]);
            $new_version = $has_update
                ? ($update_themes->response[$stylesheet]['new_version'] ?? null)
                : null;
            if ($has_update) {
                $available_updates++;
            }

            $theme_list[] = array(
                'name'             => $theme->get('Name'),
                'slug'             => $stylesheet,               // unique folder name (used for update-theme call)
                'template'         => $theme->get('Template'),   // parent template (kept for reference)
                'version'          => $theme->get('Version'),
                'new_version'      => $new_version,              // null when up to date
                'active'           => $stylesheet === $current_theme->get_stylesheet(),
                'update_available' => $has_update,
                'author'           => $theme->get('Author'),
            );
        }

        return array(
            'total'             => count($themes),
            'updates_available' => $available_updates,
            'current_theme'     => $current_theme->get('Name'),
            'themes'            => $theme_list,
        );

    }

    /**
     * Get comparison with latest versions
     *
     * @return array Comparison data
     */
    public static function get_version_comparison() {
        $comparison = array(
            'wordpress' => array(),
            'php' => array(),
        );
        
        global $wp_version;
        $wp_version_info = aa_get_wp_version_info();
        
        $comparison['wordpress'] = array(
            'installed' => $wp_version,
            'latest' => $wp_version_info['latest'],
            'is_latest' => $wp_version_info['is_latest'],
            'days_behind' => $wp_version_info['is_latest'] ? 0 : 'unknown',
        );
        
        $php_version = phpversion();
        $comparison['php'] = array(
            'installed' => $php_version,
            'minimum_required' => '7.4.0',
            'is_supported' => version_compare($php_version, '7.4.0', '>='),
        );
        
        return $comparison;
    }

    /**
     * Get detailed changelog for updates
     *
     * @param string $type Type of update (plugin, theme, wordpress)
     * @param string $slug Slug of the item
     * @return array Changelog
     */
    public static function get_changelog($type, $slug) {
        $changelog = array(
            'type' => $type,
            'slug' => $slug,
            'timestamp' => current_time('mysql'),
        );
        
        // This would integrate with WordPress.org API in production
        // For now, return empty
        return $changelog;
    }

    /**
     * Schedule automatic update checks
     */
    public static function schedule_checks() {
        if (!wp_next_scheduled('aa_check_updates')) {
            wp_schedule_event(time(), 'twicedaily', 'aa_check_updates');
        }
    }

    /**
     * Perform update check
     */
    public static function perform_check() {
        // Trigger WordPress transient refresh
        wp_update_plugins();
        wp_update_themes();
        wp_version_check();
        
        // Store tracking data
        $tracking = self::get_updates_tracking();
        set_transient('aa_update_tracking', $tracking, 12 * HOUR_IN_SECONDS);
    }
}