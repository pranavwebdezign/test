<?php
/**
 * Security Audit Engine for Active Auditor
 */

if (!defined('ABSPATH')) {
    exit;
}

class Active_Auditor_Security_Audit {

    /**
     * Get security audit results
     *
     * @return array Audit results
     */
    public static function get_security_audit() {
        $audit = array(
            'timestamp' => current_time('mysql'),
            'vulnerabilities' => self::check_vulnerabilities(),
            'hardening' => self::check_hardening(),
            'users_security' => self::check_users_security(),
            'file_permissions' => self::check_file_permissions(),
        );
        
        return apply_filters('aa_security_audit', $audit);
    }

    /**
     * Check for vulnerabilities in plugins and themes
     *
     * @return array Vulnerabilities
     */
    private static function check_vulnerabilities() {
        if (!get_option('aa_enable_security_audit')) {
            return array(
                array(
                    'label' => 'Security audit disabled',
                    'status' => 'amber',
                    'severity' => 'low',
                ),
            );
        }
        
        $vulnerabilities = array();
        
        // Check for admin user with default password
        if (self::has_insecure_admin()) {
            $vulnerabilities[] = array(
                'label' => 'Default WordPress admin user detected',
                'status' => 'amber',
                'severity' => 'medium',
            );
        }
        
        // Check for exposed wp-admin
        if (!self::is_admin_protected()) {
            $vulnerabilities[] = array(
                'label' => 'wp-admin not password protected',
                'status' => 'amber',
                'severity' => 'high',
            );
        }
        
        // Check WordPress version
        $wp_version_info = aa_get_wp_version_info();
        if (!$wp_version_info['is_latest']) {
            $vulnerabilities[] = array(
                'label' => 'WordPress is outdated',
                'status' => 'red',
                'severity' => 'critical',
            );
        }
        
        // Check for outdated plugins
        $outdated_plugins = self::get_outdated_plugins();
        if (!empty($outdated_plugins)) {
            $vulnerabilities[] = array(
                'label' => count($outdated_plugins) . ' plugins need updates',
                'status' => 'amber',
                'severity' => 'high',
                'details' => $outdated_plugins,
            );
        }
        
        if (empty($vulnerabilities)) {
            $vulnerabilities[] = array(
                'label' => 'No vulnerabilities detected',
                'status' => 'green',
                'severity' => 'none',
            );
        }
        
        return $vulnerabilities;
    }

    /**
     * Check security hardening measures
     *
     * @return array Hardening checks
     */
    private static function check_hardening() {
        $checks = array();
        
        // Check SSL
        $checks['ssl'] = array(
            'label' => 'SSL/HTTPS',
            'value' => is_ssl() ? 'Enabled' : 'Disabled',
            'status' => aa_get_status(is_ssl()),
        );
        
        // Check debug mode
        $debug_enabled = defined('WP_DEBUG') && WP_DEBUG;
        $checks['debug_mode'] = array(
            'label' => 'Debug Mode',
            'value' => $debug_enabled ? 'Enabled (Dev Only)' : 'Disabled',
            'status' => aa_get_status(!$debug_enabled),
        );
        
        // Check database prefix
        global $wpdb;
        $default_prefix = 'wp_';
        $checks['db_prefix'] = array(
            'label' => 'Database Prefix',
            'value' => $wpdb->prefix !== $default_prefix ? 'Custom' : 'Default',
            'status' => aa_get_status($wpdb->prefix !== $default_prefix, true),
        );
        
        // Check file editing
        $file_editing = defined('DISALLOW_FILE_EDIT') && DISALLOW_FILE_EDIT;
        $checks['file_editing'] = array(
            'label' => 'File Editing',
            'value' => $file_editing ? 'Disabled' : 'Enabled',
            'status' => aa_get_status($file_editing),
        );
        
        return $checks;
    }

    /**
     * Check user security
     *
     * @return array User security checks
     */
    private static function check_users_security() {
        $checks = array();
        
        // Get all users
        $users = get_users(array('role' => 'administrator'));
        
        $checks['admin_count'] = array(
            'label' => 'Administrator Accounts',
            'value' => count($users),
            'status' => aa_get_status(count($users) > 0, count($users) === 1),
        );
        
        // Check for inactive users
        $all_users = get_users();
        $checks['total_users'] = array(
            'label' => 'Total Users',
            'value' => count($all_users),
            'status' => 'green',
        );
        
        return $checks;
    }

    /**
     * Check file permissions
     *
     * @return array File permission checks
     */
    private static function check_file_permissions() {
        $checks = array();
        
        // Check wp-config.php permissions
        $wp_config_path = ABSPATH . 'wp-config.php';
        if (file_exists($wp_config_path)) {
            $perms = substr(sprintf('%o', fileperms($wp_config_path)), -4);
            $checks['wp_config'] = array(
                'label' => 'wp-config.php Permissions',
                'value' => $perms,
                'status' => aa_get_status((int)$perms < 644),
            );
        }
        
        // Check if .htaccess exists
        $htaccess_path = ABSPATH . '.htaccess';
        $checks['htaccess'] = array(
            'label' => '.htaccess',
            'value' => file_exists($htaccess_path) ? 'Present' : 'Missing',
            'status' => aa_get_status(file_exists($htaccess_path), true),
        );
        
        return $checks;
    }

    /**
     * Check if admin user is secure
     *
     * @return bool
     */
    private static function has_insecure_admin() {
        $admin = get_user_by('login', 'admin');
        return !empty($admin);
    }

    /**
     * Check if admin is protected
     *
     * @return bool
     */
    private static function is_admin_protected() {
        // Check if .htaccess protection exists
        $htaccess = ABSPATH . '.htaccess';
        if (file_exists($htaccess)) {
            $content = file_get_contents($htaccess);
            return strpos($content, 'wp-admin') !== false;
        }
        return false;
    }

    /**
     * Get outdated plugins
     *
     * @return array
     */
    private static function get_outdated_plugins() {
        if (!function_exists('get_plugin_updates')) {
            require_once ABSPATH . 'wp-admin/includes/update.php';
        }
        
        $updates = get_plugin_updates();
        $outdated = array();
        
        foreach ($updates as $plugin_file => $plugin_data) {
            $outdated[] = array(
                'plugin' => $plugin_data->Name,
                'current' => $plugin_data->Version,
                'available' => $plugin_data->update->new_version,
            );
        }
        
        return $outdated;
    }
}