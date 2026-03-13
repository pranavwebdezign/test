<?php

if (!function_exists('divi_carousel_builder_library')) {
    function divi_carousel_builder_library()
    {
        $layouts = array(
            '-1' => esc_html__(' -- Select a Layout -- ', 'divi-carousel-free'),
        );

        $saved_layouts = get_posts(
            array(
                'post_type'      => 'et_pb_layout',
                'post_status'    => 'publish',
                'posts_per_page' => -1,
                'orderby'        => 'title',
            )
        );

        if (!empty($saved_layouts)) {
            $layout_options = wp_list_pluck($saved_layouts, 'post_title', 'ID');
            $layouts        = array_merge($layouts, $layout_options);
        }

        return $layouts;
    }
}

if (!function_exists('dcm_global_assets_list')) {
    function dcm_global_assets_list($global_list)
    {
        $assets_list   = array();
        $assets_prefix = et_get_dynamic_assets_path();

        $assets_list['et_icons_fa'] = array(
            'css' => "{$assets_prefix}/css/icons_fa_all.css",
        );

        return array_merge($global_list, $assets_list);
    }
}

if (!function_exists('dcm_inject_fa_icons')) {
    function dcm_inject_fa_icons($icon_data)
    {
        if (function_exists('et_pb_maybe_fa_font_icon') && et_pb_maybe_fa_font_icon($icon_data)) {
            add_filter('et_global_assets_list', 'dcm_global_assets_list');
            add_filter('et_late_global_assets_list', 'dcm_global_assets_list');
        }
    }
}
