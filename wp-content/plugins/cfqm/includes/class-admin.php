<?php
namespace CFQM;
defined('ABSPATH') || exit;

/**
 * Admin
 *
 * WordPress admin integration:
 *   - Custom menu: "Quote Manager" with sub-pages
 *   - Settings page (Stripe, Email, Geo-matching, Brand, Pages)
 *   - Meta boxes on cfqm_trade_profile, cfqm_hw_query, cfqm_quote
 *   - Geocoding on trade profile save
 */
class Admin {
    use Singleton;

    public function boot(): void {
        add_action('admin_menu',                [$this, 'register_menu']);
        add_action('admin_init',                [$this, 'register_settings']);
        add_action('add_meta_boxes',            [$this, 'register_meta_boxes']);
        add_action('save_post_cfqm_trade_profile', [$this, 'save_trade_profile_meta'], 10, 2);
        add_action('save_post_cfqm_quote',       [$this, 'save_quote_meta'], 10, 2);
        add_action('admin_enqueue_scripts',      [$this, 'enqueue_admin_assets']);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Admin menu
    // ─────────────────────────────────────────────────────────────────────

    public function register_menu(): void {
        add_menu_page(
            'Quote Manager',
            'Quote Manager',
            'edit_posts',
            'cfqm-admin',
            [$this, 'page_dashboard'],
            'dashicons-clipboard',
            26
        );
        // Dashboard submenu (rename the parent duplicate)
        add_submenu_page('cfqm-admin', 'Dashboard', 'Dashboard', 'edit_posts',
            'cfqm-admin', [$this, 'page_dashboard']);
        
        // Note: Quotes, Trade Profiles, HO Queries, and Customers are automatically
        // added by WordPress because their post types have 'show_in_menu' => 'cfqm-admin'
        // No need to add them manually here.
        
        // Settings submenu (manual page, not a post type)
        add_submenu_page('cfqm-admin', 'Settings',  'Settings',  'manage_options',
            'cfqm-settings', [$this, 'page_settings']);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Settings registration (WordPress Settings API)
    // ─────────────────────────────────────────────────────────────────────

    public function register_settings(): void {
        register_setting('cfqm_settings_group', Settings::OPTION, [
            'sanitize_callback' => [$this, 'sanitize_settings'],
        ]);
    }

    public function sanitize_settings(array $input): array {
        $clean = Settings::instance()->all();

        $text_keys = [
            'brand_name','brand_industry','brand_tradesperson','brand_homeowner',
            'brand_accent_colour','email_from_name','email_from_address','email_quotes_address',
            'stripe_mode','stripe_test_pk','stripe_test_sk','stripe_live_pk','stripe_live_sk',
            'stripe_webhook_secret',
        ];
        $int_keys  = ['query_pool_size','response_cap','brand_logo_id',
                      'page_portal','page_dashboard_hw','page_dashboard_trade',
                      'page_magic_link','page_query_form'];

        foreach ($text_keys as $k) {
            if (isset($input[$k])) {
                $clean[$k] = sanitize_text_field($input[$k]);
            }
        }
        foreach ($int_keys as $k) {
            if (isset($input[$k])) {
                $clean[$k] = (int) $input[$k];
            }
        }

        Settings::instance()->update($clean);
        return $clean;
    }

    // ─────────────────────────────────────────────────────────────────────
    // Admin pages
    // ─────────────────────────────────────────────────────────────────────

    public function page_dashboard(): void {
        include CFQM_DIR . 'admin/views/admin-dashboard.php';
    }

    public function page_settings(): void {
        include CFQM_DIR . 'admin/views/settings.php';
    }

    // ─────────────────────────────────────────────────────────────────────
    // Meta boxes
    // ─────────────────────────────────────────────────────────────────────

    public function register_meta_boxes(): void {
        add_meta_box('cfqm_trade_profile_meta', 'Trade Profile Details',
            [$this, 'meta_box_trade_profile'], 'cfqm_trade_profile', 'normal', 'high');

        add_meta_box('cfqm_hw_query_meta', 'Query Details',
            [$this, 'meta_box_hw_query'], 'cfqm_hw_query', 'normal', 'high');

        add_meta_box('cfqm_quote_meta', 'Quote Details & Timeline',
            [$this, 'meta_box_quote'], 'cfqm_quote', 'normal', 'high');
    }

    public function meta_box_trade_profile(\WP_Post $post): void {
        wp_nonce_field('cfqm_trade_meta', 'cfqm_trade_nonce');
        include CFQM_DIR . 'admin/views/meta-box-trade-profile.php';
    }

    public function meta_box_hw_query(\WP_Post $post): void {
        include CFQM_DIR . 'admin/views/meta-box-hw-query.php';
    }

    public function meta_box_quote(\WP_Post $post): void {
        include CFQM_DIR . 'admin/views/meta-box-quote.php';
    }

    // ─────────────────────────────────────────────────────────────────────
    // Save meta boxes
    // ─────────────────────────────────────────────────────────────────────

    public function save_trade_profile_meta(int $post_id, \WP_Post $post): void {
        if (!isset($_POST['cfqm_trade_nonce'])
            || !wp_verify_nonce($_POST['cfqm_trade_nonce'], 'cfqm_trade_meta')
            || defined('DOING_AUTOSAVE') && DOING_AUTOSAVE
            || !current_user_can('edit_post', $post_id)
        ) return;

        $fields = [
            '_cfqm_user_id'        => 'int',
            '_cfqm_postcode'       => 'text',
            '_cfqm_radius_miles'   => 'int',
            '_cfqm_phone'          => 'text',
            '_cfqm_email'          => 'email',
            '_cfqm_address'        => 'textarea',
        ];

        foreach ($fields as $key => $type) {
            $raw = $_POST[$key] ?? null;
            if ($raw === null) continue;
            $val = match ($type) {
                'int'      => (int) $raw,
                'email'    => sanitize_email($raw),
                'textarea' => sanitize_textarea_field($raw),
                default    => sanitize_text_field($raw),
            };
            update_post_meta($post_id, $key, $val);
        }

        // Geocode postcode on save
        $postcode = sanitize_text_field($_POST['_cfqm_postcode'] ?? '');
        if ($postcode) {
            $coords = Geo_Matching::instance()->geocode($postcode);
            if ($coords) {
                update_post_meta($post_id, '_cfqm_lat', $coords['lat']);
                update_post_meta($post_id, '_cfqm_lng', $coords['lng']);
            }
        }
    }

    public function save_quote_meta(int $post_id, \WP_Post $post): void {
        // Quote meta is managed entirely via AJAX (Quote_Builder class)
        // Nothing to save here during standard post save
    }

    // ─────────────────────────────────────────────────────────────────────
    // Admin assets
    // ─────────────────────────────────────────────────────────────────────

    public function enqueue_admin_assets(string $hook): void {
        $screens = ['toplevel_page_cfqm-admin', 'quote-manager_page_cfqm-settings',
                    'post.php', 'post-new.php'];
        if (!in_array($hook, $screens, true)) return;

        wp_enqueue_style('cfqm-admin', CFQM_URL . 'assets/css/admin.css', [], CFQM_VERSION);
        wp_enqueue_script('cfqm-admin', CFQM_URL . 'assets/js/frontend.js', ['jquery'], CFQM_VERSION, true);
        wp_localize_script('cfqm-admin', 'cfqmData', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('cfqm_nonce'),
        ]);
    }
}
