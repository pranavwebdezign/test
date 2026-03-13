<?php
namespace CFQM;
defined('ABSPATH') || exit;

/**
 * Settings
 *
 * Single WP option key `cfqm_settings` stores all config.
 * Multi-brand: brand_name, brand_industry, brand_tradesperson, brand_homeowner
 * can all be changed to reuse on LeafAndLush.com with zero code edits.
 */
class Settings {
    use Singleton;

    const OPTION = 'cfqm_settings';

    private array $data = [];

    public function boot(): void {
        $this->data = (array) get_option(self::OPTION, []);
    }

    public function install_defaults(): void {
        $defaults = [
            // ── Brand / multi-brand config ─────────────────────────────
            'brand_name'            => get_bloginfo('name'),
            'brand_industry'        => 'trades',          // 'trades' | 'landscaping'
            'brand_tradesperson'    => 'Tradesperson',    // UI label
            'brand_homeowner'       => 'Homeowner',
            'brand_accent_colour'   => '#1a5276',
            'brand_logo_id'         => 0,                 // attachment ID

            // ── Stripe ─────────────────────────────────────────────────
            'stripe_mode'           => 'test',
            'stripe_test_pk'        => '',
            'stripe_test_sk'        => '',
            'stripe_live_pk'        => '',
            'stripe_live_sk'        => '',
            'stripe_webhook_secret' => '',

            // ── Email / SMTP ───────────────────────────────────────────
            'email_from_name'       => get_bloginfo('name'),
            'email_from_address'    => get_bloginfo('admin_email'),
            'email_quotes_address'  => '',  // e.g. quotes@fixdly.com

            // ── Geo-matching ───────────────────────────────────────────
            'query_pool_size'       => 10,  // top N trades notified per query
            'response_cap'          => 5,   // first N quotes accepted per query

            // ── Page IDs (set after admin creates pages) ───────────────
            'page_portal'           => 0,
            'page_dashboard_hw'     => 0,
            'page_dashboard_trade'  => 0,
            'page_magic_link'       => 0,
            'page_query_form'       => 0,
        ];
        update_option(self::OPTION, $defaults);
        $this->data = $defaults;
    }

    // ─────────────────────────────────────────────────────────────────────
    public function get(string $key, mixed $default = null): mixed {
        return $this->data[$key] ?? $default;
    }

    public function all(): array { return $this->data; }

    public function update(array $values): void {
        $this->data = array_merge($this->data, $values);
        update_option(self::OPTION, $this->data);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────
    public function stripe_sk(): string {
        $mode = $this->get('stripe_mode', 'test');
        return (string) $this->get("stripe_{$mode}_sk", '');
    }

    public function stripe_pk(): string {
        $mode = $this->get('stripe_mode', 'test');
        return (string) $this->get("stripe_{$mode}_pk", '');
    }

    /** Build the portal URL, optionally appending a token. */
    public function portal_url(string $token = ''): string {
        $id   = (int) $this->get('page_portal');
        $base = $id ? (string) get_permalink($id) : home_url('/quote-portal/');
        return $token ? add_query_arg('cfqm_token', rawurlencode($token), $base) : $base;
    }

    /** Homeowner dashboard URL. */
    public function hw_dashboard_url(): string {
        $id = (int) $this->get('page_dashboard_hw');
        return $id ? (string) get_permalink($id) : home_url('/my-quotes/');
    }
}
