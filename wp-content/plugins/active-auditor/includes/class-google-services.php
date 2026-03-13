<?php
/**
 * Google Services Detector for Active Auditor
 * Detects GA4, GTM, Google Ads, and other Google services
 */

if (!defined('ABSPATH')) {
    exit;
}

class Active_Auditor_Google_Services {

    const CACHE_KEY = 'aa_google_services';
    const CACHE_DURATION = 24 * HOUR_IN_SECONDS;

    /**
     * Detect all Google services on the site
     *
     * @param string $url URL to scan (defaults to home_url())
     * @return array Detected services
     */
    public static function detect_services($url = null, $bust_cache = false) {
        if (!$url) {
            $url = home_url();
        }

        // Check cache (skip if bust_cache requested)
        $cache_key = self::CACHE_KEY . '_' . md5($url);
        if ($bust_cache) {
            delete_transient($cache_key);
        }
        $cached = get_transient($cache_key);
        if ($cached !== false) {
            return $cached;
        }

        // Fetch page content
        $response = wp_remote_get($url, array(
            'timeout' => 30,
            'user-agent' => 'ActiveAuditor/1.0',
        ));

        if (is_wp_error($response)) {
            return self::get_error_result($response->get_error_message());
        }

        $html = wp_remote_retrieve_body($response);

        $results = array(
            'url' => $url,
            'scan_time' => current_time('mysql'),
            'services' => array(
                'ga4' => self::detect_ga4($html),
                'gtm' => self::detect_gtm($html),
                'google_ads' => self::detect_google_ads($html),
                'google_analytics_4_api' => self::detect_ga4_api(),
                'google_search_console' => self::detect_gsc(),
                'google_site_verification' => self::detect_site_verification($html),
                'recaptcha' => self::detect_recaptcha($html),
            ),
        );

        // Calculate summary
        $results['detected_services'] = array_filter($results['services'], fn($s) => $s['detected']);
        $results['total_services'] = count($results['detected_services']);

        // Cache results
        set_transient($cache_key, $results, self::CACHE_DURATION);

        return $results;
    }

    /**
     * Detect Google Analytics 4 (GA4)
     *
     * @param string $html HTML content
     * @return array GA4 detection result
     */
    private static function detect_ga4($html) {
        $ga4_ids = array();

        // GA4 gtag.js
        if (preg_match('/gtag\s*\(\s*[\'"]config[\'"]\s*,\s*[\'"]G-([A-Z0-9]+)[\'"]/', $html, $matches)) {
            $ga4_ids[] = 'G-' . $matches[1];
        }

        // GA4 from script tag
        if (preg_match('/https:\/\/www\.googletagmanager\.com\/gtag\/js\?id=(G-[A-Z0-9]+)/', $html, $matches)) {
            $ga4_ids[] = $matches[1];
        }

        $ga4_ids = array_unique($ga4_ids);

        return array(
            'detected' => count($ga4_ids) > 0,
            'ids' => $ga4_ids,
            'status' => count($ga4_ids) > 0 ? 'green' : 'not_detected',
            'description' => count($ga4_ids) > 0 ? 
                'Google Analytics 4 is installed' : 
                'Google Analytics 4 not found',
        );
    }

    /**
     * Detect Google Tag Manager (GTM)
     *
     * @param string $html HTML content
     * @return array GTM detection result
     */
    private static function detect_gtm($html) {
        $gtm_ids = array();

        // GTM script tag
        if (preg_match('/https:\/\/www\.googletagmanager\.com\/gtm\.js\?id=(GTM-[A-Z0-9]+)/', $html, $matches)) {
            $gtm_ids[] = $matches[1];
        }

        // GTM noscript tag
        if (preg_match('/<noscript>.*?https:\/\/www\.googletagmanager\.com\/ns\.html\?id=(GTM-[A-Z0-9]+)/', $html, $matches)) {
            if (!in_array($matches[1], $gtm_ids)) {
                $gtm_ids[] = $matches[1];
            }
        }

        // GTM dataLayer
        if (preg_match('/window\.dataLayer\s*=\s*window\.dataLayer\s*\|\|\s*\[\]/', $html)) {
            if (count($gtm_ids) === 0) {
                // dataLayer found but no GTM ID, might be in separate script
                $gtm_ids[] = 'detected_datalayer';
            }
        }

        return array(
            'detected' => count($gtm_ids) > 0,
            'ids' => $gtm_ids,
            'status' => count($gtm_ids) > 0 ? 'green' : 'not_detected',
            'description' => count($gtm_ids) > 0 ? 
                'Google Tag Manager is installed' : 
                'Google Tag Manager not found',
        );
    }

    /**
     * Detect Google Ads (conversion tracking)
     *
     * @param string $html HTML content
     * @return array Google Ads detection result
     */
    private static function detect_google_ads($html) {
        $google_ads_found = false;
        $conversion_labels = array();

        // Google Ads conversion tracking pixel
        if (preg_match('/https:\/\/www\.googleadservices\.com\/pagead\/conversion\/([0-9]+)/', $html, $matches)) {
            $google_ads_found = true;
        }

        // Google Ads gtag conversion
        if (preg_match_all('/gtag\s*\(\s*[\'"]event[\'"]\s*,\s*[\'"]page_view[\'"]\s*,\s*\{\s*[\'"]conversion_id[\'"]\s*:\s*[\'"]([0-9]+)[\'"]/', $html, $matches)) {
            $google_ads_found = true;
        }

        // Google Ads conversion labels
        if (preg_match_all('/conversion_label[\'"]?\s*:\s*[\'"]([A-Za-z0-9_-]+)[\'"]/', $html, $matches)) {
            $conversion_labels = array_unique($matches[1]);
        }

        return array(
            'detected' => $google_ads_found,
            'conversion_labels' => $conversion_labels,
            'status' => $google_ads_found ? 'green' : 'not_detected',
            'description' => $google_ads_found ? 
                'Google Ads conversion tracking is installed' : 
                'Google Ads conversion tracking not found',
        );
    }

    /**
     * Detect Google Analytics API v4 (via WordPress integration)
     *
     * @return array GA4 API detection result
     */
    private static function detect_ga4_api() {
        // Check for MonsterInsights or similar plugins that might connect to GA4 API
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $plugins = get_plugins();
        $ga_plugins = array();

        foreach ($plugins as $plugin_file => $plugin_data) {
            $plugin_name = strtolower($plugin_data['Name']);
            if (strpos($plugin_name, 'analytics') !== false || 
                strpos($plugin_name, 'insights') !== false ||
                strpos($plugin_name, 'ga4') !== false) {
                $ga_plugins[] = $plugin_data['Name'];
            }
        }

        return array(
            'detected' => count($ga_plugins) > 0,
            'plugins' => $ga_plugins,
            'status' => count($ga_plugins) > 0 ? 'green' : 'not_detected',
            'description' => count($ga_plugins) > 0 ? 
                'Google Analytics integration plugin detected' : 
                'Google Analytics integration not found',
        );
    }

    /**
     * Detect Google Search Console connection
     *
     * @return array GSC detection result
     */
    private static function detect_gsc() {
        // Check for Google Site Kit or similar plugins
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $plugins = get_plugins();
        $gsc_plugins = array();

        foreach ($plugins as $plugin_file => $plugin_data) {
            $plugin_name = strtolower($plugin_data['Name']);
            if (strpos($plugin_name, 'site kit') !== false || 
                strpos($plugin_name, 'search console') !== false ||
                strpos($plugin_name, 'google') !== false) {
                $gsc_plugins[] = $plugin_data['Name'];
            }
        }

        return array(
            'detected' => count($gsc_plugins) > 0,
            'plugins' => $gsc_plugins,
            'status' => count($gsc_plugins) > 0 ? 'green' : 'not_detected',
            'description' => count($gsc_plugins) > 0 ? 
                'Google Search Console integration detected' : 
                'Google Search Console integration not found',
        );
    }

    /**
     * Detect Google Site Verification meta tags
     *
     * @param string $html HTML content
     * @return array Site verification detection result
     */
    private static function detect_site_verification($html) {
        $verifications = array();

        // Google verification
        if (preg_match('/<meta\s+name=[\'"]google-site-verification[\'"]\s+content=[\'"]([^\'"]*)/', $html, $matches)) {
            $verifications['google'] = $matches[1];
        }

        // Bing verification
        if (preg_match('/<meta\s+name=[\'"]msvalidate\.01[\'"]\s+content=[\'"]([^\'"]*)/', $html, $matches)) {
            $verifications['bing'] = $matches[1];
        }

        return array(
            'detected' => count($verifications) > 0,
            'verifications' => $verifications,
            'status' => count($verifications) > 0 ? 'green' : 'not_detected',
            'description' => count($verifications) > 0 ? 
                'Site verification tags detected' : 
                'Site verification tags not found',
        );
    }

    /**
     * Detect reCAPTCHA
     *
     * @param string $html HTML content
     * @return array reCAPTCHA detection result
     */
    private static function detect_recaptcha($html) {
        $recaptcha_versions = array();

        // reCAPTCHA v3
        if (preg_match('/https:\/\/www\.google\.com\/recaptcha\/api\.js\?render=([0-9A-Za-z_-]+)/', $html, $matches)) {
            $recaptcha_versions[] = 'v3';
        }

        // reCAPTCHA v2
        if (preg_match('/<div\s+.*?class=[\'"]?g-recaptcha/', $html)) {
            if (!in_array('v2', $recaptcha_versions)) {
                $recaptcha_versions[] = 'v2';
            }
        }

        // reCAPTCHA Enterprise
        if (preg_match('/google\.recaptcha\.enterprise\.render/', $html)) {
            if (!in_array('enterprise', $recaptcha_versions)) {
                $recaptcha_versions[] = 'enterprise';
            }
        }

        return array(
            'detected' => count($recaptcha_versions) > 0,
            'versions' => $recaptcha_versions,
            'status' => count($recaptcha_versions) > 0 ? 'green' : 'not_detected',
            'description' => count($recaptcha_versions) > 0 ? 
                'Google reCAPTCHA is installed (' . implode(', ', $recaptcha_versions) . ')' : 
                'Google reCAPTCHA not found',
        );
    }

    /**
     * Get summary of detected services
     *
     * @param string $url Optional URL to scan
     * @return array Summary of services
     */
    public static function get_summary($url = null) {
        $detection = self::detect_services($url);

        if (isset($detection['error'])) {
            return array(
                'error' => $detection['error'],
                'total' => 0,
            );
        }

        return array(
            'total_detected' => $detection['total_services'],
            'services' => $detection['detected_services'],
            'scan_time' => $detection['scan_time'],
        );
    }

    /**
     * Get detailed service report
     *
     * @param string $url Optional URL to scan
     * @return string HTML formatted report
     */
    public static function get_report($url = null) {
        $detection = self::detect_services($url);

        if (isset($detection['error'])) {
            return '<p>Error: ' . esc_html($detection['error']) . '</p>';
        }

        $html = '<div class="aa-services-report">';
        $html .= '<h3>Google Services Detected</h3>';

        if ($detection['total_services'] === 0) {
            $html .= '<p>No Google services detected on this site.</p>';
        } else {
            $html .= '<ul>';
            foreach ($detection['detected_services'] as $service => $info) {
                $html .= '<li>';
                $html .= '<strong>' . esc_html($info['description']) . '</strong>';
                if (!empty($info['ids'])) {
                    $html .= ' - IDs: ' . esc_html(implode(', ', $info['ids']));
                }
                $html .= '</li>';
            }
            $html .= '</ul>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Get error result
     *
     * @param string $error_message Error message
     * @return array Error result
     */
    private static function get_error_result($error_message) {
        // Return fallback when scan fails
        return array(
            'url' => home_url(),
            'scan_time' => current_time('mysql'),
            'services' => array(
                'ga4' => array(
                    'detected' => false,
                    'ids' => array(),
                    'status' => 'not_detected',
                    'description' => 'Google Analytics 4 not found',
                ),
                'gtm' => array(
                    'detected' => false,
                    'ids' => array(),
                    'status' => 'not_detected',
                    'description' => 'Google Tag Manager not found',
                ),
                'google_ads' => array(
                    'detected' => false,
                    'count' => 0,
                    'status' => 'not_detected',
                    'description' => 'Google Ads not found',
                ),
                'recaptcha' => array(
                    'detected' => false,
                    'version' => null,
                    'status' => 'not_detected',
                    'description' => 'reCAPTCHA not found',
                ),
            ),
            'detected_services' => array(),
            'total_services' => 0,
            'note' => 'Could not scan page - showing fallback data',
        );
    }

    /**
     * Clear detection cache
     *
     * @param string $url Optional URL to clear (defaults to all)
     */
    public static function clear_cache($url = null) {
        if ($url) {
            $cache_key = self::CACHE_KEY . '_' . md5($url);
            delete_transient($cache_key);
        } else {
            // Clear all caches
            global $wpdb;
            $wpdb->query($wpdb->prepare(
                "DELETE FROM $wpdb->options WHERE option_name LIKE %s",
                '_transient_' . self::CACHE_KEY . '%'
            ));
        }
    }
}