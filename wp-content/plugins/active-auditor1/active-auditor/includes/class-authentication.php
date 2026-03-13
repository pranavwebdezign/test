<?php
/**
 * Authentication handler for Active Auditor
 */

if (!defined('ABSPATH')) {
    exit;
}

class Active_Auditor_Authentication {

    /**
     * Generate a secure API token
     *
     * @return string 32-character random token
     */
    public static function generate_token() {
        return wp_generate_password(32, false);
    }

    /**
     * Validate API token
     *
     * @param string $token Token to validate
     * @return bool True if valid
     */
    public static function validate_token($token) {
        $stored_token = get_option('aa_api_token');
        
        if (empty($stored_token) || empty($token)) {
            return false;
        }
        
        return hash_equals($stored_token, $token);
    }

    /**
     * Get current API token
     *
     * @return string|false
     */
    public static function get_token() {
        return get_option('aa_api_token');
    }

    /**
     * Regenerate API token (for security)
     *
     * @return string New token
     */
    public static function regenerate_token() {
        $new_token = self::generate_token();
        update_option('aa_api_token', $new_token);
        
        // Log token regeneration
        aa_debug_log('API token regenerated', array(
            'timestamp' => current_time('mysql'),
            'user_id' => get_current_user_id(),
        ));
        
        return $new_token;
    }

    /**
     * Check if extension is authenticated for this site
     *
     * @param array $request_data Request data from extension
     * @return array Status and data
     */
    public static function authenticate_extension($request_data) {
        $token = isset($request_data['token']) ? sanitize_text_field($request_data['token']) : '';
        $domain = isset($request_data['domain']) ? sanitize_text_field($request_data['domain']) : '';
        
        if (empty($token)) {
            return array(
                'authenticated' => false,
                'message' => 'Missing API token',
            );
        }
        
        if (!self::validate_token($token)) {
            // Log failed authentication attempt
            aa_debug_log('Authentication failed', array(
                'provided_token' => substr($token, 0, 4) . '***', // Log partial token
                'timestamp' => current_time('mysql'),
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            ));
            
            return array(
                'authenticated' => false,
                'message' => 'Invalid API token',
            );
        }
        
        return array(
            'authenticated' => true,
            'domain' => parse_url(get_site_url(), PHP_URL_HOST),
        );
    }

    /**
     * Create an authenticated session
     *
     * @param string $token API token
     * @return array Session data
     */
    public static function create_session($token) {
        if (!self::validate_token($token)) {
            return array(
                'success' => false,
                'error' => 'Invalid token',
            );
        }
        
        return array(
            'success' => true,
            'session' => array(
                'created' => current_time('mysql'),
                'domain' => parse_url(get_site_url(), PHP_URL_HOST),
                'expires' => wp_date('c', strtotime('+24 hours')),
            ),
        );
    }

    /**
     * Verify nonce for secure requests
     *
     * @param string $nonce Nonce to verify
     * @param string $action Nonce action
     * @return bool
     */
    public static function verify_nonce($nonce, $action = 'aa_api') {
        return wp_verify_nonce($nonce, $action) !== false;
    }

    /**
     * Log authentication event
     *
     * @param string $event Event type
     * @param array $details Event details
     */
    public static function log_event($event, $details = array()) {
        $log_entry = array(
            'timestamp' => current_time('mysql'),
            'event' => $event,
            'details' => $details,
        );
        
        aa_debug_log($event, $log_entry);
    }
}