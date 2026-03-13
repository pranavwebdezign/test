<?php
namespace CFQM;
defined('ABSPATH') || exit;

/**
 * Plugin – master singleton that boots every subsystem in dependency order.
 */
final class Plugin {
    use Singleton;

    public function boot(): void {
        $this->load_textdomain();

        // Infrastructure
        Database::instance()->boot();
        Settings::instance()->boot();

        // Data model & tiers
        Post_Types::instance()->boot();
        Subscription_Tiers::instance()->boot();

        // Core logic
        Token_Manager::instance()->boot();
        Geo_Matching::instance()->boot();
        Quote_Builder::instance()->boot();
        Amendment_Engine::instance()->boot();
        Versioning::instance()->boot();
        Audit_Trail::instance()->boot();
        PDF_Generator::instance()->boot();

        // Payments + comms
        Stripe::instance()->boot();
        Email_Notifications::instance()->boot();

        // Frontend portals & shortcodes
        Homeowner_Query::instance()->boot();
        Customer_Portal::instance()->boot();
        Homeowner_Dashboard::instance()->boot();
        Trades_Dashboard::instance()->boot();

        // Scheduled jobs
        Cron::instance()->boot();

        // Admin UI
        if (is_admin()) {
            Admin::instance()->boot();
        }

        do_action('cfqm_booted');
    }

    private function load_textdomain(): void {
        load_plugin_textdomain('cfqm', false,
            dirname(plugin_basename(CFQM_FILE)) . '/languages');
    }

    // ── Activation ────────────────────────────────────────────────────────
    public static function activate(): void {
        // Pre-load required classes (autoloader not yet registered during activation)
        foreach (['database','settings','post-types','subscription-tiers'] as $cls) {
            require_once CFQM_DIR . "includes/class-{$cls}.php";
        }

        Database::instance()->install_tables();
        Post_Types::instance()->register_all();
        flush_rewrite_rules();

        if (!get_option('cfqm_settings')) {
            Settings::instance()->install_defaults();
        }

        if (!wp_next_scheduled('cfqm_hourly')) {
            wp_schedule_event(time(), 'hourly', 'cfqm_hourly');
        }

        update_option('cfqm_db_version', CFQM_DB_VER);
    }

    // ── Deactivation ─────────────────────────────────────────────────────
    public static function deactivate(): void {
        flush_rewrite_rules();
        wp_clear_scheduled_hook('cfqm_hourly');
    }
}
