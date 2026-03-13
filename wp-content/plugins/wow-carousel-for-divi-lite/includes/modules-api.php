<?php

namespace Divi_Carousel_Free;

defined('ABSPATH') || exit;

class Modules_API
{
    public function __construct()
    {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes()
    {
        register_rest_route('divi-carousel-free/v1', '/modules', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_modules'],
                'permission_callback' => [$this, 'check_permission'],
            ],
            [
                'methods' => 'POST',
                'callback' => [$this, 'update_modules'],
                'permission_callback' => [$this, 'check_permission'],
            ],
        ]);
    }

    public function check_permission()
    {
        return current_user_can('manage_options');
    }

    public function get_modules($request)
    {
        $modules = get_option('dcf_carousel_modules', [
            'carousel_maker' => true,
            'image_carousel' => true,
            'logo_carousel' => true,
        ]);

        // Check if Pro plugin is installed
        $is_pro_installed = defined('DIVI_CAROUSEL_PRO_VERSION');

        return rest_ensure_response([
            'success' => true,
            'data' => $modules,
            'is_pro_installed' => $is_pro_installed,
        ]);
    }

    public function update_modules($request)
    {
        $params = $request->get_json_params();

        if (!isset($params['modules']) || !is_array($params['modules'])) {
            return new \WP_Error(
                'invalid_data',
                __('Invalid module data', 'divi-carousel-free'),
                ['status' => 400]
            );
        }

        $modules = $params['modules'];

        // Sanitize and validate
        $valid_modules = ['carousel_maker', 'image_carousel', 'logo_carousel'];
        $sanitized_modules = [];

        foreach ($valid_modules as $module_id) {
            $sanitized_modules[$module_id] = isset($modules[$module_id]) && $modules[$module_id];
        }

        update_option('dcf_carousel_modules', $sanitized_modules);

        return rest_ensure_response([
            'success' => true,
            'message' => __('Module settings saved successfully', 'divi-carousel-free'),
            'data' => $sanitized_modules,
        ]);
    }
}
