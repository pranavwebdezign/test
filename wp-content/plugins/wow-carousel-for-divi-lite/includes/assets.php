<?php

namespace Divi_Carousel_Free;

class Assets
{
    private static $instance = null;

    public static function get_instance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct()
    {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_builder_scripts'));
    }

    public function enqueue_assets($prefix, $dependencies = array('react-dom', 'react'), $include_style = true, $include_script = true)
    {
        // Get asset info from wp-scripts generated file
        $asset_file = DCF_PLUGIN_DIR . "dist/{$prefix}.asset.php";
        $asset_info = file_exists($asset_file) ? include $asset_file : [
            'dependencies' => [],
            'version' => DCF_PLUGIN_VERSION,
        ];

        if ($include_script) {
            $script_path = DCF_PLUGIN_URL . "dist/{$prefix}.js";
            if (file_exists(DCF_PLUGIN_DIR . "dist/{$prefix}.js")) {
                wp_enqueue_script(
                    "dcl-{$prefix}",
                    $script_path,
                    array_merge($asset_info['dependencies'], $dependencies),
                    $asset_info['version'],
                    true
                );
            }
        }

        if ($include_style) {
            $style_path = DCF_PLUGIN_URL . "dist/{$prefix}.css";
            if (file_exists(DCF_PLUGIN_DIR . "dist/{$prefix}.css")) {
                wp_enqueue_style(
                    "dcl-{$prefix}",
                    $style_path,
                    array(),
                    $asset_info['version']
                );
            }
        }
    }

    public function enqueue_frontend_scripts()
    {
        $this->enqueue_libraries();
        $this->enqueue_assets('frontend', array('jquery'), true, true);
        $this->enqueue_assets('frontend-styles', array(), true, false);
    }

    private function enqueue_libraries()
    {
        wp_enqueue_script('dcl-slick', DCF_PLUGIN_ASSETS . 'libs/slick/slick.min.js', array('jquery'), DCF_PLUGIN_VERSION, true);
        wp_enqueue_script('dcl-magnific', DCF_PLUGIN_ASSETS . 'libs/magnific/magnific-popup.min.js', array('jquery'), DCF_PLUGIN_VERSION, true);

        wp_enqueue_style('dcl-slick', DCF_PLUGIN_ASSETS . 'libs/slick/slick.min.css', array(), DCF_PLUGIN_VERSION);
        wp_enqueue_style('dcl-magnific', DCF_PLUGIN_ASSETS . 'libs/magnific/magnific-popup.min.css', array(), DCF_PLUGIN_VERSION);
    }

    public function enqueue_builder_scripts()
    {
        if (function_exists('et_core_is_fb_enabled') && et_core_is_fb_enabled()) {
            $this->enqueue_assets('builder', array('react-dom', 'react'), false, true);
        }
    }
}
