<?php
namespace CFQM;
defined('ABSPATH') || exit;

/**
 * Trades_Dashboard
 *
 * Shortcode: [cfqm_trades_dashboard]
 *
 * Three tabs:
 *   1. Pipeline      – all active quotes with status badge, iteration count, last activity
 *   2. Amendments    – pending_amendment quotes with Review CTA
 *   3. Awaiting Deposit – approved/partial quotes without deposit_paid yet
 *
 * Stat cards: quotes this month, win rate, avg time to approval
 * Filter by status, sortable columns via AJAX.
 */
class Trades_Dashboard {
    use Singleton;

    public function boot(): void {
        add_shortcode('cfqm_trades_dashboard',       [$this, 'shortcode_dashboard']);
        add_action('wp_ajax_cfqm_get_pipeline',      [$this, 'ajax_get_pipeline']);
        add_action('wp_ajax_cfqm_get_pipeline_stats',[$this, 'ajax_get_stats']);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Shortcode
    // ─────────────────────────────────────────────────────────────────────

    public function shortcode_dashboard(array $atts): string {
        if (!is_user_logged_in()) {
            return '<p class="cfqm-notice">' . esc_html__('Please log in to access your Toolbox.', 'cfqm') . '</p>';
        }

        $plan = Subscription_Tiers::instance()->get_user_plan();
        if ($plan === Subscription_Tiers::PLAN_NONE) {
            return '<p class="cfqm-notice">' . esc_html__('You need an active subscription to access this feature.', 'cfqm') . '</p>';
        }

        $this->enqueue_assets();
        ob_start();
        include CFQM_TPL . 'dashboard/trades-dashboard.php';
        return ob_get_clean();
    }

    // ─────────────────────────────────────────────────────────────────────
    // AJAX: pipeline data (tab 1 + 2 + 3)
    // ─────────────────────────────────────────────────────────────────────

    public function ajax_get_pipeline(): void {
        check_ajax_referer('cfqm_nonce', 'nonce');

        $user_id   = get_current_user_id();
        $tab       = sanitize_key($_POST['tab'] ?? 'pipeline');
        $status_f  = sanitize_key($_POST['status_filter'] ?? '');

        $statuses = match ($tab) {
            'amendments'      => ['pending_amendment'],
            'awaiting_deposit'=> ['approved', 'partial'],
            default           => ['draft','sent','viewed','approved','partial',
                                  'declined','deposit_paid','expired','pending_amendment'],
        };

        if ($status_f && $tab === 'pipeline') {
            $statuses = [$status_f];
        }

        $meta_query = [['key' => '_cfqm_status', 'value' => $statuses, 'compare' => 'IN']];

        // For awaiting_deposit: exclude quotes that already have deposit paid
        if ($tab === 'awaiting_deposit') {
            $meta_query[] = [
                'relation' => 'OR',
                ['key' => '_cfqm_deposit_amount', 'value' => 0, 'compare' => '>'],
            ];
        }

        $posts = get_posts([
            'post_type'      => 'cfqm_quote',
            'post_status'    => 'publish',
            'author'         => $user_id,
            'posts_per_page' => 100,
            'meta_query'     => $meta_query,
            'orderby'        => 'modified',
            'order'          => 'DESC',
        ]);

        $rows = [];
        foreach ($posts as $post) {
            $m = get_post_meta($post->ID);
            $row = [
                'id'              => $post->ID,
                'customer_name'   => $m['_cfqm_customer_name'][0]   ?? '',
                'status'          => $m['_cfqm_status'][0]           ?? '',
                'version'         => $m['_cfqm_version'][0]          ?? '1.0',
                'iteration_used'  => (int)($m['_cfqm_iteration_used'][0]  ?? 0),
                'iteration_limit' => (int)($m['_cfqm_iteration_limit'][0] ?? 5),
                'grand_total'     => (float)($m['_cfqm_grand_total'][0]   ?? 0),
                'deposit_amount'  => (float)($m['_cfqm_deposit_amount'][0]?? 0),
                'stripe_link'     => $m['_cfqm_stripe_link'][0]      ?? '',
                'portal_token'    => $m['_cfqm_portal_token'][0]     ?? '',
                'sent_at'         => $m['_cfqm_sent_at'][0]          ?? '',
                'last_modified'   => $post->post_modified,
                'query_source'    => (int)($m['_cfqm_query_id'][0] ?? 0) ? 'Fixdly Lead' : 'Direct',
                'portal_url'      => Settings::instance()->portal_url($m['_cfqm_portal_token'][0] ?? ''),
            ];

            // Amendment history count
            $hist  = json_decode($m['_cfqm_amendment_history'][0] ?? '[]', true) ?: [];
            $row['amendment_count'] = count($hist);

            $rows[] = $row;
        }

        wp_send_json_success(['rows' => $rows]);
    }

    // ─────────────────────────────────────────────────────────────────────
    // AJAX: stats cards
    // ─────────────────────────────────────────────────────────────────────

    public function ajax_get_stats(): void {
        check_ajax_referer('cfqm_nonce', 'nonce');

        $user_id = get_current_user_id();

        // Quotes sent this month
        $monthly  = Subscription_Tiers::instance()->get_monthly_usage($user_id);
        $plan     = Subscription_Tiers::instance()->get_user_plan($user_id);
        $limit    = Subscription_Tiers::instance()->get_limit($plan, 'quotes_per_month');

        // Win rate: approved / (approved + declined)
        $approved_count = $this->count_status($user_id, ['approved','partial','deposit_paid']);
        $declined_count = $this->count_status($user_id, ['declined']);
        $total_acted    = $approved_count + $declined_count;
        $win_rate       = $total_acted > 0 ? round($approved_count / $total_acted * 100, 1) : 0;

        // Average hours from sent → first viewed
        $avg_hrs = $this->avg_time_to_viewed($user_id);

        wp_send_json_success([
            'monthly_used'  => $monthly,
            'monthly_limit' => $limit,
            'win_rate'      => $win_rate,
            'avg_view_hrs'  => $avg_hrs,
            'plan_label'    => Subscription_Tiers::instance()->plan_label($plan),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────

    private function count_status(int $user_id, array $statuses): int {
        global $wpdb;
        $in = implode("','", array_map('esc_sql', $statuses));
        return (int) $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON pm.post_id=p.ID AND pm.meta_key='_cfqm_status'
            WHERE p.post_type='cfqm_quote' AND p.post_status='publish'
              AND p.post_author=%d AND pm.meta_value IN ('$in')
        ", $user_id));
    }

    private function avg_time_to_viewed(int $user_id): float {
        global $wpdb;
        // Average hours between sent_at and first viewed event
        $rows = $wpdb->get_results($wpdb->prepare("
            SELECT pm_sent.meta_value AS sent_at, e.created_at AS viewed_at
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm_sent ON pm_sent.post_id=p.ID AND pm_sent.meta_key='_cfqm_sent_at'
            INNER JOIN {$wpdb->prefix}cfqm_events e ON e.quote_id=p.ID AND e.event_type='quote_viewed'
            WHERE p.post_type='cfqm_quote' AND p.post_status='publish' AND p.post_author=%d
            GROUP BY p.ID
            LIMIT 100
        ", $user_id));

        if (empty($rows)) return 0;

        $total_hours = 0;
        $count       = 0;
        foreach ($rows as $r) {
            $diff = strtotime($r->viewed_at) - strtotime($r->sent_at);
            if ($diff > 0) {
                $total_hours += $diff / HOUR_IN_SECONDS;
                $count++;
            }
        }

        return $count > 0 ? round($total_hours / $count, 1) : 0;
    }

    private function enqueue_assets(): void {
        wp_enqueue_script('cfqm-frontend', CFQM_URL . 'assets/js/frontend.js', ['jquery'], CFQM_VERSION, true);
        wp_localize_script('cfqm-frontend', 'cfqmData', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('cfqm_nonce'),
        ]);
        wp_enqueue_style('cfqm-frontend', CFQM_URL . 'assets/css/frontend.css', [], CFQM_VERSION);
    }
}
