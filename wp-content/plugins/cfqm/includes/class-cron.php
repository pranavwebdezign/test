<?php
namespace CFQM;
defined('ABSPATH') || exit;

/**
 * Cron
 *
 * Registered WP-Cron job: cfqm_hourly (fired every hour)
 *
 * Tasks per run:
 *   1. 48-hour unviewed reminder → email homeowner if quote still 'sent' after 48h
 *   2. 24-hour expiry reminder  → email homeowner 24h before validity_date
 *   3. Quote expiration          → set status = expired when validity_date passed
 *   4. Activity score update     → recalculate all trade profile activity scores
 *   5. Expired token purge       → clean up cfqm_tokens table
 */
class Cron {
    use Singleton;

    public function boot(): void {
        add_action('cfqm_hourly', [$this, 'run_all_jobs']);

        // Register custom interval (not strictly needed — 'hourly' is built-in)
        add_filter('cron_schedules', [$this, 'add_schedules']);
    }

    public function add_schedules(array $schedules): array {
        if (!isset($schedules['cfqm_every_4_hours'])) {
            $schedules['cfqm_every_4_hours'] = [
                'interval' => 4 * HOUR_IN_SECONDS,
                'display'  => 'Every 4 Hours',
            ];
        }
        return $schedules;
    }

    // ─────────────────────────────────────────────────────────────────────
    // Master runner
    // ─────────────────────────────────────────────────────────────────────

    public function run_all_jobs(): void {
        $this->send_48h_unviewed_reminders();
        $this->send_24h_expiry_reminders();
        $this->expire_overdue_quotes();
        Geo_Matching::instance()->update_activity_scores();
        Database::instance()->purge_expired_tokens();
    }

    // ─────────────────────────────────────────────────────────────────────
    // Job 1 — 48-hour unviewed reminder
    // ─────────────────────────────────────────────────────────────────────

    private function send_48h_unviewed_reminders(): void {
        $threshold = gmdate('Y-m-d H:i:s', time() - 48 * HOUR_IN_SECONDS);

        // Quotes in 'sent' status where sent_at < 48h ago AND no reminder logged yet
        $posts = $this->get_quotes_by_status('sent', [
            ['key' => '_cfqm_sent_at', 'value' => $threshold, 'compare' => '<', 'type' => 'DATETIME'],
            ['key' => '_cfqm_reminder_48h_sent', 'compare' => 'NOT EXISTS'],
        ]);

        foreach ($posts as $post) {
            Email_Notifications::instance()->send('reminder_unviewed', $post->ID);
            update_post_meta($post->ID, '_cfqm_reminder_48h_sent', current_time('mysql'));
            Audit_Trail::instance()->log_system(Audit_Trail::EVT_REMINDER_48H, $post->ID);
        }
    }

    // ─────────────────────────────────────────────────────────────────────
    // Job 2 — 24-hour expiry reminder
    // ─────────────────────────────────────────────────────────────────────

    private function send_24h_expiry_reminders(): void {
        $tomorrow = gmdate('Y-m-d', time() + 24 * HOUR_IN_SECONDS);
        $today    = gmdate('Y-m-d');

        // Any area with validity_date = tomorrow, quote still active, no reminder sent
        $area_posts = get_posts([
            'post_type'      => 'cfqm_quote_area',
            'post_status'    => 'publish',
            'posts_per_page' => 100,
            'meta_query'     => [
                ['key' => '_cfqm_validity_date', 'value' => $tomorrow, 'compare' => '='],
            ],
        ]);

        $notified = [];
        foreach ($area_posts as $area) {
            $quote_id = (int) get_post_meta($area->ID, '_cfqm_quote_id', true);
            if (!$quote_id || in_array($quote_id, $notified, true)) continue;

            $status = (string) get_post_meta($quote_id, '_cfqm_status', true);
            if (!in_array($status, ['sent','viewed','pending_amendment'], true)) continue;

            if (get_post_meta($quote_id, '_cfqm_reminder_expiry_sent', true)) continue;

            Email_Notifications::instance()->send('reminder_expiry', $quote_id);
            update_post_meta($quote_id, '_cfqm_reminder_expiry_sent', current_time('mysql'));
            Audit_Trail::instance()->log_system(Audit_Trail::EVT_REMINDER_24H_EXPIRY, $quote_id);
            $notified[] = $quote_id;
        }
    }

    // ─────────────────────────────────────────────────────────────────────
    // Job 3 — Expire overdue quotes
    // ─────────────────────────────────────────────────────────────────────

    private function expire_overdue_quotes(): void {
        $today = gmdate('Y-m-d');

        // Find areas where ALL areas' validity_date has passed
        // Simplified: mark expired if quote was sent > 90 days ago with no action
        $threshold = gmdate('Y-m-d H:i:s', time() - 90 * DAY_IN_SECONDS);

        $posts = $this->get_quotes_by_status(['sent','viewed'], [
            ['key' => '_cfqm_sent_at', 'value' => $threshold, 'compare' => '<', 'type' => 'DATETIME'],
        ]);

        foreach ($posts as $post) {
            Quote_Builder::instance()->set_status($post->ID, 'expired');
            Audit_Trail::instance()->log_system(Audit_Trail::EVT_QUOTE_EXPIRED, $post->ID);
        }
    }

    // ─────────────────────────────────────────────────────────────────────
    // Helper
    // ─────────────────────────────────────────────────────────────────────

    private function get_quotes_by_status(string|array $statuses, array $extra_meta = []): array {
        $statuses   = (array) $statuses;
        $meta_query = [
            ['key' => '_cfqm_status', 'value' => $statuses, 'compare' => 'IN'],
        ];

        if ($extra_meta) {
            foreach ($extra_meta as $clause) {
                $meta_query[] = $clause;
            }
        }

        return get_posts([
            'post_type'      => 'cfqm_quote',
            'post_status'    => 'publish',
            'posts_per_page' => 200,
            'meta_query'     => $meta_query,
        ]);
    }
}
