<?php
namespace CFQM;
defined('ABSPATH') || exit;

/**
 * Post_Types
 *
 * CPTs registered:
 *   cfqm_quote          – Quote document (core entity, per tradesperson)
 *   cfqm_quote_area     – Area / room line within a quote (hidden UI)
 *   cfqm_trade_profile  – Tradesperson business profile (linked to WP user)
 *   cfqm_hw_query       – Homeowner inbound query (geo-matching target)
 *   cfqm_customer       – Customer contact record
 *
 * Taxonomy:
 *   cfqm_category       – Flat (non-hierarchical) trade categories (~20 terms)
 *
 * ── Quote meta keys (all prefixed _cfqm_) ──────────────────────────────
 *   _cfqm_trade_id          int     post ID of cfqm_trade_profile
 *   _cfqm_customer_id       int     post ID of cfqm_customer
 *   _cfqm_customer_name     string
 *   _cfqm_customer_email    string
 *   _cfqm_customer_phone    string
 *   _cfqm_query_id          int     originating hw_query post ID (if Fixdly lead)
 *   _cfqm_status            string  draft|sent|viewed|approved|partial|declined|
 *                                   deposit_paid|expired|pending_amendment
 *   _cfqm_version           string  e.g. "1.2"
 *   _cfqm_iteration_limit   int
 *   _cfqm_iteration_used    int
 *   _cfqm_vat_enabled       bool
 *   _cfqm_vat_rate          float   e.g. 20.0
 *   _cfqm_deposit_type      string  'percent'|'none'
 *   _cfqm_deposit_percent   float
 *   _cfqm_deposit_amount    float   (calculated)
 *   _cfqm_subtotal          float
 *   _cfqm_vat_amount        float
 *   _cfqm_grand_total       float
 *   _cfqm_validity_days     int
 *   _cfqm_validity_date     string  Y-m-d
 *   _cfqm_portal_token      string  raw token for always-latest link
 *   _cfqm_pdf_path          string  server path to latest PDF
 *   _cfqm_stripe_link       string  Stripe Payment Link URL
 *   _cfqm_stripe_intent_id  string  for idempotency
 *   _cfqm_project_label     string  homeowner-editable project grouping
 *   _cfqm_sent_at           string  Y-m-d H:i:s
 *   _cfqm_signed_at         string
 *   _cfqm_signature         string  typed e-signature text
 *   _cfqm_amendment_history json    array of past amendment snapshots
 *   _cfqm_selected_areas    json    area IDs the homeowner accepted
 *   _cfqm_snapshots         json    version snapshots keyed by version string
 *
 * ── Area meta keys ───────────────────────────────────────────────────────
 *   _cfqm_quote_id          int
 *   _cfqm_area_type         string  'included'|'optional'
 *   _cfqm_scope             string  rich-text scope description
 *   _cfqm_price             float
 *   _cfqm_materials         string
 *   _cfqm_labour_hours      float
 *   _cfqm_validity_date     string
 *   _cfqm_photo_ids         json    attachment IDs
 *   _cfqm_sort_order        int
 *
 * ── Trade profile meta keys ──────────────────────────────────────────────
 *   _cfqm_user_id           int
 *   _cfqm_postcode          string
 *   _cfqm_radius_miles      int
 *   _cfqm_lat               float
 *   _cfqm_lng               float
 *   _cfqm_activity_score    float   0–100 (higher = more active)
 *   _cfqm_phone             string
 *   _cfqm_email             string
 *   _cfqm_address           string
 *   _cfqm_response_rate     float   0–1
 *   _cfqm_last_active       string  Y-m-d H:i:s
 *
 * ── HW Query meta keys ───────────────────────────────────────────────────
 *   _cfqm_hw_name           string
 *   _cfqm_hw_email          string
 *   _cfqm_hw_phone          string
 *   _cfqm_hw_postcode       string
 *   _cfqm_hw_lat            float
 *   _cfqm_hw_lng            float
 *   _cfqm_hw_job_desc       string
 *   _cfqm_hw_status         string  open|quotes_full|closed|reopened
 *   _cfqm_matched_trades    json    ordered array of trade_profile IDs
 *   _cfqm_responses         json    {trade_id: quote_id} map
 *   _cfqm_project_label     string
 *   _cfqm_submitted_at      string
 */
class Post_Types {
    use Singleton;

    const CATEGORIES = [
        'Builder','Carpenter & Joiner','Decorator & Painter','Electrician',
        'Gas Engineer','General Handyman','Glazier','Groundworker',
        'Heating Engineer','Kitchen Fitter','Landscaper & Gardener','Locksmith',
        'Plasterer','Plumber','Roofer','Scaffolder','Security Systems',
        'Tiler','Tree Surgeon','Underfloor Heating',
    ];

    public function boot(): void {
        add_action('init', [$this, 'register_all'], 5);
    }

    public function register_all(): void {
        $this->register_quote();
        $this->register_quote_area();
        $this->register_trade_profile();
        $this->register_hw_query();
        $this->register_customer();
        $this->register_category_taxonomy();
    }

    private function register_quote(): void {
        register_post_type('cfqm_quote', [
            'label'           => 'Quotes',
            'labels'          => $this->labels('Quote', 'Quotes'),
            'public'          => false,
            'show_ui'         => true,
            'show_in_menu'    => 'cfqm-admin',
            'capability_type' => 'post',
            'map_meta_cap'    => true,
            'supports'        => ['title','author'],
            'has_archive'     => false,
            'rewrite'         => false,
            'show_in_rest'    => false,
            'taxonomies'      => ['cfqm_category'],
        ]);
    }

    private function register_quote_area(): void {
        register_post_type('cfqm_quote_area', [
            'label'        => 'Quote Areas',
            'public'       => false,
            'show_ui'      => false,
            'supports'     => ['title'],
            'has_archive'  => false,
            'rewrite'      => false,
        ]);
    }

    private function register_trade_profile(): void {
        register_post_type('cfqm_trade_profile', [
            'label'           => 'Trade Profiles',
            'labels'          => $this->labels('Trade Profile', 'Trade Profiles'),
            'public'          => false,
            'show_ui'         => true,
            'show_in_menu'    => 'cfqm-admin',
            'capability_type' => 'post',
            'map_meta_cap'    => true,
            'supports'        => ['title','thumbnail'],
            'has_archive'     => false,
            'rewrite'         => false,
            'taxonomies'      => ['cfqm_category'],
        ]);
    }

    private function register_hw_query(): void {
        register_post_type('cfqm_hw_query', [
            'label'           => 'HO Queries',
            'labels'          => $this->labels('HO Query', 'HO Queries'),
            'public'          => false,
            'show_ui'         => true,
            'show_in_menu'    => 'cfqm-admin',
            'capability_type' => 'post',
            'map_meta_cap'    => true,
            'supports'        => ['title'],
            'has_archive'     => false,
            'rewrite'         => false,
            'taxonomies'      => ['cfqm_category'],
        ]);
    }

    private function register_customer(): void {
        register_post_type('cfqm_customer', [
            'label'           => 'Customers',
            'labels'          => $this->labels('Customer', 'Customers'),
            'public'          => false,
            'show_ui'         => true,
            'show_in_menu'    => 'cfqm-admin',
            'capability_type' => 'post',
            'map_meta_cap'    => true,
            'supports'        => ['title'],
            'has_archive'     => false,
            'rewrite'         => false,
        ]);
    }

    private function register_category_taxonomy(): void {
        register_taxonomy('cfqm_category', [
            'cfqm_quote','cfqm_trade_profile','cfqm_hw_query',
        ], [
            'label'             => 'Trade Category',
            'hierarchical'      => false,   // flat list per meeting decision
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_rest'      => false,
            'rewrite'           => false,
        ]);

        if (!get_option('cfqm_cats_seeded')) {
            foreach (self::CATEGORIES as $name) {
                if (!term_exists($name, 'cfqm_category')) {
                    wp_insert_term($name, 'cfqm_category');
                }
            }
            update_option('cfqm_cats_seeded', true);
        }
    }

    private function labels(string $s, string $p): array {
        return [
            'name'               => $p,
            'singular_name'      => $s,
            'add_new_item'       => "Add New $s",
            'edit_item'          => "Edit $s",
            'new_item'           => "New $s",
            'view_item'          => "View $s",
            'search_items'       => "Search $p",
            'not_found'          => "No $p found.",
            'not_found_in_trash' => "No $p in trash.",
        ];
    }
}
