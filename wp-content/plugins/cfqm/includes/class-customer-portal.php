<?php
namespace CFQM;
defined('ABSPATH') || exit;

/**
 * Customer_Portal
 *
 * Shortcode: [cfqm_quote_portal]
 *
 * Token-based, no login required. Customer can:
 *   - View quote summary and all areas with scope + price
 *   - Select / deselect optional areas (Included areas locked)
 *   - Live-recalculate selected total
 *   - E-sign (typed name + date + checkbox)
 *   - Accept (full or partial) or Decline
 *   - Request amendment (tick-box + message)
 *   - Download PDF
 *   - View event timeline
 */
class Customer_Portal {
    use Singleton;

    public function boot(): void {
        add_shortcode('cfqm_quote_portal',              [$this, 'shortcode_portal']);
        add_action('wp_ajax_nopriv_cfqm_portal_accept', [$this, 'ajax_accept']);
        add_action('wp_ajax_nopriv_cfqm_portal_decline',[$this, 'ajax_decline']);
        add_action('wp_ajax_cfqm_portal_accept',        [$this, 'ajax_accept']);
        add_action('wp_ajax_cfqm_portal_decline',       [$this, 'ajax_decline']);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Shortcode
    // ─────────────────────────────────────────────────────────────────────

    public function shortcode_portal(array $atts): string {
        // Check for token in URL (new visit)
        $raw = isset($_GET['cfqm_token'])
            ? sanitize_text_field(wp_unslash($_GET['cfqm_token']))
            : '';

        if ($raw) {
            $quote_id = Token_Manager::instance()->validate_portal_token($raw);
            if ($quote_id) {
                Token_Manager::instance()->start_portal_session($quote_id);
                // Mark as viewed (once)
                $status = (string) get_post_meta($quote_id, '_cfqm_status', true);
                if ($status === 'sent') {
                    Quote_Builder::instance()->set_status($quote_id, 'viewed');
                    Audit_Trail::instance()->log(Audit_Trail::EVT_QUOTE_VIEWED, $quote_id);
                }
            }
        }

        // Get quote from session
        $quote_id = Token_Manager::instance()->get_portal_session_quote();

        ob_start();
        $this->enqueue_assets();

        if (!$quote_id) {
            include CFQM_TPL . 'portal/invalid-token.php';
        } else {
            $data     = Quote_Builder::instance()->get_full_quote_data($quote_id);
            $timeline = Audit_Trail::instance()->get_quote_timeline($quote_id);
            $status   = $data['status'];
            include CFQM_TPL . 'portal/quote-portal.php';
        }

        return ob_get_clean();
    }

    // ─────────────────────────────────────────────────────────────────────
    // AJAX: Accept
    // ─────────────────────────────────────────────────────────────────────

    public function ajax_accept(): void {
        if (!check_ajax_referer('cfqm_portal_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => 'Security check failed.'], 403);
        }

        $quote_id = Token_Manager::instance()->get_portal_session_quote();
        if (!$quote_id) {
            wp_send_json_error(['message' => 'Session expired. Please re-open your quote link.']);
        }

        $data = Quote_Builder::instance()->get_full_quote_data($quote_id);

        // Must not already be in a terminal state
        if (in_array($data['status'], ['approved','partial','deposit_paid','declined'], true)) {
            wp_send_json_error(['message' => 'This quote has already been actioned.']);
        }

        $selected_ids = array_map('intval', (array)($_POST['selected_areas'] ?? []));
        $signature    = sanitize_text_field($_POST['signature'] ?? '');
        $sig_date     = sanitize_text_field($_POST['sig_date']  ?? '');

        if (!$signature) {
            wp_send_json_error(['message' => 'Please enter your name to e-sign the quote.']);
        }

        // Determine all included area IDs (homeowner cannot deselect these)
        $all_areas     = Quote_Builder::instance()->get_quote_areas($quote_id);
        $included_ids  = [];
        $optional_ids  = [];
        foreach ($all_areas as $a) {
            $type = (string) get_post_meta($a->ID, '_cfqm_area_type', true);
            if ($type === 'optional') $optional_ids[] = $a->ID;
            else                      $included_ids[]  = $a->ID;
        }

        // Merge: all included + whichever optional areas were ticked
        $valid_optional = array_intersect($selected_ids, $optional_ids);
        $final_selected = array_merge($included_ids, $valid_optional);

        // Recalculate totals for selected areas
        $totals = Quote_Builder::instance()->calculate_totals($quote_id, $final_selected);

        // Store signature + selection
        update_post_meta($quote_id, '_cfqm_signature',      $signature);
        update_post_meta($quote_id, '_cfqm_signed_at',      current_time('mysql'));
        update_post_meta($quote_id, '_cfqm_selected_areas', wp_json_encode($final_selected));

        // Recalculate and store selected totals
        update_post_meta($quote_id, '_cfqm_grand_total',    $totals['grand_total']);
        update_post_meta($quote_id, '_cfqm_deposit_amount', $totals['deposit_amount']);
        update_post_meta($quote_id, '_cfqm_vat_amount',     $totals['vat_amount']);

        // Status: partial if any optional skipped, else approved
        $all_area_ids = array_merge($included_ids, $optional_ids);
        $new_status   = count($final_selected) === count($all_area_ids) ? 'approved' : 'partial';
        Quote_Builder::instance()->set_status($quote_id, $new_status);

        Audit_Trail::instance()->log(
            $new_status === 'approved' ? Audit_Trail::EVT_QUOTE_APPROVED : Audit_Trail::EVT_QUOTE_PARTIAL,
            $quote_id,
            ['selected_areas' => $final_selected, 'grand_total' => $totals['grand_total']]
        );
        Audit_Trail::instance()->log(Audit_Trail::EVT_SIGNATURE_CAPTURED, $quote_id, [
            'signature' => $signature,
            'date'      => $sig_date,
        ]);

        // Trigger Stripe payment link creation (if deposit configured)
        $stripe_link = (string) get_post_meta($quote_id, '_cfqm_stripe_link', true);

        wp_send_json_success([
            'status'       => $new_status,
            'grand_total'  => number_format($totals['grand_total'], 2),
            'deposit'      => number_format($totals['deposit_amount'], 2),
            'stripe_link'  => $stripe_link,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────
    // AJAX: Decline
    // ─────────────────────────────────────────────────────────────────────

    public function ajax_decline(): void {
        if (!check_ajax_referer('cfqm_portal_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => 'Security check failed.'], 403);
        }

        $quote_id = Token_Manager::instance()->get_portal_session_quote();
        if (!$quote_id) {
            wp_send_json_error(['message' => 'Session expired.']);
        }

        Quote_Builder::instance()->set_status($quote_id, 'declined');
        Audit_Trail::instance()->log(Audit_Trail::EVT_QUOTE_DECLINED, $quote_id);

        // Check if ALL quotes for this query are now declined → reopen
        $query_id = (int) get_post_meta($quote_id, '_cfqm_query_id', true);
        if ($query_id) {
            $this->maybe_reopen_query($query_id);
        }

        wp_send_json_success(['message' => 'Quote declined.']);
    }

    // ─────────────────────────────────────────────────────────────────────

    private function maybe_reopen_query(int $query_id): void {
        $responses = json_decode((string) get_post_meta($query_id, '_cfqm_responses', true), true) ?: [];
        foreach ($responses as $trade_id => $qid) {
            $s = (string) get_post_meta((int)$qid, '_cfqm_status', true);
            if (!in_array($s, ['declined', 'expired'], true)) {
                return;  // At least one quote is still active
            }
        }
        // All declined — reopen
        Geo_Matching::instance()->reopen_on_all_declined($query_id);
    }

    private function enqueue_assets(): void {
        wp_enqueue_script('cfqm-frontend', CFQM_URL . 'assets/js/frontend.js', ['jquery'], CFQM_VERSION, true);
        wp_localize_script('cfqm-frontend', 'cfqmData', [
            'ajaxUrl'     => admin_url('admin-ajax.php'),
            'portalNonce' => wp_create_nonce('cfqm_portal_nonce'),
        ]);
        wp_enqueue_style('cfqm-frontend', CFQM_URL . 'assets/css/frontend.css', [], CFQM_VERSION);
    }
}
