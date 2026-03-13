<?php

namespace Divi_Carousel_Free;

defined('ABSPATH') || exit;

class Plugin
{
    public function __construct()
    {
        $this->init_hooks();
        $this->include_functions();
        $this->init_classes();
    }

    private function init_hooks()
    {
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        add_action('et_builder_ready', array($this, 'load_divi_modules'), 11);
        add_action('admin_init', array($this, 'redirect_after_activation'));
    }

    private function init_classes()
    {
        Assets::get_instance();

        // Always load Modules_API for REST API endpoints
        new Modules_API();

        if (is_admin()) {
            new Admin();
            new Admin_Notice();
        }
    }

    public function include_functions()
    {
        require_once DCF_PLUGIN_DIR . 'includes/functions.php';
    }

    public static function activation()
    {
        update_option('divi_carousel_free_version', DCF_PLUGIN_VERSION);

        if (!get_option('divi_carousel_free_activation_time')) {
            update_option('divi_carousel_free_activation_time', time());
        }

        if (!get_option('divi_carousel_free_install_date')) {
            update_option('divi_carousel_free_install_date', time());
        }

        // Initialize default module settings
        if (!get_option('dcf_carousel_modules')) {
            update_option('dcf_carousel_modules', [
                'carousel_maker' => true,
                'image_carousel' => true,
                'logo_carousel' => true,
            ]);
        }

        // Set redirect flag for first-time activation
        set_transient('dcf_activation_redirect', true, 30);
    }

    public function redirect_after_activation()
    {
        // Check if this is a first-time activation
        if (!get_transient('dcf_activation_redirect')) {
            return;
        }

        // Delete the redirect transient
        delete_transient('dcf_activation_redirect');

        // Don't redirect if activating multiple plugins or doing AJAX
        if (isset($_GET['activate-multi']) || wp_doing_ajax()) {
            return;
        }

        // Redirect to dashboard
        wp_safe_redirect(admin_url('admin.php?page=divi-carousel-free'));
        exit;
    }

    public function load_textdomain()
    {
        load_plugin_textdomain(
            'divi-carousel-free',
            false,
            dirname(dirname(plugin_basename(__FILE__))) . '/languages/'
        );
    }


    public function load_divi_modules()
    {
        $modules_dir = DCF_PLUGIN_DIR . 'includes/modules/';

        // Get enabled modules
        $modules = get_option('dcf_carousel_modules', [
            'carousel_maker' => true,
            'image_carousel' => true,
            'logo_carousel' => true,
        ]);

        // Always load base module
        require_once $modules_dir . 'Base.php';

        // Load Carousel Maker
        if (!empty($modules['carousel_maker'])) {
            require_once $modules_dir . 'CarouselMaker.php';
            require_once $modules_dir . 'CarouselMakerChild.php';
        }

        // Load Image Carousel
        if (!empty($modules['image_carousel'])) {
            require_once $modules_dir . 'ImageCarousel.php';
            require_once $modules_dir . 'ImageCarouselChild.php';
        }

        // Load Logo Carousel
        if (!empty($modules['logo_carousel'])) {
            require_once $modules_dir . 'LogoCarousel.php';
            require_once $modules_dir . 'LogoCarouselChild.php';
        }
    }
}
