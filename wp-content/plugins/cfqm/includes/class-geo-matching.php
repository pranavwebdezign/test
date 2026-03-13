<?php
namespace CFQM;
defined('ABSPATH') || exit;

/**
 * Geo_Matching
 *
 * Algorithm:
 *   1. Geocode homeowner postcode via postcodes.io (UK).
 *   2. Filter TradeProfiles by matching cfqm_category AND homeowner within tradesperson's radius.
 *   3. Rank by activity_score DESC (response_rate × 0.6 + recency × 0.4).
 *   4. Notify top-N (default 10) trade profiles.
 *   5. Enforce response cap: first M (default 5) responders accepted; rest notified "Quotes Full".
 *   6. If ALL submitted quotes are declined by homeowner, reopen query to remaining pool.
 */
class Geo_Matching {
    use Singleton;

    const POSTCODES_API = 'https://api.postcodes.io/postcodes/';

    public function boot(): void {
        add_action('cfqm_query_submitted', [$this, 'on_query_submitted'], 10, 1);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Public API
    // ─────────────────────────────────────────────────────────────────────

    /** Called when a homeowner submits a query. Stores matched trade IDs. */
    public function on_query_submitted(int $query_id): void {
        $postcode = (string) get_post_meta($query_id, '_cfqm_hw_postcode', true);
        $terms    = wp_get_post_terms($query_id, 'cfqm_category', ['fields' => 'ids']);

        if (!$postcode || empty($terms)) return;

        $coords = $this->geocode($postcode);
        if (!$coords) return;

        update_post_meta($query_id, '_cfqm_hw_lat', $coords['lat']);
        update_post_meta($query_id, '_cfqm_hw_lng', $coords['lng']);

        $matches = $this->find_matching_trades($coords['lat'], $coords['lng'], (int)$terms[0]);

        $pool_size = (int) Settings::instance()->get('query_pool_size', 10);
        $top       = array_slice($matches, 0, $pool_size);
        $trade_ids = array_column($top, 'post_id');

        update_post_meta($query_id, '_cfqm_matched_trades', wp_json_encode($trade_ids));
        update_post_meta($query_id, '_cfqm_hw_status', 'open');

        // Notify matched trades
        foreach ($trade_ids as $tid) {
            Email_Notifications::instance()->send('query_received', 0, $query_id, $tid);
        }

        Database::instance()->log_event([
            'query_id'   => $query_id,
            'event_type' => 'query_matched',
            'event_data' => ['trade_count' => count($trade_ids)],
        ]);
    }

    /**
     * Called when a tradesperson submits a quote for a query.
     * Returns false if cap is reached (this trade should be blocked).
     */
    public function record_response(int $query_id, int $trade_id, int $quote_id): bool {
        $responses = $this->get_responses($query_id);
        $cap       = (int) Settings::instance()->get('response_cap', 5);

        if (count($responses) >= $cap) {
            // Notify this late trade that the cap is reached
            Email_Notifications::instance()->send('query_full', 0, $query_id, $trade_id);
            return false;
        }

        $responses[$trade_id] = $quote_id;
        update_post_meta($query_id, '_cfqm_responses', wp_json_encode($responses));

        if (count($responses) >= $cap) {
            // Mark query as "quotes full" and notify remaining pool
            update_post_meta($query_id, '_cfqm_hw_status', 'quotes_full');
            $this->notify_remaining_pool($query_id, array_keys($responses));
        }

        return true;
    }

    /**
     * Called when homeowner declines ALL quotes for this query.
     * Reopens the query to trades in the original pool who haven't responded.
     */
    public function reopen_on_all_declined(int $query_id): void {
        $matched   = json_decode((string) get_post_meta($query_id, '_cfqm_matched_trades', true), true) ?: [];
        $responses = $this->get_responses($query_id);
        $remaining = array_diff($matched, array_keys($responses));

        if (empty($remaining)) return;

        update_post_meta($query_id, '_cfqm_hw_status', 'reopened');
        update_post_meta($query_id, '_cfqm_responses', wp_json_encode([])); // reset cap

        foreach ($remaining as $tid) {
            Email_Notifications::instance()->send('query_reopened', 0, $query_id, $tid);
        }

        Database::instance()->log_event([
            'query_id'   => $query_id,
            'event_type' => 'query_reopened',
            'event_data' => ['remaining_trades' => count($remaining)],
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Geocoding
    // ─────────────────────────────────────────────────────────────────────

    public function geocode(string $postcode): ?array {
        $postcode = strtoupper(preg_replace('/\s+/', '', $postcode));
        $cached   = get_transient('cfqm_geo_' . $postcode);
        if ($cached) return $cached;

        $resp = wp_remote_get(self::POSTCODES_API . rawurlencode($postcode), [
            'timeout' => 5,
            'headers' => ['Accept' => 'application/json'],
        ]);
        if (is_wp_error($resp)) return null;

        $body = json_decode(wp_remote_retrieve_body($resp), true);
        if (empty($body['result'])) return null;

        $coords = [
            'lat' => (float) $body['result']['latitude'],
            'lng' => (float) $body['result']['longitude'],
        ];
        set_transient('cfqm_geo_' . $postcode, $coords, WEEK_IN_SECONDS);
        return $coords;
    }

    // ─────────────────────────────────────────────────────────────────────
    // Matching
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Return all trade profiles in the given category that cover the point,
     * ordered by activity score descending.
     *
     * @return array<array{post_id: int, distance: float, score: float}>
     */
    public function find_matching_trades(float $lat, float $lng, int $category_term_id): array {
        $args  = [
            'post_type'      => 'cfqm_trade_profile',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'tax_query'      => [[
                'taxonomy' => 'cfqm_category',
                'field'    => 'term_id',
                'terms'    => [$category_term_id],
            ]],
        ];
        $ids    = get_posts($args);
        $result = [];

        foreach ($ids as $tid) {
            $t_lat    = (float) get_post_meta($tid, '_cfqm_lat',          true);
            $t_lng    = (float) get_post_meta($tid, '_cfqm_lng',          true);
            $radius   = (float) get_post_meta($tid, '_cfqm_radius_miles', true) ?: 30;
            $score    = (float) get_post_meta($tid, '_cfqm_activity_score', true);

            if (!$t_lat || !$t_lng) continue;

            $dist = $this->haversine($lat, $lng, $t_lat, $t_lng);
            if ($dist > $radius) continue;

            $result[] = [
                'post_id'  => $tid,
                'distance' => $dist,
                'score'    => $score,
            ];
        }

        usort($result, fn($a, $b) => $b['score'] <=> $a['score']);
        return $result;
    }

    // ─────────────────────────────────────────────────────────────────────
    // Activity score update (run via cron)
    // ─────────────────────────────────────────────────────────────────────

    public function update_activity_scores(): void {
        $ids = get_posts([
            'post_type'      => 'cfqm_trade_profile',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
        ]);

        foreach ($ids as $tid) {
            $response_rate = (float) get_post_meta($tid, '_cfqm_response_rate', true);  // 0-1
            $last_active   = strtotime((string) get_post_meta($tid, '_cfqm_last_active', true) ?: '-30 days');
            $days_ago      = max(0, (time() - $last_active) / DAY_IN_SECONDS);
            $recency       = max(0, 1 - ($days_ago / 30));  // decays to 0 after 30 days

            $score = ($response_rate * 0.6) + ($recency * 0.4);
            update_post_meta($tid, '_cfqm_activity_score', round($score * 100, 2));
        }
    }

    // ─────────────────────────────────────────────────────────────────────
    // Internals
    // ─────────────────────────────────────────────────────────────────────

    private function haversine(float $lat1, float $lng1, float $lat2, float $lng2): float {
        $R  = 3958.8; // Earth radius in miles
        $dL = deg2rad($lat2 - $lat1);
        $dG = deg2rad($lng2 - $lng1);
        $a  = sin($dL / 2) ** 2
              + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dG / 2) ** 2;
        return 2 * $R * asin(sqrt($a));
    }

    private function notify_remaining_pool(int $query_id, array $already_responded): void {
        $matched   = json_decode((string) get_post_meta($query_id, '_cfqm_matched_trades', true), true) ?: [];
        $remaining = array_diff($matched, $already_responded);
        foreach ($remaining as $tid) {
            Email_Notifications::instance()->send('query_full', 0, $query_id, $tid);
        }
    }

    private function get_responses(int $query_id): array {
        $raw = (string) get_post_meta($query_id, '_cfqm_responses', true);
        return json_decode($raw, true) ?: [];
    }
}
