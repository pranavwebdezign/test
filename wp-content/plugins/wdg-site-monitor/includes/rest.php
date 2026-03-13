<?php
// REST API logic for WDG Site Monitor
if (!defined('ABSPATH')) { exit; }

class WDG_SM_REST {
    // Register all REST API endpoints
    public static function register() {
        register_rest_route('wdg-site-monitor/v1', '/status', array(
            'methods' => 'GET',
            'callback' => array('WDG_SM_REST', 'rest_status'),
            'permission_callback' => array('WDG_SM_REST', 'rest_permission'),
        ));
        register_rest_route('wdg-site-monitor/v1', '/finding-status', array(
            'methods' => 'POST',
            'callback' => array('WDG_SM_REST', 'rest_set_finding_status'),
            'permission_callback' => array('WDG_SM_REST', 'rest_permission'),
            'args' => array(
                'key' => array('required' => true),
                'status' => array('required' => true),
            ),
        ));
    }

    public static function rest_permission() {
        $opts = WDG_SM::get_options();
        $token = isset($opts['token']) ? $opts['token'] : '';
        if (!$token) return false;
        $headers = function_exists('getallheaders') ? getallheaders() : array();
        $auth = '';
        if (isset($headers['Authorization'])) {
            $auth = $headers['Authorization'];
        } elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $auth = $_SERVER['HTTP_AUTHORIZATION'];
        }
        if (stripos($auth, 'Bearer ') === 0) {
            $provided = trim(substr($auth, 7));
            return hash_equals($token, $provided);
        }
        return false;
    }

    public static function rest_status($request) {
        $payload = WDG_SM::build_heartbeat_payload();
        $payload['site_id'] = isset(WDG_SM::get_options()['site_id']) ? WDG_SM::get_options()['site_id'] : '';
        $payload['admin_email'] = get_bloginfo('admin_email');
        $payload['site_name'] = get_bloginfo('name');
        $payload['last_checked'] = time();
        $statuses = get_option('wdg_sm_finding_status', array());
        $payload['finding_status'] = $statuses;
        return rest_ensure_response($payload);
    }

    public static function rest_set_finding_status($request) {
        $key = sanitize_text_field($request['key']);
        $status = sanitize_text_field($request['status']);
        $allowed = array('open','acknowledged','in_progress','done','not_applicable');
        if (!in_array($status, $allowed, true)) {
            return new WP_Error('invalid_status', 'Invalid status', array('status'=>400));
        }
        WDG_SM::set_finding_status($key, $status);
        return rest_ensure_response(array('key'=>$key, 'status'=>$status));
    }
}
