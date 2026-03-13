<?php
namespace CFQM;
defined('ABSPATH') || exit;

/**
 * Token_Manager
 *
 * Two token types:
 *
 *   portal     – always-latest quote link for customers (no expiry, no account needed)
 *   magic_link – homeowner dashboard access (expires 15 min, session cookie 24 h)
 *
 * On every page load the token in `?cfqm_token=` is validated here and
 * the appropriate context (quote ID or homeowner email) is stashed in a
 * short-lived WP transient + cookie so subsequent requests don't re-auth.
 */
class Token_Manager {
    use Singleton;

    const SESSION_TTL     = DAY_IN_SECONDS;          // 24h cookie / transient
    const MAGIC_LINK_TTL  = 15 * MINUTE_IN_SECONDS;  // 15-min email link

    const COOKIE_PORTAL   = 'cfqm_portal_session';
    const COOKIE_HW       = 'cfqm_hw_session';

    public function boot(): void {
        add_action('template_redirect', [$this, 'process_incoming_token'], 1);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Portal tokens (always-latest quote link)
    // ─────────────────────────────────────────────────────────────────────

    /** Create or refresh the always-latest portal token for a quote. */
    public function issue_portal_token(int $quote_id): string {
        // Reuse existing valid token if present
        $existing = (string) get_post_meta($quote_id, '_cfqm_portal_token', true);
        if ($existing && $this->validate_portal_token($existing) === $quote_id) {
            return $existing;
        }

        $raw = Database::instance()->create_token([
            'type'    => 'portal',
            'ref_id'  => $quote_id,
        ]);
        if (!$raw) return '';

        update_post_meta($quote_id, '_cfqm_portal_token', $raw);
        return $raw;
    }

    /**
     * Validate a portal token. Returns quote_id on success, 0 on failure.
     */
    public function validate_portal_token(string $raw): int {
        $row = Database::instance()->find_token($raw);
        if (!$row || $row->token_type !== 'portal') return 0;
        return (int) $row->ref_id;
    }

    // ─────────────────────────────────────────────────────────────────────
    // Magic-link tokens (homeowner dashboard — 15-min one-time link)
    // ─────────────────────────────────────────────────────────────────────

    /** Generate a 15-min magic link token for an email address. */
    public function issue_magic_link(string $email): string {
        $email = sanitize_email($email);
        if (!is_email($email)) return '';

        $expires = gmdate('Y-m-d H:i:s', time() + self::MAGIC_LINK_TTL);
        $raw     = Database::instance()->create_token([
            'type'       => 'magic_link',
            'ref_email'  => $email,
            'expires_at' => $expires,
        ]);
        return $raw ?: '';
    }

    /**
     * Consume a magic-link token. Returns email on success, '' on failure.
     */
    public function consume_magic_link(string $raw): string {
        $row = Database::instance()->find_token($raw);
        if (!$row
            || $row->token_type !== 'magic_link'
            || !empty($row->used_at)
            || ($row->expires_at && strtotime($row->expires_at) < time())
        ) {
            return '';
        }
        Database::instance()->consume_token($raw);
        return (string) $row->ref_email;
    }

    // ─────────────────────────────────────────────────────────────────────
    // Session helpers
    // ─────────────────────────────────────────────────────────────────────

    /** Start a portal session for a quote (cookie + transient). */
    public function start_portal_session(int $quote_id): void {
        $key = 'cfqm_ps_' . bin2hex(random_bytes(16));
        set_transient($key, ['type' => 'portal', 'quote_id' => $quote_id], self::SESSION_TTL);
        setcookie(self::COOKIE_PORTAL, $key, time() + self::SESSION_TTL, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true);
    }

    /** Get the current quote_id from a portal session cookie. Returns 0 if none. */
    public function get_portal_session_quote(): int {
        $key  = sanitize_key($_COOKIE[self::COOKIE_PORTAL] ?? '');
        $data = $key ? get_transient($key) : false;
        return is_array($data) && ($data['type'] ?? '') === 'portal' ? (int)$data['quote_id'] : 0;
    }

    /** Start a homeowner dashboard session (cookie + transient). */
    public function start_hw_session(string $email): void {
        $key = 'cfqm_hw_' . bin2hex(random_bytes(16));
        set_transient($key, ['type' => 'hw', 'email' => $email], self::SESSION_TTL);
        setcookie(self::COOKIE_HW, $key, time() + self::SESSION_TTL, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true);
    }

    /** Get the authenticated homeowner email from session cookie. Returns '' if none. */
    public function get_hw_session_email(): string {
        $key  = sanitize_key($_COOKIE[self::COOKIE_HW] ?? '');
        $data = $key ? get_transient($key) : false;
        return is_array($data) && ($data['type'] ?? '') === 'hw' ? (string)$data['email'] : '';
    }

    // ─────────────────────────────────────────────────────────────────────
    // Incoming token dispatcher
    // ─────────────────────────────────────────────────────────────────────

    public function process_incoming_token(): void {
        $raw = isset($_GET['cfqm_token']) ? sanitize_text_field(wp_unslash($_GET['cfqm_token'])) : '';
        if (!$raw) return;

        $row = Database::instance()->find_token($raw);
        if (!$row) return;

        if ($row->token_type === 'portal') {
            $quote_id = (int) $row->ref_id;
            if ($quote_id) {
                $this->start_portal_session($quote_id);
                // Log "viewed" event (once per session)
                Audit_Trail::instance()->log('quote_viewed', $quote_id);
            }
        }

        if ($row->token_type === 'magic_link') {
            $email = $this->consume_magic_link($raw);
            if ($email) {
                $this->start_hw_session($email);
            }
        }
    }
}
