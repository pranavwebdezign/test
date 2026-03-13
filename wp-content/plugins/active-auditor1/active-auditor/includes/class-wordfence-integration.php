<?php
/**
 * Wordfence Vulnerability Integration for Active Auditor
 * Integrates with Wordfence Intelligence API for real-time vulnerability data
 */

if (!defined('ABSPATH')) {
    exit;
}

class Active_Auditor_Wordfence {

    const WORDFENCE_API_URL = 'https://www.wordfence.com/api/intelligence/v2/plugins';
    const WORDFENCE_THEME_API_URL = 'https://www.wordfence.com/api/intelligence/v2/themes';
    const CACHE_KEY = 'aa_wordfence_vulns';
    const CACHE_DURATION = 24 * HOUR_IN_SECONDS;

    /**
     * Get Wordfence API key
     *
     * @return string|null API key or null if not configured
     */
    public static function get_api_key() {
        return get_option('aa_wordfence_api_key');
    }

    /**
     * Set Wordfence API key
     *
     * @param string $api_key Wordfence API key
     */
    public static function set_api_key($api_key) {
        update_option('aa_wordfence_api_key', $api_key);
    }

    /**
     * Get vulnerabilities from Wordfence Intelligence API or cache
     *
     * @return array Vulnerabilities list
     */
    public static function get_vulnerabilities() {
        // Check cache first
        $cached = get_transient(self::CACHE_KEY);
        if ($cached !== false) {
            return $cached;
        }

        // Get installed plugins and themes
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $vulnerabilities = array();

        // Check WordPress core
        $wp_core_vuln = self::check_wordpress_core();
        if (!empty($wp_core_vuln)) {
            $vulnerabilities[] = $wp_core_vuln;
        }

        // Check installed plugins against Wordfence API
        $plugins = get_plugins();
        foreach ($plugins as $plugin_file => $plugin_data) {
            $plugin_slug = dirname($plugin_file);
            if ($plugin_slug === '.') {
                $plugin_slug = plugin_basename($plugin_file);
                $plugin_slug = str_replace('.php', '', $plugin_slug);
            }

            $vuln = self::check_plugin_vulnerabilities($plugin_slug, $plugin_data);
            if (!empty($vuln)) {
                $vulnerabilities = array_merge($vulnerabilities, $vuln);
            }
        }

        // Check themes
        $current_theme = wp_get_theme();
        $theme_slug = $current_theme->get('Template');
        $theme_vuln = self::check_theme_vulnerabilities($theme_slug, $current_theme);
        if (!empty($theme_vuln)) {
            $vulnerabilities = array_merge($vulnerabilities, $theme_vuln);
        }

        // Cache results
        set_transient(self::CACHE_KEY, $vulnerabilities, self::CACHE_DURATION);

        return $vulnerabilities;
    }

    /**
     * Check a specific plugin for vulnerabilities via Wordfence API
     *
     * @param string $plugin_slug Plugin slug
     * @param array $plugin_data Plugin data
     * @return array Vulnerabilities
     */
    private static function check_plugin_vulnerabilities($plugin_slug, $plugin_data) {
        $vulnerabilities = array();
        $api_key = self::get_api_key();

        // If no API key, try local database
        if (empty($api_key)) {
            return self::check_plugin_vulnerabilities_local($plugin_slug, $plugin_data);
        }

        // Query Wordfence Intelligence API
        $vulnerability_data = self::query_wordfence_api('plugin', $plugin_slug);

        if (is_array($vulnerability_data) && count($vulnerability_data) > 0) {
            foreach ($vulnerability_data as $vuln) {
                // Check if installed version is affected
                if (version_compare($plugin_data['Version'], $vuln['patched_version'], '<')) {
                    $vulnerabilities[] = array(
                        'type' => 'plugin',
                        'name' => $plugin_data['Name'],
                        'slug' => $plugin_slug,
                        'installed_version' => $plugin_data['Version'],
                        'affected_versions' => $vuln['affected_versions'] ?? 'Multiple',
                        'fixed_version' => $vuln['patched_version'],
                        'severity' => $vuln['severity'] ?? 'unknown',
                        'description' => $vuln['description'] ?? 'Security vulnerability found',
                        'status' => self::map_severity_to_status($vuln['severity'] ?? 'unknown'),
                        'cve' => $vuln['cve'] ?? null,
                        'source' => 'wordfence_api',
                    );
                }
            }
        }

        return $vulnerabilities;
    }

    /**
     * Check plugin vulnerabilities via local database (fallback)
     *
     * @param string $plugin_slug Plugin slug
     * @param array $plugin_data Plugin data
     * @return array Vulnerabilities
     */
    private static function check_plugin_vulnerabilities_local($plugin_slug, $plugin_data) {
        $vulnerabilities = array();
        $known_vulnerable = self::get_known_vulnerable_plugins();

        if (in_array($plugin_slug, array_keys($known_vulnerable))) {
            $vuln_data = $known_vulnerable[$plugin_slug];
            
            if (version_compare($plugin_data['Version'], $vuln_data['fixed_version'], '<')) {
                $vulnerabilities[] = array(
                    'type' => 'plugin',
                    'name' => $plugin_data['Name'],
                    'slug' => $plugin_slug,
                    'installed_version' => $plugin_data['Version'],
                    'affected_versions' => $vuln_data['affected_versions'],
                    'fixed_version' => $vuln_data['fixed_version'],
                    'severity' => $vuln_data['severity'],
                    'description' => $vuln_data['description'],
                    'status' => self::map_severity_to_status($vuln_data['severity']),
                    'source' => 'local_database',
                );
            }
        }

        return $vulnerabilities;
    }

    /**
     * Check theme vulnerabilities
     *
     * @param string $theme_slug Theme slug
     * @param WP_Theme $theme Theme object
     * @return array Vulnerabilities
     */
    private static function check_theme_vulnerabilities($theme_slug, $theme) {
        $vulnerabilities = array();
        $api_key = self::get_api_key();

        if (empty($api_key)) {
            return self::check_theme_vulnerabilities_local($theme_slug, $theme);
        }

        // Query Wordfence Intelligence API for theme
        $vulnerability_data = self::query_wordfence_api('theme', $theme_slug);

        if (is_array($vulnerability_data) && count($vulnerability_data) > 0) {
            foreach ($vulnerability_data as $vuln) {
                if (version_compare($theme->get('Version'), $vuln['patched_version'], '<')) {
                    $vulnerabilities[] = array(
                        'type' => 'theme',
                        'name' => $theme->get('Name'),
                        'slug' => $theme_slug,
                        'installed_version' => $theme->get('Version'),
                        'affected_versions' => $vuln['affected_versions'] ?? 'Multiple',
                        'fixed_version' => $vuln['patched_version'],
                        'severity' => $vuln['severity'] ?? 'unknown',
                        'description' => $vuln['description'] ?? 'Security vulnerability found',
                        'status' => self::map_severity_to_status($vuln['severity'] ?? 'unknown'),
                        'source' => 'wordfence_api',
                    );
                }
            }
        }

        return $vulnerabilities;
    }

    /**
     * Check theme vulnerabilities via local database (fallback)
     *
     * @param string $theme_slug Theme slug
     * @param WP_Theme $theme Theme object
     * @return array Vulnerabilities
     */
    private static function check_theme_vulnerabilities_local($theme_slug, $theme) {
        // Local database would go here, for now return empty
        return array();
    }

    /**
     * Query Wordfence Intelligence API
     *
     * @param string $type 'plugin' or 'theme'
     * @param string $slug Plugin/theme slug
     * @return array|WP_Error Vulnerability data or error
     */
    private static function query_wordfence_api($type, $slug) {
        $api_key = self::get_api_key();

        if (empty($api_key)) {
            return array();
        }

        // Construct API URL based on type
        $url = ('theme' === $type) ? self::WORDFENCE_THEME_API_URL : self::WORDFENCE_API_URL;
        $url = add_query_arg(array(
            'slug' => $slug,
            'action' => 'query',
            'format' => 'json',
        ), $url);

        // Add API key to headers
        $args = array(
            'timeout' => 10,
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'User-Agent' => 'ActiveAuditor/1.0',
            ),
            'sslverify' => true,
        );

        $response = wp_remote_get($url, $args);

        if (is_wp_error($response)) {
            // Log error but don't fail
            error_log('Wordfence API Error: ' . $response->get_error_message());
            return array();
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (!is_array($data)) {
            return array();
        }

        // Wordfence returns vulnerabilities in 'vulnerabilities' key
        return $data['vulnerabilities'] ?? array();
    }

    /**
     * Get known vulnerable plugins (free local database)
     *
     * @return array
     */
    private static function get_known_vulnerable_plugins() {
        return array(
            // Example: 'plugin-slug' => array(
            //     'affected_versions' => '< 2.0',
            //     'fixed_version' => '2.0',
            //     'severity' => 'high',
            //     'description' => 'SQL injection vulnerability',
            //     'cve' => 'CVE-2025-xxxx',
            // ),
        );
    }

    /**
     * Check WordPress core for vulnerabilities
     *
     * @return array Vulnerability status
     */
    public static function check_wordpress_core() {
        global $wp_version;
        
        $wp_updates = get_site_transient('update_core');
        
        if (isset($wp_updates->updates[0])) {
            $latest = $wp_updates->updates[0];
            if ($wp_version !== $latest->version) {
                return array(
                    'type' => 'wordpress',
                    'current_version' => $wp_version,
                    'latest_version' => $latest->version,
                    'severity' => 'high',
                    'description' => 'WordPress core is outdated. Update to the latest version to receive security patches.',
                    'status' => 'red',
                    'update_available' => true,
                );
            }
        }

        return array();
    }

    /**
     * Map severity level to status color
     *
     * @param string $severity Severity level
     * @return string Status color
     */
    private static function map_severity_to_status($severity) {
        $severity_lower = strtolower($severity);
        
        if (in_array($severity_lower, array('critical', 'high'))) {
            return 'red';
        } elseif ($severity_lower === 'medium') {
            return 'amber';
        }
        return 'green';
    }

    /**
     * Get security summary
     *
     * @return array Summary with combined severity
     */
    public static function get_security_summary() {
        $vulnerabilities = self::get_vulnerabilities();

        // Determine overall severity
        $critical_count = 0;
        $high_count = 0;
        $medium_count = 0;

        foreach ($vulnerabilities as $vuln) {
            if ($vuln['status'] === 'red') {
                $critical_count++;
            } elseif ($vuln['status'] === 'amber') {
                $medium_count++;
            }
        }

        $overall_status = 'green';
        if ($critical_count > 0 || $high_count > 0) {
            $overall_status = 'red';
        } elseif ($medium_count > 0) {
            $overall_status = 'amber';
        }

        return array(
            'overall_status' => $overall_status,
            'critical_vulnerabilities' => $critical_count,
            'medium_vulnerabilities' => $medium_count,
            'total_vulnerabilities' => count($vulnerabilities),
            'vulnerabilities' => $vulnerabilities,
            'api_configured' => !empty(self::get_api_key()),
        );
    }

    /**
     * Clear vulnerability cache
     */
    public static function clear_cache() {
        delete_transient(self::CACHE_KEY);
    }
}