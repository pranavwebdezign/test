<?php
namespace CFQM;
defined('ABSPATH') || exit;

/**
 * Versioning
 *
 * Manages quote version numbers (1.0, 1.1, 1.2 …) and per-version snapshots.
 * Snapshots store a full copy of quote + area state at the moment of sending
 * each version — used for audit display and potential per-version PDF links.
 *
 * Default behaviour: always-latest portal link (agreed in meeting notes).
 * Per-version links are a post-MVP option.
 */
class Versioning {
    use Singleton;

    public function boot(): void {
        // Snapshot on every "sent" transition
        add_action('cfqm_quote_status_changed', [$this, 'on_status_changed'], 10, 3);
    }

    /** Bump the minor version number. Returns the new version string, or '' on failure. */
    public function bump_version(int $quote_id): string {
        $current = (string)(get_post_meta($quote_id, '_cfqm_version', true) ?: '1.0');
        [$major, $minor] = explode('.', $current) + [0, 0];
        $new = $major . '.' . ((int)$minor + 1);
        update_post_meta($quote_id, '_cfqm_version', $new);
        return $new;
    }

    /** On status → sent: save a snapshot of the current quote state. */
    public function on_status_changed(int $quote_id, string $old, string $new): void {
        if ($new === 'sent') {
            $this->snapshot($quote_id);
        }
    }

    /** Save a named snapshot of the full quote data keyed by current version. */
    public function snapshot(int $quote_id): void {
        $version   = (string)(get_post_meta($quote_id, '_cfqm_version', true) ?: '1.0');
        $snapshots = json_decode((string) get_post_meta($quote_id, '_cfqm_snapshots', true), true) ?: [];

        $snapshots[$version] = [
            'at'         => current_time('mysql'),
            'quote_data' => Quote_Builder::instance()->get_full_quote_data($quote_id),
        ];

        update_post_meta($quote_id, '_cfqm_snapshots', wp_json_encode($snapshots));
    }

    /** Retrieve a specific version snapshot, or the latest if $version is empty. */
    public function get_snapshot(int $quote_id, string $version = ''): ?array {
        $snapshots = json_decode((string) get_post_meta($quote_id, '_cfqm_snapshots', true), true) ?: [];
        if (!$snapshots) return null;

        if ($version && isset($snapshots[$version])) {
            return $snapshots[$version];
        }
        // Return the last snapshot
        return end($snapshots) ?: null;
    }

    /** All version strings for a quote, sorted ascending. */
    public function get_all_versions(int $quote_id): array {
        $snapshots = json_decode((string) get_post_meta($quote_id, '_cfqm_snapshots', true), true) ?: [];
        $versions  = array_keys($snapshots);
        usort($versions, 'version_compare');
        return $versions;
    }
}
