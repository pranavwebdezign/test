<?php
namespace CFQM;
defined('ABSPATH') || exit;

/**
 * Amendment_Engine
 *
 * Manages the amendment workflow:
 *
 *   Homeowner submits amendment request (tick-boxes + message)
 *     → iteration_used increments
 *     → status → pending_amendment
 *     → tradesperson notified
 *
 *   Tradesperson responds:
 *     (a) Reissue   → opens quote builder pre-filled → saves new version → re-sends
 *     (b) Reject    → sends message to homeowner, status stays (or → declined)
 *     (c) Approve-as-is → status → approved/partial
 *
 *   When iteration_used == iteration_limit: request blocked; tradesperson can reset.
 *
 * Only customer-triggered amendments count toward iteration_used.
 */
class Amendment_Engine {
    use Singleton;

    public function boot(): void {
        add_action('wp_ajax_cfqm_submit_amendment',     [$this, 'ajax_submit_amendment']);
        add_action('wp_ajax_nopriv_cfqm_submit_amendment', [$this, 'ajax_submit_amendment']);
        add_action('wp_ajax_cfqm_respond_amendment',    [$this, 'ajax_respond_amendment']);
    }

    // ─────────────────────────────────────────────────────────────────────
    // AJAX: homeowner submits amendment
    // ─────────────────────────────────────────────────────────────────────

    public function ajax_submit_amendment(): void {
        if (!check_ajax_referer('cfqm_portal_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => 'Security check failed.'], 403);
        }

        $quote_id = $this->get_authorized_quote_id();
        if (!$quote_id) {
            wp_send_json_error(['message' => 'Session expired. Please re-open your quote link.']);
        }

        // Check iteration limit
        if (!Subscription_Tiers::instance()->can_amend($quote_id)) {
            wp_send_json_error(['message' => sprintf(
                __(
                    'Amendment limit reached (%d/%d). Please contact the tradesperson directly.',
                    'cfqm'
                ),
                get_post_meta($quote_id, '_cfqm_iteration_used', true),
                get_post_meta($quote_id, '_cfqm_iteration_limit', true)
            )]);
        }

        $areas_requested = array_map('intval', (array)($_POST['areas_requested'] ?? []));
        $message         = sanitize_textarea_field($_POST['amendment_message'] ?? '');

        $this->record_amendment_request($quote_id, $areas_requested, $message);

        wp_send_json_success(['message' => __('Amendment request submitted.', 'cfqm')]);
    }

    // ─────────────────────────────────────────────────────────────────────
    // AJAX: tradesperson responds to amendment
    // ─────────────────────────────────────────────────────────────────────

    public function ajax_respond_amendment(): void {
        if (!check_ajax_referer('cfqm_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => 'Security check failed.'], 403);
        }

        $quote_id = (int)($_POST['quote_id']  ?? 0);
        $action   = sanitize_key($_POST['amendment_action'] ?? '');
        $message  = sanitize_textarea_field($_POST['trade_message'] ?? '');

        if (!$this->current_user_owns_quote($quote_id)) {
            wp_send_json_error(['message' => 'Permission denied.']);
        }

        switch ($action) {
            case 'reissue':
                $ok = $this->reissue_quote($quote_id);
                $ok
                    ? wp_send_json_success(['message' => __('New version created. Edit and send when ready.', 'cfqm')])
                    : wp_send_json_error(['message' => 'Failed to create new version.']);
                break;

            case 'reject':
                $this->reject_amendment($quote_id, $message);
                wp_send_json_success(['message' => __('Amendment rejected and homeowner notified.', 'cfqm')]);
                break;

            case 'approve_as_is':
                $this->approve_as_is($quote_id);
                wp_send_json_success(['message' => __('Approved as-is.', 'cfqm')]);
                break;

            default:
                wp_send_json_error(['message' => 'Unknown action.']);
        }
    }

    // ─────────────────────────────────────────────────────────────────────
    // Core logic
    // ─────────────────────────────────────────────────────────────────────

    public function record_amendment_request(int $quote_id, array $area_ids, string $message): void {
        // Increment iteration counter
        $used = (int) get_post_meta($quote_id, '_cfqm_iteration_used', true);
        update_post_meta($quote_id, '_cfqm_iteration_used', $used + 1);

        // Archive in history
        $history   = json_decode((string) get_post_meta($quote_id, '_cfqm_amendment_history', true), true) ?: [];
        $history[] = [
            'at'      => current_time('mysql'),
            'areas'   => $area_ids,
            'message' => $message,
            'version' => get_post_meta($quote_id, '_cfqm_version', true),
        ];
        update_post_meta($quote_id, '_cfqm_amendment_history', wp_json_encode($history));

        // Status
        Quote_Builder::instance()->set_status($quote_id, 'pending_amendment');

        // Notify tradesperson
        Email_Notifications::instance()->send('amendment_requested', $quote_id);

        Audit_Trail::instance()->log('amendment_requested', $quote_id, [
            'iteration' => $used + 1,
            'areas'     => $area_ids,
            'message'   => $message,
        ]);
    }

    /** Create a new version of the quote ready for the tradesperson to edit and re-send. */
    public function reissue_quote(int $quote_id): bool {
        // Bump version
        $new_version = Versioning::instance()->bump_version($quote_id);
        if (!$new_version) return false;

        // Status back to draft so tradesperson can edit
        Quote_Builder::instance()->set_status($quote_id, 'draft');

        Audit_Trail::instance()->log('quote_reissued', $quote_id, ['new_version' => $new_version]);
        return true;
    }

    public function reject_amendment(int $quote_id, string $message): void {
        // Keep status as pending_amendment or revert to sent (your UX choice)
        Quote_Builder::instance()->set_status($quote_id, 'sent');

        Email_Notifications::instance()->send('amendment_rejected', $quote_id, 0, 0, [
            'trade_message' => $message,
        ]);

        Audit_Trail::instance()->log('amendment_rejected', $quote_id, ['message' => $message]);
    }

    public function approve_as_is(int $quote_id): void {
        Quote_Builder::instance()->set_status($quote_id, 'approved');
        Email_Notifications::instance()->send('quote_accepted', $quote_id);
        Audit_Trail::instance()->log('amendment_approved_as_is', $quote_id);
    }

    /** Tradesperson manually resets iteration counter (within plan max). */
    public function reset_iteration_count(int $quote_id, int $new_limit = 0): void {
        update_post_meta($quote_id, '_cfqm_iteration_used', 0);
        if ($new_limit > 0) {
            // Enforce plan ceiling
            $user_id   = (int) get_post_field('post_author', $quote_id);
            $plan      = Subscription_Tiers::instance()->get_user_plan($user_id);
            $plan_max  = Subscription_Tiers::instance()->get_limit($plan, 'max_iterations');
            $capped    = min($new_limit, $plan_max ?: $new_limit);
            update_post_meta($quote_id, '_cfqm_iteration_limit', $capped);
        }
    }

    // ─────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────

    private function get_authorized_quote_id(): int {
        // Check active portal session set by Token_Manager
        $qid = Token_Manager::instance()->get_portal_session_quote();
        if ($qid) return $qid;

        // Fallback: validate token submitted with the AJAX call directly
        $raw = sanitize_text_field($_POST['cfqm_token'] ?? '');
        return $raw ? Token_Manager::instance()->validate_portal_token($raw) : 0;
    }

    private function current_user_owns_quote(int $quote_id): bool {
        $post = get_post($quote_id);
        if (!$post || $post->post_type !== 'cfqm_quote') return false;
        if (current_user_can('manage_options')) return true;
        return (int)$post->post_author === get_current_user_id();
    }
}
