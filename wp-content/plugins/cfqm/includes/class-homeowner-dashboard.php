<?php
namespace CFQM;
defined('ABSPATH') || exit;

/**
 * Homeowner_Dashboard
 *
 * Shortcodes:
 *   [cfqm_homeowner_dashboard]  – main dashboard page
 *   [cfqm_magic_link_form]      – email input to request a 15-min login link
 *
 * Access model:
 *   - Homeowner enters their email → receives magic link (15 min expiry)
 *   - Clicking the link starts a 24-hr session cookie
 *   - Dashboard lists ALL quotes tied to that email, grouped by project_label
 *
 * Features:
 *   - Status filter tabs: All / Accepted / Pending / Closed
 *   - Search by trade / job description
 *   - Multi-quote comparison panel (2-3 quotes side-by-side)
 *   - Highlight: lowest price, fastest response
 *   - Project label editing
 *   - Direct links to individual quote portals
 */
class Homeowner_Dashboard {
    use Singleton;

    public function boot(): void {
        add_shortcode('cfqm_homeowner_dashboard', [$this, 'shortcode_dashboard']);
        add_shortcode('cfqm_magic_link_form',     [$this, 'shortcode_magic_link_form']);

        add_action('wp_ajax_nopriv_cfqm_send_magic_link',    [$this, 'ajax_send_magic_link']);
        add_action('wp_ajax_cfqm_send_magic_link',           [$this, 'ajax_send_magic_link']);
        add_action('wp_ajax_nopriv_cfqm_update_project_label',[$this, 'ajax_update_project_label']);
        add_action('wp_ajax_cfqm_update_project_label',       [$this, 'ajax_update_project_label']);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Shortcodes
    // ─────────────────────────────────────────────────────────────────────

    public function shortcode_magic_link_form(array $atts): string {
        ob_start();
        $this->enqueue_assets();
        include CFQM_TPL . 'dashboard/magic-link-form.php';
        return ob_get_clean();
    }

    public function shortcode_dashboard(array $atts): string {
        $email = Token_Manager::instance()->get_hw_session_email();

        ob_start();
        $this->enqueue_assets();

        if (!$email) {
            include CFQM_TPL . 'dashboard/magic-link-form.php';
        } else {
            $quotes   = $this->get_quotes_for_email($email);
            $grouped  = $this->group_by_project($quotes);
            $filter   = sanitize_text_field($_GET['status_filter'] ?? 'all');
            $search   = sanitize_text_field($_GET['search'] ?? '');
            include CFQM_TPL . 'dashboard/homeowner-dashboard.php';
        }

        return ob_get_clean();
    }

    // ─────────────────────────────────────────────────────────────────────
    // AJAX: send magic link
    // ─────────────────────────────────────────────────────────────────────

    public function ajax_send_magic_link(): void {
        if (!check_ajax_referer('cfqm_magic_link_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => 'Security check failed.'], 403);
        }

        $email = sanitize_email($_POST['email'] ?? '');
        if (!is_email($email)) {
            wp_send_json_error(['message' => 'Please enter a valid email address.']);
        }

        // Rate-limit: 1 magic link per 5 min per email
        $rl_key = 'cfqm_ml_rl_' . md5($email);
        if (get_transient($rl_key)) {
            wp_send_json_success(['message' => 'If we have quotes for that email, a link was sent.']); // Soft response
            return;
        }
        set_transient($rl_key, 1, 5 * MINUTE_IN_SECONDS);

        // Check there are quotes for this email
        $quotes = $this->get_quotes_for_email($email);
        // We always respond with the same success message to avoid email enumeration

        if (!empty($quotes)) {
            $raw_token = Token_Manager::instance()->issue_magic_link($email);
            if ($raw_token) {
                $magic_url = add_query_arg('cfqm_token', rawurlencode($raw_token),
                    Settings::instance()->hw_dashboard_url());

                Email_Notifications::instance()->send('magic_link', 0, 0, 0, [
                    'email'     => $email,
                    'magic_url' => $magic_url,
                ]);

                Audit_Trail::instance()->log_system(Audit_Trail::EVT_MAGIC_LINK_SENT, 0, [
                    'email' => $email,
                ]);
            }
        }

        wp_send_json_success([
            'message' => 'If we have quotes for that email address, you\'ll receive a login link shortly.',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────
    // AJAX: update project label
    // ─────────────────────────────────────────────────────────────────────

    public function ajax_update_project_label(): void {
        if (!check_ajax_referer('cfqm_magic_link_nonce', 'nonce', false)) {
            wp_send_json_error([], 403);
        }
        $email     = Token_Manager::instance()->get_hw_session_email();
        $quote_id  = (int)($_POST['quote_id'] ?? 0);
        $label     = sanitize_text_field($_POST['label'] ?? '');

        if (!$email || !$quote_id) {
            wp_send_json_error(['message' => 'Not authorised.']);
        }

        // Verify the quote belongs to this email
        $customer_email = (string) get_post_meta($quote_id, '_cfqm_customer_email', true);
        if (strtolower($customer_email) !== strtolower($email)) {
            wp_send_json_error(['message' => 'Not authorised.']);
        }

        update_post_meta($quote_id, '_cfqm_project_label', $label);
        wp_send_json_success();
    }

    // ─────────────────────────────────────────────────────────────────────
    // Data fetching
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Get all quotes for a homeowner email address, enriched with comparison data.
     */
    public function get_quotes_for_email(string $email): array {
        global $wpdb;
        $ids = $wpdb->get_col($wpdb->prepare("
            SELECT pm.post_id
            FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
            WHERE pm.meta_key   = '_cfqm_customer_email'
              AND pm.meta_value = %s
              AND p.post_type   = 'cfqm_quote'
              AND p.post_status = 'publish'
        ", $email));

        $quotes = [];
        foreach ($ids as $qid) {
            $data = Quote_Builder::instance()->get_full_quote_data((int)$qid);
            if (!$data) continue;

            // Attach comparison helpers
            $data['sent_timestamp'] = strtotime($data['sent_at'] ?? '') ?: 0;
            $data['trade_name']     = $this->get_trade_name_for_quote((int)$qid);
            $quotes[]               = $data;
        }

        // Sort by sent date desc
        usort($quotes, fn($a, $b) => $b['sent_timestamp'] <=> $a['sent_timestamp']);
        return $quotes;
    }

    /**
     * Group quotes by _cfqm_project_label (falls back to "General Enquiry").
     * @return array<string, array> key = project label
     */
    public function group_by_project(array $quotes): array {
        $groups = [];
        foreach ($quotes as $q) {
            $label = trim($q['project_label'] ?? '') ?: 'General Enquiry';
            $groups[$label][] = $q;
        }
        ksort($groups);
        return $groups;
    }

    /**
     * Filter quotes by status group.
     * 'active' = sent|viewed|pending_amendment
     * 'accepted' = approved|partial|deposit_paid
     * 'closed' = declined|expired
     */
    public function filter_quotes(array $quotes, string $filter): array {
        if ($filter === 'all') return $quotes;

        $map = [
            'active'   => ['sent','viewed','pending_amendment'],
            'accepted' => ['approved','partial','deposit_paid'],
            'closed'   => ['declined','expired'],
        ];
        $allowed = $map[$filter] ?? [];
        return array_values(array_filter($quotes, fn($q) => in_array($q['status'], $allowed, true)));
    }

    /**
     * Build comparison data for 2-3 quotes side-by-side.
     * Highlights cheapest grand_total and fastest response (lowest sent_timestamp).
     */
    public function build_comparison(array $quote_ids): array {
        $quotes = [];
        foreach (array_slice($quote_ids, 0, 3) as $qid) {
            $data = Quote_Builder::instance()->get_full_quote_data((int)$qid);
            if ($data) {
                $data['trade_name']     = $this->get_trade_name_for_quote((int)$qid);
                $data['sent_timestamp'] = strtotime($data['sent_at'] ?? '') ?: PHP_INT_MAX;
                $quotes[] = $data;
            }
        }

        if (count($quotes) < 2) return $quotes;

        $min_price   = min(array_column($quotes, 'grand_total'));
        $min_time    = min(array_column($quotes, 'sent_timestamp'));

        foreach ($quotes as &$q) {
            $q['is_cheapest']  = (float)$q['grand_total']   === (float)$min_price;
            $q['is_fastest']   = (int)$q['sent_timestamp']  === (int)$min_time;
        }

        return $quotes;
    }

    // ─────────────────────────────────────────────────────────────────────

    private function get_trade_name_for_quote(int $quote_id): string {
        $tid = (int) get_post_meta($quote_id, '_cfqm_trade_id', true);
        return $tid ? (string) get_the_title($tid) : 'Unknown Trade';
    }

    private function enqueue_assets(): void {
        wp_enqueue_script('cfqm-frontend', CFQM_URL . 'assets/js/frontend.js', ['jquery'], CFQM_VERSION, true);
        wp_localize_script('cfqm-frontend', 'cfqmData', [
            'ajaxUrl'       => admin_url('admin-ajax.php'),
            'magicLinkNonce'=> wp_create_nonce('cfqm_magic_link_nonce'),
        ]);
        wp_enqueue_style('cfqm-frontend', CFQM_URL . 'assets/css/frontend.css', [], CFQM_VERSION);
    }
}
