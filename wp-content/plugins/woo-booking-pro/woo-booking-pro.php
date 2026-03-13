<?php
/**
 * Plugin Name: WooBooking Pro
 * Plugin URI:  https://yoursite.com/woo-booking-pro
 * Description: A complete WooCommerce booking system with postcode-zone routing, custom add-ons, Google Maps autocomplete, date pickers, and an order summary — no ACF or third-party form plugins required. Works with Gutenberg, WPBakery, and Divi.
 * Version:     1.0.0
 * Author:      Your Name
 * Text Domain: woo-booking-pro
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 6.0
 */

defined( 'ABSPATH' ) || exit;

// ─────────────────────────────────────────────
// Constants
// ─────────────────────────────────────────────
define( 'WBP_VERSION',   '1.0.0' );
define( 'WBP_PLUGIN_FILE', __FILE__ );
define( 'WBP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WBP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// ─────────────────────────────────────────────
// Bootstrap
// ─────────────────────────────────────────────
require_once WBP_PLUGIN_DIR . 'includes/class-wbp-settings.php';
require_once WBP_PLUGIN_DIR . 'includes/class-wbp-ajax.php';
require_once WBP_PLUGIN_DIR . 'includes/class-wbp-frontend.php';
require_once WBP_PLUGIN_DIR . 'includes/class-wbp-woocommerce.php';
require_once WBP_PLUGIN_DIR . 'admin/class-wbp-admin.php';

add_action( 'plugins_loaded', function () {
    if ( ! class_exists( 'WooCommerce' ) ) {
        add_action( 'admin_notices', function () {
            echo '<div class="notice notice-error"><p><strong>WooBooking Pro</strong> requires WooCommerce to be active.</p></div>';
        } );
        return;
    }

    WBP_Admin::init();
    WBP_Settings::init();
    WBP_Ajax::init();
    WBP_Frontend::init();
    WBP_WooCommerce::init();
} );

// ─────────────────────────────────────────────
// Activation
// ─────────────────────────────────────────────
register_activation_hook( __FILE__, function () {
    if ( ! get_option( 'wbp_settings' ) ) {
        update_option( 'wbp_settings', [
            'google_maps_api_key'       => '',
            'currency_symbol'           => '£',
            'terms_url'                 => '/terms/',
            'privacy_url'               => '/privacy-policy/',
            'order_notes_title'         => 'Special Instructions',
            'order_notes_placeholder'   => 'Add any special instructions here…',
            'banner_image_url'          => '',
            'step_labels'               => [
                'Enter your Postcode',
                'See Service Availability',
                'Customise your order',
                'Summary + Details',
            ],
        ] );
    }
    if ( ! get_option( 'wbp_zones' ) ) {
        update_option( 'wbp_zones', '[]' );
    }
} );

require_once WBP_PLUGIN_DIR . 'includes/class-wbp-config-cpt.php';
require_once WBP_PLUGIN_DIR . 'includes/class-wbp-addons-admin.php';

add_action( 'plugins_loaded', function () {
    WBP_Config_CPT::init();
    WBP_Addons_Admin::init();
}, 11 );
