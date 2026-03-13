<?php
namespace CFQM;
defined('ABSPATH') || exit;

/**
 * Email_Notifications
 *
 * Plugin-native email via wp_mail() (SMTP plugin optional).
 * No SendGrid API dependency for MVP — swap is a trivial config change post-MVP.
 *
 * 9 transactional templates:
 *   query_received        → tradesperson: new query available
 *   query_full            → tradesperson: quote slots filled
 *   query_reopened        → tradesperson: query reopened
 *   quote_sent            → homeowner: new quote to review
 *   amendment_requested   → tradesperson: customer requested amendment
 *   amendment_rejected    → homeowner: amendment rejected with message
 *   quote_accepted        → tradesperson: quote accepted
 *   deposit_paid          → tradesperson: deposit received
 *   magic_link            → homeowner: dashboard login link
 *   reminder_unviewed     → homeowner: 48h unviewed reminder
 *   reminder_expiry       → homeowner: 24h expiry reminder
 */
class Email_Notifications {
    use Singleton;

    public function boot(): void {
        // Hook status changes to auto-fire notifications
        add_action('cfqm_quote_status_changed', [$this, 'on_status_changed'], 20, 3);
        add_filter('wp_mail_from',              [$this, 'filter_from_address']);
        add_filter('wp_mail_from_name',         [$this, 'filter_from_name']);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Public send() entry point
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @param string $template       Template key (see class docblock).
     * @param int    $quote_id       0 if not quote-specific.
     * @param int    $query_id       0 if not query-specific.
     * @param int    $trade_id       Trade profile post ID (for direct trade notifications).
     * @param array  $extra          Extra variables to merge into the template.
     */
    public function send(string $template, int $quote_id = 0, int $query_id = 0, int $trade_id = 0, array $extra = []): bool {
        $vars = $this->build_vars($template, $quote_id, $query_id, $trade_id, $extra);
        if (!$vars) return false;

        $subject = $this->render_string($vars['subject_template'], $vars);
        $body    = $this->render_template($template, $vars);
        $to      = $vars['to'];

        if (!$to || !is_email($to)) return false;

        add_filter('wp_mail_content_type', fn() => 'text/html');
        $sent = wp_mail($to, $subject, $body);
        remove_filter('wp_mail_content_type', fn() => 'text/html');

        do_action('cfqm_email_sent', $template, $quote_id, $to, $sent);
        return $sent;
    }

    // ─────────────────────────────────────────────────────────────────────
    // Status change → auto-send
    // ─────────────────────────────────────────────────────────────────────

    public function on_status_changed(int $quote_id, string $old, string $new): void {
        switch ($new) {
            case 'viewed':
                // Optional notification — disabled by default
                break;
            case 'approved':
            case 'partial':
                $this->send('quote_accepted', $quote_id);
                break;
            case 'declined':
                $this->send('quote_declined', $quote_id);
                break;
            case 'deposit_paid':
                // Handled separately by Stripe::mark_deposit_paid() to avoid double-send
                break;
            case 'expired':
                // Handled by cron
                break;
        }
    }

    // ─────────────────────────────────────────────────────────────────────
    // Variable builders per template
    // ─────────────────────────────────────────────────────────────────────

    private function build_vars(string $tpl, int $qid, int $query_id, int $trade_id, array $extra): array {
        $brand = (string) Settings::instance()->get('brand_name', get_bloginfo('name'));
        $base  = ['brand_name' => $brand];

        switch ($tpl) {
            case 'query_received':
            case 'query_full':
            case 'query_reopened':
                $query   = get_post($query_id ?: $extra['query_id'] ?? 0);
                $trade   = get_post($trade_id);
                if (!$query || !$trade) return [];
                $t_email = (string) get_post_meta($trade->ID, '_cfqm_email', true);
                return array_merge($base, [
                    'to'               => $t_email,
                    'subject_template' => $this->subject($tpl, $brand),
                    'trade_name'       => $trade->post_title,
                    'job_desc'         => get_post_meta($query->ID, '_cfqm_hw_job_desc', true),
                    'postcode'         => get_post_meta($query->ID, '_cfqm_hw_postcode', true),
                    'query_id'         => $query->ID,
                    'dashboard_url'    => $this->trade_dashboard_url(),
                ], $extra);

            case 'quote_sent':
                if (!$qid) return [];
                $data = Quote_Builder::instance()->get_full_quote_data($qid);
                return array_merge($base, [
                    'to'               => $data['customer_email'],
                    'subject_template' => $this->subject($tpl, $brand),
                    'customer_name'    => $data['customer_name'],
                    'quote_id'         => $qid,
                    'portal_url'       => Settings::instance()->portal_url($data['portal_token']),
                    'pdf_url'          => PDF_Generator::instance()->get_download_url($qid),
                    'grand_total'      => number_format($data['grand_total'], 2),
                    'version'          => $data['version'],
                    'trade_name'       => $this->get_trade_name_for_quote($qid),
                ], $extra);

            case 'amendment_requested':
                if (!$qid) return [];
                $data      = Quote_Builder::instance()->get_full_quote_data($qid);
                $trade_email = $this->get_trade_email_for_quote($qid);
                return array_merge($base, [
                    'to'               => $trade_email,
                    'subject_template' => $this->subject($tpl, $brand),
                    'customer_name'    => $data['customer_name'],
                    'quote_id'         => $qid,
                    'iteration_used'   => $data['iteration_used'],
                    'iteration_limit'  => $data['iteration_limit'],
                    'dashboard_url'    => $this->trade_dashboard_url(),
                ], $extra);

            case 'amendment_rejected':
                if (!$qid) return [];
                $data = Quote_Builder::instance()->get_full_quote_data($qid);
                return array_merge($base, [
                    'to'               => $data['customer_email'],
                    'subject_template' => $this->subject($tpl, $brand),
                    'customer_name'    => $data['customer_name'],
                    'trade_message'    => $extra['trade_message'] ?? '',
                    'portal_url'       => Settings::instance()->portal_url($data['portal_token']),
                    'trade_name'       => $this->get_trade_name_for_quote($qid),
                ], $extra);

            case 'quote_accepted':
            case 'quote_declined':
                if (!$qid) return [];
                $data        = Quote_Builder::instance()->get_full_quote_data($qid);
                $trade_email = $this->get_trade_email_for_quote($qid);
                return array_merge($base, [
                    'to'               => $trade_email,
                    'subject_template' => $this->subject($tpl, $brand),
                    'customer_name'    => $data['customer_name'],
                    'grand_total'      => number_format($data['grand_total'], 2),
                    'dashboard_url'    => $this->trade_dashboard_url(),
                ], $extra);

            case 'deposit_paid':
                if (!$qid) return [];
                $data        = Quote_Builder::instance()->get_full_quote_data($qid);
                $trade_email = $this->get_trade_email_for_quote($qid);
                return array_merge($base, [
                    'to'               => $trade_email,
                    'subject_template' => $this->subject($tpl, $brand),
                    'customer_name'    => $data['customer_name'],
                    'deposit_amount'   => number_format($data['deposit_amount'], 2),
                    'dashboard_url'    => $this->trade_dashboard_url(),
                ], $extra);

            case 'magic_link':
                $email      = $extra['email'] ?? '';
                $magic_url  = $extra['magic_url'] ?? '';
                return array_merge($base, [
                    'to'               => $email,
                    'subject_template' => $this->subject($tpl, $brand),
                    'magic_url'        => $magic_url,
                ], $extra);

            case 'reminder_unviewed':
            case 'reminder_expiry':
                if (!$qid) return [];
                $data = Quote_Builder::instance()->get_full_quote_data($qid);
                return array_merge($base, [
                    'to'               => $data['customer_email'],
                    'subject_template' => $this->subject($tpl, $brand),
                    'customer_name'    => $data['customer_name'],
                    'portal_url'       => Settings::instance()->portal_url($data['portal_token']),
                    'validity_date'    => $data['areas'][0]['validity_date'] ?? '',
                    'trade_name'       => $this->get_trade_name_for_quote($qid),
                ], $extra);

            default:
                return [];
        }
    }

    // ─────────────────────────────────────────────────────────────────────
    // Template rendering
    // ─────────────────────────────────────────────────────────────────────

    private function render_template(string $template, array $vars): string {
        $file = CFQM_TPL . 'emails/' . $template . '.php';
        if (!file_exists($file)) {
            // Simple fallback body
            return $this->default_body($template, $vars);
        }
        extract($vars, EXTR_SKIP);
        ob_start();
        include $file;
        return ob_get_clean();
    }

    private function render_string(string $str, array $vars): string {
        foreach ($vars as $k => $v) {
            if (is_string($v)) {
                $str = str_replace('{' . $k . '}', $v, $str);
            }
        }
        return $str;
    }

    private function default_body(string $template, array $vars): string {
        $brand = esc_html($vars['brand_name'] ?? '');
        $name  = esc_html($vars['customer_name'] ?? $vars['trade_name'] ?? '');
        return "<p>Hi {$name},</p><p>" . ucwords(str_replace('_', ' ', $template)) . "</p><p>— {$brand} Team</p>";
    }

    private function subject(string $tpl, string $brand): string {
        return match ($tpl) {
            'query_received'     => "[$brand] New job enquiry — please respond promptly",
            'query_full'         => "[$brand] Quote slots filled — {job_desc}",
            'query_reopened'     => "[$brand] Job reopened — quotes needed",
            'quote_sent'         => "QUOTE RECEIVED: {trade_name} has sent you a quote",
            'amendment_requested'=> "[$brand] {customer_name} requested an amendment",
            'amendment_rejected' => "[$brand] Your amendment request has been reviewed",
            'quote_accepted'     => "[$brand] {customer_name} has accepted your quote!",
            'quote_declined'     => "[$brand] {customer_name} declined your quote",
            'deposit_paid'       => "[$brand] Deposit received from {customer_name}",
            'magic_link'         => "[$brand] Your quotes dashboard login link",
            'reminder_unviewed'  => "[$brand] Have you seen your quote? It's waiting for you",
            'reminder_expiry'    => "[$brand] Reminder: your quote expires soon",
            default              => "[$brand] Notification",
        };
    }

    // ─────────────────────────────────────────────────────────────────────
    // wp_mail filters
    // ─────────────────────────────────────────────────────────────────────

    public function filter_from_address(string $email): string {
        $configured = (string) Settings::instance()->get('email_from_address', '');
        return $configured && is_email($configured) ? $configured : $email;
    }

    public function filter_from_name(string $name): string {
        return (string) Settings::instance()->get('email_from_name', $name);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Helper fetchers
    // ─────────────────────────────────────────────────────────────────────

    private function get_trade_email_for_quote(int $quote_id): string {
        $trade_id = (int) get_post_meta($quote_id, '_cfqm_trade_id', true);
        return $trade_id ? (string) get_post_meta($trade_id, '_cfqm_email', true) : '';
    }

    private function get_trade_name_for_quote(int $quote_id): string {
        $trade_id = (int) get_post_meta($quote_id, '_cfqm_trade_id', true);
        return $trade_id ? (string) get_the_title($trade_id) : '';
    }

    private function trade_dashboard_url(): string {
        $id = (int) Settings::instance()->get('page_dashboard_trade');
        return $id ? (string) get_permalink($id) : home_url('/toolbox/');
    }
}
