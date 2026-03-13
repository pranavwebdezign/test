<?php
/**
 * Utility functions for Active Auditor plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get a plugin option with prefix
 *
 * @param string $option Option name
 * @param mixed $default Default value
 * @return mixed
 */
function aa_get_option($option, $default = false) {
    return get_option('aa_' . $option, $default);
}

/**
 * Set a plugin option with prefix
 *
 * @param string $option Option name
 * @param mixed $value Option value
 * @return bool
 */
function aa_set_option($option, $value) {
    return update_option('aa_' . $option, $value);
}

/**
 * Get cached health data
 *
 * @return array|false
 */
function aa_get_cached_health() {
    return get_transient('aa_health_data_cache');
}

/**
 * Determine status color
 *
 * @param bool $is_ok Whether the check passed
 * @return string 'green', 'amber', or 'red'
 */
function aa_get_status($is_ok, $is_warning = false) {
    if (!$is_ok) {
        return 'red';
    }
    return $is_warning ? 'amber' : 'green';
}

/**
 * Get WordPress memory usage
 *
 * @return float Memory used in MB
 */
function aa_get_memory_usage() {
    return memory_get_usage(true) / 1024 / 1024;
}

/**
 * Get memory limit
 *
 * @return float Memory limit in MB
 */
function aa_get_memory_limit() {
    $limit = wp_convert_hr_to_bytes(WP_MEMORY_LIMIT);
    return $limit / 1024 / 1024;
}

/**
 * Check if a theme has updates
 *
 * @return bool
 */
function aa_has_theme_updates() {
    if (!function_exists('wp_get_themes')) {
        require_once ABSPATH . 'wp-admin/includes/theme.php';
    }
    
    $update_themes = get_site_transient('update_themes');
    
    if (isset($update_themes->response)) {
        return count($update_themes->response) > 0;
    }
    
    return false;
}

/**
 * Get core WordPress version info
 *
 * @return array
 */
function aa_get_wp_version_info() {
    global $wp_version;
    
    // In a production environment, you'd check against the latest version
    $latest_version = get_site_transient('update_core')->updates[0]->version ?? $wp_version;
    $is_latest = $wp_version === $latest_version;
    
    return array(
        'current' => $wp_version,
        'latest' => $latest_version,
        'is_latest' => $is_latest,
    );
}

/**
 * Sanitize API token
 *
 * @param string $token Token to sanitize
 * @return string
 */
function aa_sanitize_token($token) {
    return sanitize_text_field(trim($token));
}

/**
 * Escape JSON data for safe output
 *
 * @param array $data Data to escape
 * @return string
 */
function aa_escape_json($data) {
    return wp_json_encode($data);
}

/**
 * Log debug information
 *
 * @param string $message Message to log
 * @param array $context Additional context
 */
function aa_debug_log($message, $context = array()) {
    if (defined('WP_DEBUG') && WP_DEBUG === true) {
        error_log('Active Auditor: ' . $message . ' ' . wp_json_encode($context));
    }
}