<?php
namespace CFQM;
defined('ABSPATH') || exit;

/**
 * Quote_Builder
 *
 * Handles all quote CRUD operations, status transitions, area management,
 * totals calculation, and AJAX endpoints used by the tradesperson Toolbox.
 *
 * Status flow:
 *   draft → sent → viewed → approved | partial | declined
 *   sent/viewed → pending_amendment → (reissue = new version → sent)
 *   approved/partial → deposit_paid
 *   sent/viewed → expired
 */
class Quote_Builder {
    use Singleton;

    const STATUSES = [
        'draft', 'sent', 'viewed', 'approved', 'partial',
        'declined', 'deposit_paid', 'expired', 'pending_amendment',
    ];

    public function boot(): void {
        // AJAX (logged-in users = tradespeople)
        add_action('wp_ajax_cfqm_save_quote',      [$this, 'ajax_save_quote']);
        add_action('wp_ajax_cfqm_send_quote',      [$this, 'ajax_send_quote']);
        add_action('wp_ajax_cfqm_delete_quote',    [$this, 'ajax_delete_quote']);
        add_action('wp_ajax_cfqm_save_area',       [$this, 'ajax_save_area']);
        add_action('wp_ajax_cfqm_delete_area',     [$this, 'ajax_delete_area']);
        add_action('wp_ajax_cfqm_get_quote_data',  [$this, 'ajax_get_quote_data']);
    }

    // ─────────────────────────────────────────────────────────────────────
    // AJAX handlers
    // ─────────────────────────────────────────────────────────────────────

    public function ajax_save_quote(): void {
        $this->verify_nonce('cfqm_nonce');

        $data = [
            'customer_name'    => sanitize_text_field($_POST['customer_name']    ?? ''),
            'customer_email'   => sanitize_email($_POST['customer_email']        ?? ''),
            'customer_phone'   => sanitize_text_field($_POST['customer_phone']   ?? ''),
            'query_id'         => (int)($_POST['query_id']                       ?? 0),
            'vat_enabled'      => !empty($_POST['vat_enabled']),
            'vat_rate'         => (float)($_POST['vat_rate']                     ?? 20),
            'deposit_type'     => sanitize_text_field($_POST['deposit_type']     ?? 'none'),
            'deposit_percent'  => (float)($_POST['deposit_percent']              ?? 0),
            'iteration_limit'  => (int)($_POST['iteration_limit']                ?? 5),
            'project_label'    => sanitize_text_field($_POST['project_label']    ?? ''),
            'category_id'      => (int)($_POST['category_id']                   ?? 0),
        ];

        $quote_id = (int)($_POST['quote_id'] ?? 0);

        if ($quote_id) {
            $this->update_quote($quote_id, $data);
        } else {
            if (!Subscription_Tiers::instance()->can_create_quote()) {
                wp_send_json_error(['message' => __('Monthly quote limit reached. Please upgrade your plan.', 'cfqm')]);
            }
            $quote_id = $this->create_quote($data);
        }

        if (!$quote_id) {
            wp_send_json_error(['message' => __('Failed to save quote.', 'cfqm')]);
        }

        wp_send_json_success([
            'quote_id' => $quote_id,
            'totals'   => $this->calculate_totals($quote_id),
        ]);
    }

    public function ajax_send_quote(): void {
        $this->verify_nonce('cfqm_nonce');
        $quote_id = (int)($_POST['quote_id'] ?? 0);
        if (!$this->current_user_owns_quote($quote_id)) {
            wp_send_json_error(['message' => __('Permission denied.', 'cfqm')]);
        }
        $result = $this->send_quote($quote_id);
        $result ? wp_send_json_success() : wp_send_json_error(['message' => __('Failed to send quote.', 'cfqm')]);
    }

    public function ajax_delete_quote(): void {
        $this->verify_nonce('cfqm_nonce');
        $quote_id = (int)($_POST['quote_id'] ?? 0);
        if (!$this->current_user_owns_quote($quote_id)) {
            wp_send_json_error(['message' => __('Permission denied.', 'cfqm')]);
        }
        wp_trash_post($quote_id);
        wp_send_json_success();
    }

    public function ajax_save_area(): void {
        $this->verify_nonce('cfqm_nonce');
        $quote_id = (int)($_POST['quote_id'] ?? 0);
        if (!$this->current_user_owns_quote($quote_id)) {
            wp_send_json_error(['message' => __('Permission denied.', 'cfqm')]);
        }

        $area_data = [
            'name'         => sanitize_text_field($_POST['area_name']       ?? ''),
            'scope'        => wp_kses_post($_POST['area_scope']             ?? ''),
            'area_type'    => sanitize_key($_POST['area_type']              ?? 'included'),
            'price'        => (float)($_POST['area_price']                  ?? 0),
            'materials'    => sanitize_textarea_field($_POST['materials']   ?? ''),
            'labour_hours' => (float)($_POST['labour_hours']                ?? 0),
            'validity_date'=> sanitize_text_field($_POST['validity_date']   ?? ''),
            'sort_order'   => (int)($_POST['sort_order']                    ?? 0),
            'photo_ids'    => array_map('intval', (array)($_POST['photo_ids'] ?? [])),
        ];

        $area_id = (int)($_POST['area_id'] ?? 0);
        if ($area_id) {
            $this->update_area($area_id, $area_data);
        } else {
            $area_id = $this->create_area($quote_id, $area_data);
        }

        $this->recalculate_totals($quote_id);

        wp_send_json_success([
            'area_id' => $area_id,
            'totals'  => $this->calculate_totals($quote_id),
        ]);
    }

    public function ajax_delete_area(): void {
        $this->verify_nonce('cfqm_nonce');
        $area_id  = (int)($_POST['area_id']  ?? 0);
        $quote_id = (int) get_post_meta($area_id, '_cfqm_quote_id', true);
        if (!$this->current_user_owns_quote($quote_id)) {
            wp_send_json_error(['message' => __('Permission denied.', 'cfqm')]);
        }
        wp_delete_post($area_id, true);
        $this->recalculate_totals($quote_id);
        wp_send_json_success(['totals' => $this->calculate_totals($quote_id)]);
    }

    public function ajax_get_quote_data(): void {
        $this->verify_nonce('cfqm_nonce');
        $quote_id = (int)($_POST['quote_id'] ?? 0);
        if (!$this->current_user_owns_quote($quote_id)) {
            wp_send_json_error(['message' => __('Permission denied.', 'cfqm')]);
        }
        wp_send_json_success($this->get_full_quote_data($quote_id));
    }

    // ─────────────────────────────────────────────────────────────────────
    // Quote CRUD
    // ─────────────────────────────────────────────────────────────────────

    public function create_quote(array $data): int {
        $user_id  = get_current_user_id();
        $trade_id = $this->get_trade_profile_id($user_id);

        // Create / find customer
        $customer_id = $this->get_or_create_customer(
            $data['customer_name'], $data['customer_email'], $data['customer_phone']
        );

        $quote_id = wp_insert_post([
            'post_type'   => 'cfqm_quote',
            'post_status' => 'publish',
            'post_author' => $user_id,
            'post_title'  => sprintf('Quote: %s — %s',
                $data['customer_name'], current_time('d M Y')),
        ], true);

        if (is_wp_error($quote_id)) return 0;

        // Category
        if ($data['category_id']) {
            wp_set_post_terms($quote_id, [(int)$data['category_id']], 'cfqm_category');
        }

        // Meta
        $meta = [
            '_cfqm_trade_id'         => $trade_id,
            '_cfqm_customer_id'      => $customer_id,
            '_cfqm_customer_name'    => $data['customer_name'],
            '_cfqm_customer_email'   => $data['customer_email'],
            '_cfqm_customer_phone'   => $data['customer_phone'],
            '_cfqm_query_id'         => $data['query_id'],
            '_cfqm_status'           => 'draft',
            '_cfqm_version'          => '1.0',
            '_cfqm_iteration_used'   => 0,
            '_cfqm_iteration_limit'  => $data['iteration_limit'],
            '_cfqm_vat_enabled'      => $data['vat_enabled'] ? 1 : 0,
            '_cfqm_vat_rate'         => $data['vat_rate'],
            '_cfqm_deposit_type'     => $data['deposit_type'],
            '_cfqm_deposit_percent'  => $data['deposit_percent'],
            '_cfqm_project_label'    => $data['project_label'],
            '_cfqm_subtotal'         => 0,
            '_cfqm_vat_amount'       => 0,
            '_cfqm_grand_total'      => 0,
            '_cfqm_deposit_amount'   => 0,
        ];
        foreach ($meta as $k => $v) update_post_meta($quote_id, $k, $v);

        // Honour plan tier for iteration limit
        Subscription_Tiers::instance()->set_default_iteration_limit($quote_id, $user_id);

        Audit_Trail::instance()->log('quote_created', $quote_id);
        return $quote_id;
    }

    public function update_quote(int $quote_id, array $data): void {
        if (!$this->current_user_owns_quote($quote_id)) return;

        $map = [
            '_cfqm_customer_name'   => 'customer_name',
            '_cfqm_customer_email'  => 'customer_email',
            '_cfqm_customer_phone'  => 'customer_phone',
            '_cfqm_vat_enabled'     => 'vat_enabled',
            '_cfqm_vat_rate'        => 'vat_rate',
            '_cfqm_deposit_type'    => 'deposit_type',
            '_cfqm_deposit_percent' => 'deposit_percent',
            '_cfqm_iteration_limit' => 'iteration_limit',
            '_cfqm_project_label'   => 'project_label',
        ];

        foreach ($map as $meta_key => $data_key) {
            if (isset($data[$data_key])) {
                update_post_meta($quote_id, $meta_key, $data[$data_key]);
            }
        }

        $this->recalculate_totals($quote_id);
    }

    /**
     * Send the quote: generate PDF, issue portal token, send email, update status.
     */
    public function send_quote(int $quote_id): bool {
        $status = (string) get_post_meta($quote_id, '_cfqm_status', true);
        if (!in_array($status, ['draft', 'pending_amendment'], true)) return false;

        // 1. Generate PDF
        PDF_Generator::instance()->generate($quote_id);

        // 2. Issue / refresh portal token
        $token = Token_Manager::instance()->issue_portal_token($quote_id);
        if (!$token) return false;

        // 3. Update status + timestamps
        $this->set_status($quote_id, 'sent');
        update_post_meta($quote_id, '_cfqm_sent_at', current_time('mysql'));

        // 4. Email customer
        Email_Notifications::instance()->send('quote_sent', $quote_id);

        // 5. Record geo-match response if originating from a query
        $query_id = (int) get_post_meta($quote_id, '_cfqm_query_id', true);
        if ($query_id) {
            $trade_id = (int) get_post_meta($quote_id, '_cfqm_trade_id', true);
            Geo_Matching::instance()->record_response($query_id, $trade_id, $quote_id);
        }

        Audit_Trail::instance()->log('quote_sent', $quote_id);
        return true;
    }

    // ─────────────────────────────────────────────────────────────────────
    // Area CRUD
    // ─────────────────────────────────────────────────────────────────────

    public function create_area(int $quote_id, array $data): int {
        $area_id = wp_insert_post([
            'post_type'   => 'cfqm_quote_area',
            'post_status' => 'publish',
            'post_title'  => sanitize_text_field($data['name']),
        ], true);

        if (is_wp_error($area_id)) return 0;

        update_post_meta($area_id, '_cfqm_quote_id',     $quote_id);
        $this->write_area_meta($area_id, $data);
        return $area_id;
    }

    public function update_area(int $area_id, array $data): void {
        wp_update_post(['ID' => $area_id, 'post_title' => sanitize_text_field($data['name'])]);
        $this->write_area_meta($area_id, $data);
    }

    private function write_area_meta(int $area_id, array $data): void {
        $meta = [
            '_cfqm_area_type'     => in_array($data['area_type'] ?? '', ['included','optional']) ? $data['area_type'] : 'included',
            '_cfqm_scope'         => wp_kses_post($data['scope'] ?? ''),
            '_cfqm_price'         => (float)($data['price'] ?? 0),
            '_cfqm_materials'     => sanitize_textarea_field($data['materials'] ?? ''),
            '_cfqm_labour_hours'  => (float)($data['labour_hours'] ?? 0),
            '_cfqm_validity_date' => sanitize_text_field($data['validity_date'] ?? ''),
            '_cfqm_sort_order'    => (int)($data['sort_order'] ?? 0),
            '_cfqm_photo_ids'     => wp_json_encode(array_map('intval', $data['photo_ids'] ?? [])),
        ];
        foreach ($meta as $k => $v) update_post_meta($area_id, $k, $v);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Totals calculation
    // ─────────────────────────────────────────────────────────────────────

    public function recalculate_totals(int $quote_id): void {
        $totals = $this->calculate_totals($quote_id);
        update_post_meta($quote_id, '_cfqm_subtotal',      $totals['subtotal']);
        update_post_meta($quote_id, '_cfqm_vat_amount',    $totals['vat_amount']);
        update_post_meta($quote_id, '_cfqm_grand_total',   $totals['grand_total']);
        update_post_meta($quote_id, '_cfqm_deposit_amount',$totals['deposit_amount']);
    }

    /**
     * @return array{subtotal: float, vat_amount: float, grand_total: float,
     *               deposit_amount: float, selected_subtotal: float}
     */
    public function calculate_totals(int $quote_id, array $selected_area_ids = []): array {
        $areas     = $this->get_quote_areas($quote_id);
        $subtotal  = 0.0;
        $sel_total = 0.0;

        foreach ($areas as $area) {
            $price    = (float) get_post_meta($area->ID, '_cfqm_price', true);
            $subtotal += $price;
            if (empty($selected_area_ids) || in_array($area->ID, $selected_area_ids, true)) {
                $sel_total += $price;
            }
        }

        $base        = $selected_area_ids ? $sel_total : $subtotal;
        $vat_enabled = (bool) get_post_meta($quote_id, '_cfqm_vat_enabled', true);
        $vat_rate    = (float)(get_post_meta($quote_id, '_cfqm_vat_rate', true) ?: 20);
        $vat_amount  = $vat_enabled ? round($base * $vat_rate / 100, 2) : 0.0;
        $grand_total = round($base + $vat_amount, 2);

        $dep_type    = (string) get_post_meta($quote_id, '_cfqm_deposit_type', true);
        $dep_pct     = (float)  get_post_meta($quote_id, '_cfqm_deposit_percent', true);
        $dep_amount  = ($dep_type === 'percent' && $dep_pct > 0)
            ? round($grand_total * $dep_pct / 100, 2)
            : 0.0;

        return [
            'subtotal'         => round($subtotal, 2),
            'selected_subtotal'=> round($sel_total, 2),
            'vat_amount'       => $vat_amount,
            'grand_total'      => $grand_total,
            'deposit_amount'   => $dep_amount,
        ];
    }

    // ─────────────────────────────────────────────────────────────────────
    // Data retrieval
    // ─────────────────────────────────────────────────────────────────────

    /** Get all area posts for a quote, ordered by sort_order. */
    public function get_quote_areas(int $quote_id): array {
        return get_posts([
            'post_type'      => 'cfqm_quote_area',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'meta_query'     => [['key' => '_cfqm_quote_id', 'value' => $quote_id, 'type' => 'NUMERIC']],
            'meta_key'       => '_cfqm_sort_order',
            'orderby'        => 'meta_value_num',
            'order'          => 'ASC',
        ]);
    }

    /** Full quote data including all areas with their meta. */
    public function get_full_quote_data(int $quote_id): array {
        $post = get_post($quote_id);
        if (!$post || $post->post_type !== 'cfqm_quote') return [];

        $meta  = get_post_meta($quote_id);
        $areas = $this->get_quote_areas($quote_id);

        $area_data = [];
        foreach ($areas as $area) {
            $am = get_post_meta($area->ID);
            $area_data[] = [
                'id'           => $area->ID,
                'name'         => $area->post_title,
                'area_type'    => $am['_cfqm_area_type'][0]     ?? 'included',
                'scope'        => $am['_cfqm_scope'][0]         ?? '',
                'price'        => (float)($am['_cfqm_price'][0] ?? 0),
                'materials'    => $am['_cfqm_materials'][0]     ?? '',
                'labour_hours' => (float)($am['_cfqm_labour_hours'][0] ?? 0),
                'validity_date'=> $am['_cfqm_validity_date'][0] ?? '',
                'photo_ids'    => json_decode($am['_cfqm_photo_ids'][0] ?? '[]', true),
            ];
        }

        return [
            'id'              => $quote_id,
            'title'           => $post->post_title,
            'status'          => $meta['_cfqm_status'][0]          ?? 'draft',
            'version'         => $meta['_cfqm_version'][0]         ?? '1.0',
            'iteration_used'  => (int)($meta['_cfqm_iteration_used'][0]  ?? 0),
            'iteration_limit' => (int)($meta['_cfqm_iteration_limit'][0] ?? 5),
            'customer_name'   => $meta['_cfqm_customer_name'][0]   ?? '',
            'customer_email'  => $meta['_cfqm_customer_email'][0]  ?? '',
            'customer_phone'  => $meta['_cfqm_customer_phone'][0]  ?? '',
            'vat_enabled'     => (bool)($meta['_cfqm_vat_enabled'][0] ?? false),
            'vat_rate'        => (float)($meta['_cfqm_vat_rate'][0]    ?? 20),
            'deposit_type'    => $meta['_cfqm_deposit_type'][0]    ?? 'none',
            'deposit_percent' => (float)($meta['_cfqm_deposit_percent'][0] ?? 0),
            'subtotal'        => (float)($meta['_cfqm_subtotal'][0]    ?? 0),
            'vat_amount'      => (float)($meta['_cfqm_vat_amount'][0]  ?? 0),
            'grand_total'     => (float)($meta['_cfqm_grand_total'][0] ?? 0),
            'deposit_amount'  => (float)($meta['_cfqm_deposit_amount'][0] ?? 0),
            'project_label'   => $meta['_cfqm_project_label'][0]   ?? '',
            'portal_token'    => $meta['_cfqm_portal_token'][0]    ?? '',
            'stripe_link'     => $meta['_cfqm_stripe_link'][0]     ?? '',
            'sent_at'         => $meta['_cfqm_sent_at'][0]         ?? '',
            'areas'           => $area_data,
        ];
    }

    // ─────────────────────────────────────────────────────────────────────
    // Status machine
    // ─────────────────────────────────────────────────────────────────────

    public function set_status(int $quote_id, string $status): void {
        if (!in_array($status, self::STATUSES, true)) return;
        $old = (string) get_post_meta($quote_id, '_cfqm_status', true);
        if ($old === $status) return;
        update_post_meta($quote_id, '_cfqm_status', $status);
        do_action('cfqm_quote_status_changed', $quote_id, $old, $status);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────

    public function get_or_create_customer(string $name, string $email, string $phone = ''): int {
        global $wpdb;
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT pm.post_id FROM {$wpdb->postmeta} pm
             INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
             WHERE pm.meta_key='_cfqm_customer_email' AND pm.meta_value=%s
               AND p.post_type='cfqm_customer' AND p.post_status='publish'
             LIMIT 1", $email
        ));

        if ($existing) return (int)$existing;

        $id = wp_insert_post([
            'post_type'   => 'cfqm_customer',
            'post_status' => 'publish',
            'post_title'  => $name ?: $email,
        ]);
        if (is_wp_error($id)) return 0;

        update_post_meta($id, '_cfqm_customer_name',  $name);
        update_post_meta($id, '_cfqm_customer_email', $email);
        update_post_meta($id, '_cfqm_customer_phone', $phone);
        return $id;
    }

    public function get_trade_profile_id(int $user_id): int {
        global $wpdb;
        $id = $wpdb->get_var($wpdb->prepare(
            "SELECT pm.post_id FROM {$wpdb->postmeta} pm
             INNER JOIN {$wpdb->posts} p ON p.ID=pm.post_id
             WHERE pm.meta_key='_cfqm_user_id' AND pm.meta_value=%d
               AND p.post_type='cfqm_trade_profile' AND p.post_status='publish'
             LIMIT 1", $user_id
        ));
        return (int)$id;
    }

    private function current_user_owns_quote(int $quote_id): bool {
        $post = get_post($quote_id);
        if (!$post || $post->post_type !== 'cfqm_quote') return false;
        if (current_user_can('manage_options')) return true;
        return (int)$post->post_author === get_current_user_id();
    }

    private function verify_nonce(string $action): void {
        if (!check_ajax_referer($action, 'nonce', false)) {
            wp_send_json_error(['message' => 'Security check failed.'], 403);
        }
    }
}
