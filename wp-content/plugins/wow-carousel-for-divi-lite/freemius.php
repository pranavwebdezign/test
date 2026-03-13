<?php

if (!function_exists('dcl_fs')) {
    // Create a helper function for easy SDK access.
    function dcl_fs()
    {
        global $dcl_fs;

        if (!isset($dcl_fs)) {
            // Include Freemius SDK.
            require_once dirname(__FILE__) . '/vendor/freemius/start.php';

            $dcl_fs = fs_dynamic_init(array(
                'id'                  => '15011',
                'slug'                => 'wow-carousel-for-divi-lite',
                'premium_slug'        => 'divi-carousel-pro',
                'type'                => 'plugin',
                'public_key'          => 'pk_252a2b82cb841adfe6f9c575ca5d9',
                'is_premium'          => false,
                'has_premium_version' => true,
                'has_paid_plans'      => true,
                'has_addons'          => false,
                'menu'                => array(
                    'slug'           => 'divi-carousel-free',
                    'account'        => true,
                    'contact'        => false,
                    'support'        => false,
                )
            ));
        }

        return $dcl_fs;
    }

    // Init Freemius.
    dcl_fs();

    $dcl_fs = dcl_fs();

    // Set plugin icon
    $dcl_fs->add_filter('plugin_icon', function () {
        return __DIR__ . '/assets/imgs/icon.svg';
    });

    // Signal that SDK was initiated.
    do_action('dcl_fs_loaded');
}
