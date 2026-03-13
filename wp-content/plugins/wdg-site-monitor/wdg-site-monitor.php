<?php
/**
 * Plugin Name: WDG Site Monitor
 * Description: Generates a per-site token and sends health + update status to an external portal (MVP).
 * Version: 0.1.0
 * Author: WebDezign GPT
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) { exit; }

define('WDG_SM_VERSION', '0.1.0');
define('WDG_SM_SLUG', 'wdg-site-monitor');
define('WDG_SM_OPT', 'wdg_sm_options');

require_once __DIR__ . '/includes/class-wdg-sm.php';
require_once __DIR__ . '/includes/rest.php';

register_activation_hook(__FILE__, array('WDG_SM', 'activate'));
register_deactivation_hook(__FILE__, array('WDG_SM', 'deactivate'));

add_action('plugins_loaded', function() {
    WDG_SM::init();
});
