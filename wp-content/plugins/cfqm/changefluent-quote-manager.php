<?php
/**
 * Plugin Name:  ChangeFluent Quote Manager
 * Plugin URI:   https://changefluent.com
 * Description:  Complete quote management for trades businesses. Per-area pricing,
 *               geo-matched query distribution (top-10 pool, 3-5 response cap),
 *               customer portal, amendment engine, homeowner dashboard (magic-link),
 *               native PDF, Stripe deposit payments. Powers Fixdly.com.
 *               Reusable for LeafAndLush.com via brand config.
 * Version:      1.0.0
 * Requires at least: 6.3
 * Requires PHP: 8.1
 * Author:       ChangeFluent Ltd
 * Author URI:   https://changefluent.com
 * Text Domain:  cfqm
 * Domain Path:  /languages
 */

defined('ABSPATH') || exit;

// ── Constants ─────────────────────────────────────────────────────────────
define('CFQM_VERSION', '1.0.0');
define('CFQM_FILE',    __FILE__);
define('CFQM_DIR',     plugin_dir_path(__FILE__));
define('CFQM_URL',     plugin_dir_url(__FILE__));
define('CFQM_TPL',     CFQM_DIR . 'templates/');
define('CFQM_DB_VER',  1);

// ── Base trait (must exist before autoloader) ────────────────────────────
require_once CFQM_DIR . 'includes/trait-singleton.php';

// ── PSR-4-style autoloader: CFQM\Class_Name → class-class-name.php ───────
spl_autoload_register(static function (string $class): void {
    if (!str_starts_with($class, 'CFQM\\')) return;
    $rel  = str_replace(['CFQM\\', '_'], ['', '-'], $class);
    $file = CFQM_DIR . 'includes/class-' . strtolower($rel) . '.php';
    if (file_exists($file)) require_once $file;
});

// ── Optional Composer vendor (FPDF / DomPDF / mPDF) ─────────────────────
if (file_exists(CFQM_DIR . 'vendor/autoload.php')) {
    require_once CFQM_DIR . 'vendor/autoload.php';
}

// ── Lifecycle ────────────────────────────────────────────────────────────
register_activation_hook(__FILE__,   ['CFQM\\Plugin', 'activate']);
register_deactivation_hook(__FILE__, ['CFQM\\Plugin', 'deactivate']);

// ── Boot ─────────────────────────────────────────────────────────────────
add_action('plugins_loaded', static fn() => CFQM\Plugin::instance()->boot());
