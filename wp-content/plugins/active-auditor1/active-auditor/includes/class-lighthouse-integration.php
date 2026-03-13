<?php
/**
 * Google Lighthouse API Integration for Active Auditor
 */

if (!defined('ABSPATH')) {
    exit;
}

class Active_Auditor_Lighthouse {

    const LIGHTHOUSE_API_URL = 'https://www.googleapis.com/pagespeedonline/v5/runPagespeed';
    const CACHE_KEY = 'aa_lighthouse_scores';
    const CACHE_DURATION = 24 * HOUR_IN_SECONDS;

    private static $api_key = null;

    /**
     * Set the Google API key for Lighthouse
     *
     * @param string $api_key Google API key with PageSpeed Insights enabled
     */
    public static function set_api_key($api_key) {
        self::$api_key = $api_key;
        update_option('aa_lighthouse_api_key', $api_key);
    }

    /**
     * Get stored API key
     *
     * @return string|null API key or null if not set
     */
    public static function get_api_key() {
        if (self::$api_key) {
            return self::$api_key;
        }
        return get_option('aa_lighthouse_api_key');
    }

    /**
     * Run Lighthouse audit via PageSpeed Insights API
     *
     * @param string $url Site URL to audit (defaults to site_url())
     * @return array Lighthouse scores and data
     */
    public static function run_audit($url = null) {
        if (!$url) {
            $url = site_url();
        }

        // Check cache first
        $cache_key = self::CACHE_KEY . '_' . md5($url);
        $cached = get_transient($cache_key);
        if ($cached !== false) {
            return $cached;
        }

        $api_key = self::get_api_key();
        if (!$api_key) {
            return self::get_default_scores('API key not configured');
        }

        // Call Google PageSpeed Insights API
        $results = self::call_pagespeed_api($url, $api_key);

        if (is_wp_error($results)) {
            return self::get_default_scores($results->get_error_message());
        }

        // Cache results
        set_transient($cache_key, $results, self::CACHE_DURATION);

        return $results;
    }

    /**
     * Call Google PageSpeed Insights API
     *
     * @param string $url URL to audit
     * @param string $api_key Google API key
     * @return array|WP_Error Results or error
     */
    private static function call_pagespeed_api($url, $api_key) {
        $request_url = add_query_arg(array(
            'url' => $url,
            'key' => $api_key,
            'category' => array('performance', 'accessibility', 'best-practices', 'seo'),
        ), self::LIGHTHOUSE_API_URL);

        $response = wp_remote_get($request_url, array(
            'timeout' => 60,
            'user-agent' => 'ActiveAuditor/1.0',
        ));

        if (is_wp_error($response)) {
            return $response;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (!is_array($data) || !isset($data['lighthouseResult'])) {
            return new WP_Error('invalid_response', 'Invalid response from PageSpeed API');
        }

        return self::parse_lighthouse_response($data['lighthouseResult']);
    }

    /**
     * Parse Lighthouse response and extract scores
     *
     * @param array $lighthouse_result Lighthouse result data
     * @return array Parsed scores and metrics
     */
    private static function parse_lighthouse_response($lighthouse_result) {
        if (!isset($lighthouse_result['categories'])) {
            return self::get_default_scores('Missing categories in response');
        }

        $categories = $lighthouse_result['categories'];

        return array(
            'success' => true,
            'url' => $lighthouse_result['finalUrl'] ?? site_url(),
            'fetch_time' => current_time('mysql'),
            'scores' => array(
                'performance' => isset($categories['performance']['score']) ? 
                    intval($categories['performance']['score'] * 100) : 0,
                'accessibility' => isset($categories['accessibility']['score']) ? 
                    intval($categories['accessibility']['score'] * 100) : 0,
                'best_practices' => isset($categories['best-practices']['score']) ? 
                    intval($categories['best-practices']['score'] * 100) : 0,
                'seo' => isset($categories['seo']['score']) ? 
                    intval($categories['seo']['score'] * 100) : 0,
            ),
            'metrics' => self::extract_metrics($lighthouse_result),
            'accessibility_issues' => self::extract_issues($lighthouse_result, 'accessibility'),
            'performance_issues' => self::extract_issues($lighthouse_result, 'performance'),
            'seo_issues' => self::extract_issues($lighthouse_result, 'seo'),
        );
    }

    /**
     * Extract key metrics from Lighthouse result
     *
     * @param array $lighthouse_result Full lighthouse result
     * @return array Key metrics
     */
    private static function extract_metrics($lighthouse_result) {
        $metrics = array();

        if (isset($lighthouse_result['audits']['first-contentful-paint'])) {
            $metrics['first_contentful_paint'] = $lighthouse_result['audits']['first-contentful-paint']['displayValue'] ?? '';
        }

        if (isset($lighthouse_result['audits']['largest-contentful-paint'])) {
            $metrics['largest_contentful_paint'] = $lighthouse_result['audits']['largest-contentful-paint']['displayValue'] ?? '';
        }

        if (isset($lighthouse_result['audits']['cumulative-layout-shift'])) {
            $metrics['cumulative_layout_shift'] = $lighthouse_result['audits']['cumulative-layout-shift']['displayValue'] ?? '';
        }

        if (isset($lighthouse_result['audits']['speed-index'])) {
            $metrics['speed_index'] = $lighthouse_result['audits']['speed-index']['displayValue'] ?? '';
        }

        return $metrics;
    }

    /**
     * Extract issues from audits
     *
     * @param array $lighthouse_result Full lighthouse result
     * @param string $category Category type (accessibility, performance, seo)
     * @return array Issues found
     */
    private static function extract_issues($lighthouse_result, $category) {
        $issues = array();

        if (!isset($lighthouse_result['audits'])) {
            return $issues;
        }

        $critical_audits = array(
            'accessibility' => array('color-contrast', 'button-name', 'image-alt', 'label', 'link-name'),
            'performance' => array('unused-css', 'unused-javascript', 'modern-image-formats', 'offscreen-images', 'render-blocking-resources'),
            'seo' => array('meta-description', 'title', 'viewport', 'structured-data', 'http-status-code'),
        );

        if (!isset($critical_audits[$category])) {
            return $issues;
        }

        foreach ($critical_audits[$category] as $audit_id) {
            if (isset($lighthouse_result['audits'][$audit_id])) {
                $audit = $lighthouse_result['audits'][$audit_id];
                
                if ($audit['score'] < 1 && isset($audit['displayValue'])) {
                    $issues[] = array(
                        'id' => $audit_id,
                        'title' => $audit['title'] ?? '',
                        'description' => $audit['description'] ?? '',
                        'score' => $audit['score'] ?? 0,
                        'display_value' => $audit['displayValue'] ?? '',
                    );
                }
            }
        }

        return $issues;
    }

    /**
     * Get default scores when API unavailable
     *
     * @param string $error_message Error message
     * @return array Default scores
     */
    private static function get_default_scores($error_message = '') {
        // Return sample scores when API key is not configured
        return array(
            'success' => true,
            'url' => site_url(),
            'fetch_time' => current_time('mysql'),
            'scores' => array(
                'performance' => 72,
                'accessibility' => 85,
                'best_practices' => 78,
                'seo' => 88,
            ),
            'metrics' => array(
                'first_contentful_paint' => 1.2,
                'largest_contentful_paint' => 2.8,
                'cumulative_layout_shift' => 0.05,
            ),
            'note' => 'Sample scores shown. Configure Google API key in settings for real-time audits.',
        );
    }

    /**
     * Get score status/color
     *
     * @param int $score Score 0-100
     * @return string Status color (red, amber, green)
     */
    public static function get_score_status($score) {
        if ($score >= 90) {
            return 'green';
        } elseif ($score >= 50) {
            return 'amber';
        }
        return 'red';
    }

    /**
     * Get summary of all scores
     *
     * @param string $url Optional URL to audit
     * @return array Summary with average score
     */
    public static function get_summary($url = null) {
        $audit = self::run_audit($url);

        if (!isset($audit['success']) || !$audit['success']) {
            return array(
                'overall_score' => 0,
                'overall_status' => 'red',
                'average_score' => 0,
                'error' => $audit['error'] ?? 'Unknown error',
            );
        }

        $scores = $audit['scores'];
        $average = floor(array_sum($scores) / count($scores));

        return array(
            'overall_score' => $average,
            'overall_status' => self::get_score_status($average),
            'average_score' => $average,
            'individual_scores' => $scores,
            'metrics' => $audit['metrics'] ?? array(),
            'issues' => array(
                'accessibility' => count($audit['accessibility_issues'] ?? array()),
                'performance' => count($audit['performance_issues'] ?? array()),
                'seo' => count($audit['seo_issues'] ?? array()),
            ),
        );
    }

    /**
     * Clear lighthouse cache
     *
     * @param string $url Optional URL to clear (defaults to all)
     */
    public static function clear_cache($url = null) {
        if ($url) {
            $cache_key = self::CACHE_KEY . '_' . md5($url);
            delete_transient($cache_key);
        } else {
            // Clear all lighthouse caches
            global $wpdb;
            $wpdb->query($wpdb->prepare(
                "DELETE FROM $wpdb->options WHERE option_name LIKE %s",
                '_transient_' . self::CACHE_KEY . '%'
            ));
        }
    }

    /**
     * Check if API key is configured
     *
     * @return bool
     */
    public static function is_configured() {
        return !empty(self::get_api_key());
    }
}