<?php
namespace CFQM;
defined('ABSPATH') || exit;

/**
 * Stripe
 *
 * Handles deposit payment via Stripe Payment Links API (no Composer SDK needed).
 * REST endpoint for webhooks: POST /wp-json/cfqm/v1/stripe-webhook
 *
 * Flow:
 *   1. On quote acceptance → create_payment_link() → store URL → surface in portal.
 *   2. Customer pays → Stripe fires checkout.session.completed / payment_intent.succeeded.
 *   3. Webhook verifies signature → updates status to deposit_paid → logs event.
 */
class Stripe {
    use Singleton;

    const REST_NAMESPACE = 'cfqm/v1';
    const REST_WEBHOOK   = '/stripe-webhook';

    public function boot(): void {
        add_action('rest_api_init',                  [$this, 'register_webhook_endpoint']);
        add_action('cfqm_quote_status_changed',      [$this, 'on_quote_status_changed'], 10, 3);
        add_action('wp_ajax_cfqm_get_payment_link',  [$this, 'ajax_get_payment_link']);
    }

    // ─────────────────────────────────────────────────────────────────────
    // REST webhook endpoint
    // ─────────────────────────────────────────────────────────────────────

    public function register_webhook_endpoint(): void {
        register_rest_route(self::REST_NAMESPACE, self::REST_WEBHOOK, [
            'methods'             => 'POST',
            'callback'            => [$this, 'handle_webhook'],
            'permission_callback' => '__return_true',
        ]);
    }

    public function handle_webhook(\WP_REST_Request $request): \WP_REST_Response {
        $payload   = $request->get_body();
        $sig       = $request->get_header('stripe-signature');
        $secret    = (string) Settings::instance()->get('stripe_webhook_secret', '');

        // Verify signature
        if ($secret && !$this->verify_signature($payload, $sig, $secret)) {
            return new \WP_REST_Response(['error' => 'Invalid signature'], 400);
        }

        $event = json_decode($payload, true);
        if (empty($event['type'])) {
            return new \WP_REST_Response(['error' => 'No event type'], 400);
        }

        // Log raw webhook
        Database::instance()->log_event([
            'event_type' => Audit_Trail::EVT_STRIPE_WEBHOOK,
            'event_data' => ['stripe_event_type' => $event['type']],
        ]);

        // Handle relevant events
        switch ($event['type']) {
            case 'checkout.session.completed':
                $this->process_checkout_session($event['data']['object'] ?? []);
                break;
            case 'payment_intent.succeeded':
                $this->process_payment_intent($event['data']['object'] ?? []);
                break;
        }

        return new \WP_REST_Response(['received' => true]);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Auto-create payment link on acceptance
    // ─────────────────────────────────────────────────────────────────────

    public function on_quote_status_changed(int $quote_id, string $old, string $new): void {
        if (!in_array($new, ['approved', 'partial'], true)) return;

        $deposit = (float) get_post_meta($quote_id, '_cfqm_deposit_amount', true);
        if ($deposit <= 0) return;

        // Only create if not already created
        if (get_post_meta($quote_id, '_cfqm_stripe_link', true)) return;

        $this->create_payment_link($quote_id, $deposit);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Payment link creation
    // ─────────────────────────────────────────────────────────────────────

    public function create_payment_link(int $quote_id, float $amount): string {
        $sk = Settings::instance()->stripe_sk();
        if (!$sk) return '';

        // First create a price object
        $price_response = $this->api_request('POST', '/v1/prices', $sk, [
            'unit_amount' => (int) round($amount * 100),  // pence
            'currency'    => 'gbp',
            'product_data' => [
                'name' => sprintf('Deposit — %s', get_the_title($quote_id)),
            ],
        ]);

        if (empty($price_response['id'])) return '';

        // Create payment link
        $data = $this->api_request('POST', '/v1/payment_links', $sk, [
            'line_items' => [['price' => $price_response['id'], 'quantity' => 1]],
            'metadata'   => [
                'quote_id'    => (string) $quote_id,
                'cfqm_source' => 'deposit',
            ],
            'after_completion' => [
                'type'   => 'redirect',
                'redirect' => ['url' => Settings::instance()->portal_url(
                    (string) get_post_meta($quote_id, '_cfqm_portal_token', true)
                )],
            ],
        ]);

        if (empty($data['url'])) return '';

        update_post_meta($quote_id, '_cfqm_stripe_link', esc_url_raw($data['url']));
        update_post_meta($quote_id, '_cfqm_stripe_link_id', $data['id'] ?? '');

        Audit_Trail::instance()->log_system(Audit_Trail::EVT_PAYMENT_LINK_CREATED, $quote_id, [
            'amount'   => $amount,
            'link_id'  => $data['id'] ?? '',
        ]);

        return $data['url'];
    }

    // ─────────────────────────────────────────────────────────────────────
    // Webhook event processing
    // ─────────────────────────────────────────────────────────────────────

    private function process_checkout_session(array $session): void {
        $metadata = $session['metadata'] ?? [];
        $quote_id = isset($metadata['quote_id']) ? (int) $metadata['quote_id'] : 0;
        if (!$quote_id) return;

        $intent_id = $session['payment_intent'] ?? '';
        $this->mark_deposit_paid($quote_id, $intent_id, (int)($session['amount_total'] ?? 0));
    }

    private function process_payment_intent(array $intent): void {
        $metadata = $intent['metadata'] ?? [];
        $quote_id = isset($metadata['quote_id']) ? (int) $metadata['quote_id'] : 0;
        if (!$quote_id) return;

        $this->mark_deposit_paid($quote_id, $intent['id'] ?? '', (int)($intent['amount'] ?? 0));
    }

    private function mark_deposit_paid(int $quote_id, string $intent_id, int $amount_pence): void {
        // Idempotency: skip if already processed this intent
        if ($intent_id && (string) get_post_meta($quote_id, '_cfqm_stripe_intent_id', true) === $intent_id) {
            return;
        }

        if ($intent_id) {
            update_post_meta($quote_id, '_cfqm_stripe_intent_id', $intent_id);
        }

        Quote_Builder::instance()->set_status($quote_id, 'deposit_paid');

        Audit_Trail::instance()->log_system(Audit_Trail::EVT_DEPOSIT_PAID, $quote_id, [
            'intent_id'    => $intent_id,
            'amount_pence' => $amount_pence,
        ]);

        // Notify tradesperson
        Email_Notifications::instance()->send('deposit_paid', $quote_id);
    }

    // ─────────────────────────────────────────────────────────────────────
    // AJAX: return payment link for portal button
    // ─────────────────────────────────────────────────────────────────────

    public function ajax_get_payment_link(): void {
        if (!check_ajax_referer('cfqm_portal_nonce', 'nonce', false)) {
            wp_send_json_error([], 403);
        }
        $quote_id = Token_Manager::instance()->get_portal_session_quote();
        $link     = $quote_id ? (string) get_post_meta($quote_id, '_cfqm_stripe_link', true) : '';
        wp_send_json_success(['link' => $link]);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Stripe API helper (direct HTTP, no SDK)
    // ─────────────────────────────────────────────────────────────────────

    private function api_request(string $method, string $endpoint, string $sk, array $body = []): array {
        $url  = 'https://api.stripe.com' . $endpoint;
        $args = [
            'method'  => $method,
            'headers' => [
                'Authorization' => 'Bearer ' . $sk,
                'Content-Type'  => 'application/x-www-form-urlencoded',
            ],
            'body'    => $method === 'POST' ? $this->flatten($body) : [],
            'timeout' => 15,
        ];

        if ($method === 'GET' && $body) {
            $url = add_query_arg($body, $url);
        }

        $response = wp_remote_request($url, $args);
        if (is_wp_error($response)) {
            error_log('[CFQM Stripe] API error: ' . $response->get_error_message());
            return [];
        }

        $decoded = json_decode(wp_remote_retrieve_body($response), true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Flatten nested array for application/x-www-form-urlencoded.
     * e.g. ['line_items' => [['price' => 'p_123', 'quantity' => 1]]]
     * → ['line_items[0][price]' => 'p_123', 'line_items[0][quantity]' => 1]
     */
    private function flatten(array $data, string $prefix = ''): array {
        $result = [];
        foreach ($data as $key => $value) {
            $full_key = $prefix ? "{$prefix}[{$key}]" : (string)$key;
            if (is_array($value)) {
                $result = array_merge($result, $this->flatten($value, $full_key));
            } else {
                $result[$full_key] = $value;
            }
        }
        return $result;
    }

    // ─────────────────────────────────────────────────────────────────────
    // Signature verification (Stripe-Signature header)
    // ─────────────────────────────────────────────────────────────────────

    private function verify_signature(string $payload, string $header, string $secret): bool {
        $parts = [];
        foreach (explode(',', $header) as $chunk) {
            [$k, $v]     = explode('=', $chunk, 2) + ['', ''];
            $parts[$k][] = $v;
        }
        $timestamp = $parts['t'][0] ?? '';
        $sigs      = $parts['v1'] ?? [];
        if (!$timestamp || !$sigs) return false;

        // Reject replays > 5 min old
        if (abs(time() - (int)$timestamp) > 300) return false;

        $expected = hash_hmac('sha256', "{$timestamp}.{$payload}", $secret);
        foreach ($sigs as $sig) {
            if (hash_equals($expected, $sig)) return true;
        }
        return false;
    }
}
