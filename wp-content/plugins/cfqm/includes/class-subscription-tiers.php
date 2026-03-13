<?php
namespace CFQM;
defined('ABSPATH') || exit;

/**
 * Subscription_Tiers
 *
 * Starter: 10 quotes/month, 5 iterations max per quote
 * Pro:     40 quotes/month, 10 iterations max per quote
 *
 * Integrates with: Paid Memberships Pro (pmpro_hasMembershipLevel),
 *                  MemberPress (mepr_get_current_user_subscriptions),
 *                  WooCommerce Memberships (wc_memberships_is_user_active_member).
 *
 * Falls back to a site-wide "all can use" mode when no PMS is active.
 */
class Subscription_Tiers {
    use Singleton;

    // Plan constants
    const PLAN_NONE    = 'none';
    const PLAN_STARTER = 'starter';
    const PLAN_PRO     = 'pro';

    const LIMITS = [
        self::PLAN_STARTER => ['quotes_per_month' => 10, 'max_iterations' => 5],
        self::PLAN_PRO     => ['quotes_per_month' => 40, 'max_iterations' => 10],
    ];

    // PMS level IDs / slugs — update in Settings if they differ per site
    const PMPRO_STARTER_LEVEL = 1;
    const PMPRO_PRO_LEVEL     = 2;

    public function boot(): void {}

    // ─────────────────────────────────────────────────────────────────────
    // Plan detection
    // ─────────────────────────────────────────────────────────────────────

    public function get_user_plan(int $user_id = 0): string {
        if (!$user_id) $user_id = get_current_user_id();

        // Paid Memberships Pro
        if (function_exists('pmpro_hasMembershipLevel')) {
            if (pmpro_hasMembershipLevel(self::PMPRO_PRO_LEVEL,     $user_id)) return self::PLAN_PRO;
            if (pmpro_hasMembershipLevel(self::PMPRO_STARTER_LEVEL, $user_id)) return self::PLAN_STARTER;
        }

        // MemberPress
        if (function_exists('mepr_get_current_user_subscriptions')) {
            $subs = mepr_get_current_user_subscriptions($user_id);
            foreach ($subs as $sub) {
                if (stripos($sub->product_name ?? '', 'pro')     !== false) return self::PLAN_PRO;
                if (stripos($sub->product_name ?? '', 'starter') !== false) return self::PLAN_STARTER;
            }
        }

        // WooCommerce Memberships
        if (function_exists('wc_memberships_is_user_active_member')) {
            if (wc_memberships_is_user_active_member($user_id, 'pro'))     return self::PLAN_PRO;
            if (wc_memberships_is_user_active_member($user_id, 'starter')) return self::PLAN_STARTER;
        }

        // Admin always gets Pro
        if (user_can($user_id, 'manage_options')) return self::PLAN_PRO;

        return self::PLAN_NONE;
    }

    public function get_limit(string $plan, string $key): int {
        return (int)($this::LIMITS[$plan][$key] ?? 0);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Quota enforcement
    // ─────────────────────────────────────────────────────────────────────

    /** How many quotes the user has sent this calendar month. */
    public function get_monthly_usage(int $user_id): int {
        global $wpdb;
        $start = date('Y-m-01 00:00:00');
        return (int) $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$wpdb->posts}
            WHERE post_type   = 'cfqm_quote'
              AND post_author = %d
              AND post_date  >= %s
              AND post_status = 'publish'
        ", $user_id, $start));
    }

    /** Returns true when the user is allowed to create one more quote. */
    public function can_create_quote(int $user_id = 0): bool {
        if (!$user_id) $user_id = get_current_user_id();
        $plan = $this->get_user_plan($user_id);
        if ($plan === self::PLAN_NONE) return false;
        $used  = $this->get_monthly_usage($user_id);
        $limit = $this->get_limit($plan, 'quotes_per_month');
        return $used < $limit;
    }

    /** Returns true when this quote can receive one more amendment. */
    public function can_amend(int $quote_id): bool {
        $used  = (int) get_post_meta($quote_id, '_cfqm_iteration_used',  true);
        $limit = (int) get_post_meta($quote_id, '_cfqm_iteration_limit', true);
        return $limit === 0 || $used < $limit;
    }

    /** Set default iteration limit on a new quote based on user's plan. */
    public function set_default_iteration_limit(int $quote_id, int $user_id = 0): void {
        if (!$user_id) $user_id = (int) get_post_field('post_author', $quote_id);
        $plan  = $this->get_user_plan($user_id);
        $limit = $this->get_limit($plan, 'max_iterations') ?: 5;
        if (!get_post_meta($quote_id, '_cfqm_iteration_limit', true)) {
            update_post_meta($quote_id, '_cfqm_iteration_limit', $limit);
        }
    }

    /** Human-readable plan label. */
    public function plan_label(string $plan): string {
        return match ($plan) {
            self::PLAN_PRO     => 'Pro',
            self::PLAN_STARTER => 'Starter',
            default            => 'No Plan',
        };
    }
}
