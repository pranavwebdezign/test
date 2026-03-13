<?php
namespace CFQM;
defined('ABSPATH') || exit;

/**
 * Database
 *
 * Two custom tables:
 *   {prefix}cfqm_events  – append-only audit/event log
 *   {prefix}cfqm_tokens  – portal + magic-link token store
 *
 * All quote data lives in WP post-meta (no extra quote table needed).
 */
class Database {
    use Singleton;

    public function boot(): void {
        // Check for DB upgrades on every load
        if ((int) get_option('cfqm_db_version', 0) < CFQM_DB_VER) {
            $this->install_tables();
            update_option('cfqm_db_version', CFQM_DB_VER);
        }
    }

    // ─────────────────────────────────────────────────────────────────────
    // Table creation
    // ─────────────────────────────────────────────────────────────────────
    public function install_tables(): void {
        global $wpdb;
        $c = $wpdb->get_charset_collate();
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        dbDelta("
            CREATE TABLE IF NOT EXISTS {$wpdb->prefix}cfqm_events (
                event_id    BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                quote_id    BIGINT UNSIGNED NOT NULL DEFAULT 0,
                query_id    BIGINT UNSIGNED NOT NULL DEFAULT 0,
                version     VARCHAR(10)     NOT NULL DEFAULT '',
                actor_type  VARCHAR(20)     NOT NULL DEFAULT 'system',
                actor_id    VARCHAR(200)    NOT NULL DEFAULT '',
                event_type  VARCHAR(60)     NOT NULL DEFAULT '',
                event_data  LONGTEXT,
                created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (event_id),
                KEY qu_idx (quote_id),
                KEY qr_idx (query_id),
                KEY ty_idx (event_type)
            ) $c;");

        dbDelta("
            CREATE TABLE IF NOT EXISTS {$wpdb->prefix}cfqm_tokens (
                token_id    BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                token_hash  VARCHAR(64)     NOT NULL,
                token_type  VARCHAR(30)     NOT NULL DEFAULT 'portal',
                ref_id      BIGINT UNSIGNED NOT NULL DEFAULT 0,
                ref_email   VARCHAR(200)    NOT NULL DEFAULT '',
                expires_at  DATETIME,
                used_at     DATETIME,
                created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (token_id),
                UNIQUE KEY hash_uniq (token_hash),
                KEY type_idx (token_type)
            ) $c;");
    }

    // ─────────────────────────────────────────────────────────────────────
    // Event log
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Insert an audit event.
     *
     * @param array{
     *   event_type: string,
     *   quote_id?: int,
     *   query_id?: int,
     *   version?: string,
     *   actor_type?: string,   // 'tradesperson'|'homeowner'|'system'|'admin'
     *   actor_id?: string,
     *   event_data?: array|string|null
     * } $args
     */
    public function log_event(array $args): int|false {
        global $wpdb;
        $d = wp_parse_args($args, [
            'quote_id'   => 0,
            'query_id'   => 0,
            'version'    => '',
            'actor_type' => 'system',
            'actor_id'   => '',
            'event_type' => '',
            'event_data' => null,
        ]);
        if (is_array($d['event_data'])) {
            $d['event_data'] = wp_json_encode($d['event_data']);
        }
        $ok = $wpdb->insert(
            $wpdb->prefix . 'cfqm_events',
            [
                'quote_id'   => (int)    $d['quote_id'],
                'query_id'   => (int)    $d['query_id'],
                'version'    => (string) $d['version'],
                'actor_type' => (string) $d['actor_type'],
                'actor_id'   => (string) $d['actor_id'],
                'event_type' => (string) $d['event_type'],
                'event_data' => $d['event_data'],
            ],
            ['%d','%d','%s','%s','%s','%s','%s']
        );
        return $ok ? (int) $wpdb->insert_id : false;
    }

    /** Fetch events with optional filters. */
    public function get_events(array $args = []): array {
        global $wpdb;
        $where  = '1=1';
        $params = [];

        if (!empty($args['quote_id'])) { $where .= ' AND quote_id=%d'; $params[] = (int)$args['quote_id']; }
        if (!empty($args['query_id'])) { $where .= ' AND query_id=%d'; $params[] = (int)$args['query_id']; }
        if (!empty($args['event_type'])){ $where .= ' AND event_type=%s'; $params[] = $args['event_type']; }

        $limit    = (int)($args['limit'] ?? 200);
        $params[] = $limit;
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $sql = "SELECT * FROM {$wpdb->prefix}cfqm_events WHERE $where ORDER BY created_at DESC LIMIT %d";
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        return $wpdb->get_results($wpdb->prepare($sql, $params)) ?: [];
    }

    // ─────────────────────────────────────────────────────────────────────
    // Token store
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Create a token. Returns the raw (unhashed) 64-char hex string.
     *
     * @param array{type: string, ref_id?: int, ref_email?: string, expires_at?: string|null} $args
     */
    public function create_token(array $args): string|false {
        global $wpdb;
        $raw  = bin2hex(random_bytes(32));
        $hash = hash('sha256', $raw);
        $ok   = $wpdb->insert(
            $wpdb->prefix . 'cfqm_tokens',
            [
                'token_hash' => $hash,
                'token_type' => $args['type']       ?? 'portal',
                'ref_id'     => (int)($args['ref_id']   ?? 0),
                'ref_email'  => sanitize_email($args['ref_email'] ?? ''),
                'expires_at' => $args['expires_at'] ?? null,
            ],
            ['%s','%s','%d','%s','%s']
        );
        return $ok ? $raw : false;
    }

    /** Look up a token row by raw token. Returns null if not found. */
    public function find_token(string $raw): ?object {
        global $wpdb;
        $hash = hash('sha256', $raw);
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}cfqm_tokens WHERE token_hash=%s", $hash
        ));
    }

    /** Mark a token used (one-time). */
    public function consume_token(string $raw): void {
        global $wpdb;
        $hash = hash('sha256', $raw);
        $wpdb->update(
            $wpdb->prefix . 'cfqm_tokens',
            ['used_at' => current_time('mysql')],
            ['token_hash' => $hash],
            ['%s'], ['%s']
        );
    }

    /** Delete all expired tokens. */
    public function purge_expired_tokens(): int {
        global $wpdb;
        return (int) $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->prefix}cfqm_tokens WHERE expires_at IS NOT NULL AND expires_at < %s",
            current_time('mysql')
        ));
    }
}
