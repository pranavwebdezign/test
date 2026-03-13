<?php
namespace CFQM;
defined('ABSPATH') || exit;

/**
 * Homeowner_Query
 *
 * Guest query submission (no login required for MVP).
 * Shortcode: [cfqm_query_form]
 *
 * On submission:
 *   1. Validate + sanitize inputs.
 *   2. Create cfqm_hw_query post.
 *   3. Fire cfqm_query_submitted action → Geo_Matching picks it up.
 *   4. Send acknowledgement email to homeowner.
 */
class Homeowner_Query {
    use Singleton;

    public function boot(): void {
        add_shortcode('cfqm_query_form',              [$this, 'shortcode_query_form']);
        add_action('wp_ajax_nopriv_cfqm_submit_query', [$this, 'ajax_submit_query']);
        add_action('wp_ajax_cfqm_submit_query',        [$this, 'ajax_submit_query']);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Shortcode
    // ─────────────────────────────────────────────────────────────────────

    public function shortcode_query_form(array $atts): string {
        ob_start();
        $this->enqueue_assets();
        include CFQM_TPL . 'dashboard/query-form.php';
        return ob_get_clean();
    }

    // ─────────────────────────────────────────────────────────────────────
    // AJAX handler
    // ─────────────────────────────────────────────────────────────────────

    public function ajax_submit_query(): void {
        if (!check_ajax_referer('cfqm_query_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => 'Security check failed.'], 403);
        }

        // Validate
        $name     = sanitize_text_field($_POST['hw_name']     ?? '');
        $email    = sanitize_email($_POST['hw_email']         ?? '');
        $phone    = sanitize_text_field($_POST['hw_phone']    ?? '');
        $postcode = strtoupper(sanitize_text_field($_POST['hw_postcode'] ?? ''));
        $desc     = sanitize_textarea_field($_POST['hw_job_desc'] ?? '');
        $cat_id   = (int)($_POST['category_id']               ?? 0);

        $errors = [];
        if (!$name)           $errors[] = 'Name is required.';
        if (!is_email($email))$errors[] = 'A valid email is required.';
        if (!$postcode)       $errors[] = 'Postcode is required.';
        if (!$desc)           $errors[] = 'Job description is required.';
        if (!$cat_id)         $errors[] = 'Please select a trade category.';

        if ($errors) {
            wp_send_json_error(['message' => implode(' ', $errors)]);
        }

        // Rate-limiting: max 3 queries from same email per 24h
        $recent = get_transient('cfqm_query_rl_' . md5($email));
        if ($recent && (int)$recent >= 3) {
            wp_send_json_error(['message' => 'Too many submissions. Please wait 24 hours.']);
        }
        set_transient('cfqm_query_rl_' . md5($email), ((int)$recent) + 1, DAY_IN_SECONDS);

        // Create query post
        $query_id = wp_insert_post([
            'post_type'   => 'cfqm_hw_query',
            'post_status' => 'publish',
            'post_title'  => sprintf('%s — %s', $name, $postcode),
        ]);
        if (is_wp_error($query_id)) {
            wp_send_json_error(['message' => 'Failed to record query. Please try again.']);
        }

        // Set category
        if ($cat_id) {
            wp_set_post_terms($query_id, [$cat_id], 'cfqm_category');
        }

        // Meta
        $meta = [
            '_cfqm_hw_name'     => $name,
            '_cfqm_hw_email'    => $email,
            '_cfqm_hw_phone'    => $phone,
            '_cfqm_hw_postcode' => $postcode,
            '_cfqm_hw_job_desc' => $desc,
            '_cfqm_hw_status'   => 'open',
            '_cfqm_submitted_at'=> current_time('mysql'),
        ];
        foreach ($meta as $k => $v) update_post_meta($query_id, $k, $v);

        // Log audit event
        Database::instance()->log_event([
            'query_id'   => $query_id,
            'event_type' => Audit_Trail::EVT_QUERY_SUBMITTED,
            'event_data' => ['postcode' => $postcode, 'category_id' => $cat_id],
        ]);

        // Fire geo-matching (async-ish via the action)
        do_action('cfqm_query_submitted', $query_id);

        // Acknowledge to homeowner
        wp_mail(
            $email,
            sprintf('[%s] We received your enquiry', Settings::instance()->get('brand_name')),
            $this->acknowledgement_email($name, $desc),
            ['Content-Type: text/html; charset=UTF-8']
        );

        wp_send_json_success([
            'message' => 'Thank you! We\'ve sent your enquiry to local tradespeople. You\'ll receive quotes by email shortly.',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────

    private function acknowledgement_email(string $name, string $desc): string {
        $brand = esc_html(Settings::instance()->get('brand_name'));
        return "<p>Hi " . esc_html($name) . ",</p>
<p>Thank you for your enquiry! We've matched your job to qualified local tradespeople who will
be in touch with quotes shortly.</p>
<p><strong>Your job description:</strong><br>" . esc_html($desc) . "</p>
<p>— The {$brand} Team</p>";
    }

    private function enqueue_assets(): void {
        wp_enqueue_script(
            'cfqm-frontend',
            CFQM_URL . 'assets/js/frontend.js',
            ['jquery'],
            CFQM_VERSION,
            true
        );
        wp_localize_script('cfqm-frontend', 'cfqmData', [
            'ajaxUrl'    => admin_url('admin-ajax.php'),
            'queryNonce' => wp_create_nonce('cfqm_query_nonce'),
            'portalNonce'=> wp_create_nonce('cfqm_portal_nonce'),
        ]);
        wp_enqueue_style('cfqm-frontend', CFQM_URL . 'assets/css/frontend.css', [], CFQM_VERSION);
    }
}
