<?php
namespace CFQM;
defined('ABSPATH') || exit;

/**
 * Audit_Trail
 *
 * Thin wrapper around Database::log_event() with named event-type constants.
 *
 * Fields stored per event (per meeting notes):
 *   event_id · timestamp · quote_id · query_id · version · actor_type · actor_id · event_type · event_data
 *
 * Actor types: tradesperson | homeowner | system | admin
 */
class Audit_Trail {
    use Singleton;

    // ── Event type constants ───────────────────────────────────────────
    const EVT_QUOTE_CREATED          = 'quote_created';
    const EVT_QUOTE_SENT             = 'quote_sent';
    const EVT_QUOTE_VIEWED           = 'quote_viewed';
    const EVT_QUOTE_APPROVED         = 'quote_approved';
    const EVT_QUOTE_PARTIAL          = 'quote_partial';
    const EVT_QUOTE_DECLINED         = 'quote_declined';
    const EVT_DEPOSIT_PAID           = 'deposit_paid';
    const EVT_QUOTE_EXPIRED          = 'quote_expired';
    const EVT_AMENDMENT_REQUESTED    = 'amendment_requested';
    const EVT_AMENDMENT_REJECTED     = 'amendment_rejected';
    const EVT_AMENDMENT_APPROVED_AS_IS = 'amendment_approved_as_is';
    const EVT_QUOTE_REISSUED         = 'quote_reissued';
    const EVT_QUERY_SUBMITTED        = 'query_submitted';
    const EVT_QUERY_MATCHED          = 'query_matched';
    const EVT_QUERY_FULL             = 'query_full';
    const EVT_QUERY_REOPENED         = 'query_reopened';
    const EVT_REMINDER_48H           = 'reminder_48h';
    const EVT_REMINDER_24H_EXPIRY    = 'reminder_24h_expiry';
    const EVT_PAYMENT_LINK_CREATED   = 'payment_link_created';
    const EVT_STRIPE_WEBHOOK         = 'stripe_webhook';
    const EVT_PDF_GENERATED          = 'pdf_generated';
    const EVT_MAGIC_LINK_SENT        = 'magic_link_sent';
    const EVT_SIGNATURE_CAPTURED     = 'signature_captured';

    public function boot(): void {}

    // ─────────────────────────────────────────────────────────────────────

    /**
     * Log a named event for a quote (and optionally a query).
     *
     * @param string $event_type  One of the EVT_* constants above.
     * @param int    $quote_id    0 if not quote-specific.
     * @param array  $extra       Extra data to store as JSON.
     */
    public function log(string $event_type, int $quote_id = 0, array $extra = []): int|false {
        $user_id = get_current_user_id();

        // Determine actor type
        if ($user_id) {
            $actor_type = current_user_can('manage_options') ? 'admin' : 'tradesperson';
        } else {
            $actor_type = 'homeowner';  // unauthenticated = homeowner action via portal
        }

        $version  = $quote_id
            ? (string) get_post_meta($quote_id, '_cfqm_version', true)
            : '';
        $query_id = $quote_id
            ? (int) get_post_meta($quote_id, '_cfqm_query_id', true)
            : 0;

        return Database::instance()->log_event([
            'quote_id'   => $quote_id,
            'query_id'   => $query_id,
            'version'    => $version,
            'actor_type' => $actor_type,
            'actor_id'   => $user_id ? (string)$user_id : 'guest',
            'event_type' => $event_type,
            'event_data' => $extra ?: null,
        ]);
    }

    /** Log a system event (no user context). */
    public function log_system(string $event_type, int $quote_id = 0, array $extra = []): int|false {
        $version  = $quote_id ? (string) get_post_meta($quote_id, '_cfqm_version', true) : '';
        $query_id = $quote_id ? (int) get_post_meta($quote_id, '_cfqm_query_id', true) : 0;

        return Database::instance()->log_event([
            'quote_id'   => $quote_id,
            'query_id'   => $query_id,
            'version'    => $version,
            'actor_type' => 'system',
            'actor_id'   => 'cron',
            'event_type' => $event_type,
            'event_data' => $extra ?: null,
        ]);
    }

    /** Return the full event timeline for a quote, newest first. */
    public function get_quote_timeline(int $quote_id): array {
        $events = Database::instance()->get_events(['quote_id' => $quote_id]);
        foreach ($events as $e) {
            if ($e->event_data) {
                $e->event_data = json_decode($e->event_data, true);
            }
        }
        return $events;
    }

    /** Human-readable label for an event type. */
    public function event_label(string $type): string {
        return match ($type) {
            self::EVT_QUOTE_CREATED           => 'Quote created',
            self::EVT_QUOTE_SENT              => 'Quote sent',
            self::EVT_QUOTE_VIEWED            => 'Quote viewed by customer',
            self::EVT_QUOTE_APPROVED          => 'Quote accepted',
            self::EVT_QUOTE_PARTIAL           => 'Quote partially accepted',
            self::EVT_QUOTE_DECLINED          => 'Quote declined',
            self::EVT_DEPOSIT_PAID            => 'Deposit paid',
            self::EVT_QUOTE_EXPIRED           => 'Quote expired',
            self::EVT_AMENDMENT_REQUESTED     => 'Amendment requested',
            self::EVT_AMENDMENT_REJECTED      => 'Amendment rejected',
            self::EVT_AMENDMENT_APPROVED_AS_IS=> 'Approved as-is',
            self::EVT_QUOTE_REISSUED          => 'New version issued',
            self::EVT_QUERY_SUBMITTED         => 'Query submitted',
            self::EVT_QUERY_MATCHED           => 'Trades matched',
            self::EVT_QUERY_FULL              => 'Quote slots full',
            self::EVT_QUERY_REOPENED          => 'Query reopened',
            self::EVT_REMINDER_48H            => '48-hour reminder sent',
            self::EVT_REMINDER_24H_EXPIRY     => '24-hour expiry reminder sent',
            self::EVT_PAYMENT_LINK_CREATED    => 'Payment link created',
            self::EVT_STRIPE_WEBHOOK          => 'Stripe payment received',
            self::EVT_PDF_GENERATED           => 'PDF generated',
            self::EVT_MAGIC_LINK_SENT         => 'Magic link sent',
            self::EVT_SIGNATURE_CAPTURED      => 'E-signature captured',
            default                           => ucwords(str_replace('_', ' ', $type)),
        };
    }
}
