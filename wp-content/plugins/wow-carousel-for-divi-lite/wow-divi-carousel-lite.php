<?php
/*
Plugin Name: Divi Carousel Free
Plugin URI:  https://divistack.io/divi-slider-pro/
Description: Divi Carousel plugin to create beautiful carousels with any modules.
Version:     2.1.5
Author:      DiviStack
Author URI:  https://divistack.io
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: divi-carousel-free
Domain Path: /languages
*/

if (!defined('ABSPATH')) {
    exit;
}

// Prevent duplicate plugin activation
function dcf_check_plugin_conflict()
{
    $conflicts = ['divi-carousel-free/divi-carousel-free.php', 'wow-carousel-for-divi-lite/wow-divi-carousel-lite.php'];
    $current = plugin_basename(__FILE__);

    foreach ($conflicts as $plugin) {
        if ($plugin !== $current && is_plugin_active($plugin)) {
            deactivate_plugins($current);
            wp_die(
                __('Another version of Divi Carousel is already active. Please deactivate it first.', 'divi-carousel-free'),
                __('Plugin Conflict', 'divi-carousel-free'),
                ['back_link' => true]
            );
        }
    }
}

register_activation_hook(__FILE__, 'dcf_check_plugin_conflict');

add_action('plugins_loaded', function () {
    if (!function_exists('is_plugin_active')) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }

    $conflicts = ['divi-carousel-free/divi-carousel-free.php', 'wow-carousel-for-divi-lite/wow-divi-carousel-lite.php'];
    $current = plugin_basename(__FILE__);

    foreach ($conflicts as $plugin) {
        if ($plugin !== $current && is_plugin_active($plugin)) {
            deactivate_plugins($current, true);
            add_action('admin_notices', function () {
                echo '<div class="notice notice-error"><p>' . __('Divi Carousel has been deactivated due to a conflict with another version.', 'divi-carousel-free') . '</p></div>';
            });
            return;
        }
    }
}, 1);

define('DCF_PLUGIN_VERSION', '2.1.5');
define('DCF_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('DCF_PLUGIN_URL', plugin_dir_url(__FILE__));
define('DCF_PLUGIN_ASSETS', trailingslashit(DCF_PLUGIN_URL . 'assets'));
define('DCF_PLUGIN_FILE', __FILE__);
define('DCF_PLUGIN_BASE', plugin_basename(__FILE__));

// Freemius flag (et = false, fs = true)
define('DCF_FS_ENABLE', true);

// Freemius – only load when enabled and file exists
if (DCF_FS_ENABLE && file_exists(__DIR__ . '/freemius.php')) {
    require_once __DIR__ . '/freemius.php';
}

// SPL Autoloader for Divi_Carousel_Free namespace
spl_autoload_register(function ($class) {
    $namespace = 'Divi_Carousel_Free\\';

    if (strpos($class, $namespace) !== 0) {
        return;
    }

    $class_name = str_replace($namespace, '', $class);
    $base_dir = DCF_PLUGIN_DIR . 'includes/';

    $file = $base_dir . strtolower(str_replace('_', '-', $class_name)) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

use Divi_Carousel_Free\Plugin;

// Run activation logic when the plugin is activated.
register_activation_hook(DCF_PLUGIN_FILE, array(Plugin::class, 'activation'));

// Bootstrap the plugin.
new Plugin();
