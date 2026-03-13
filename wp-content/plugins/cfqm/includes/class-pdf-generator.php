<?php
namespace CFQM;
defined('ABSPATH') || exit;

/**
 * PDF_Generator
 *
 * Generates branded PDF quotes inside the plugin — no Google Docs / Zapier.
 *
 * Library priority (tries each in order):
 *   1. mPDF  (via Composer: composer require mpdf/mpdf)
 *   2. FPDF  (via Composer: composer require setasign/fpdf)
 *   3. HTML fallback — outputs HTML file with print-CSS as a last resort
 *
 * PDF is saved to wp-content/uploads/cfqm-pdfs/{year}/{month}/quote-{id}-v{version}.pdf
 * A secure download URL is generated and stored in _cfqm_pdf_path.
 */
class PDF_Generator {
    use Singleton;

    const UPLOAD_SUBDIR = 'cfqm-pdfs';

    public function boot(): void {
        add_action('wp_ajax_cfqm_download_pdf',        [$this, 'ajax_download_pdf']);
        add_action('wp_ajax_nopriv_cfqm_download_pdf', [$this, 'ajax_download_pdf']);
        add_action('wp_ajax_cfqm_regenerate_pdf',      [$this, 'ajax_regenerate_pdf']);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Public generate entry point
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Generate (or regenerate) the PDF for a quote and save the path.
     * Returns the local file path on success, '' on failure.
     */
    public function generate(int $quote_id): string {
        $data    = Quote_Builder::instance()->get_full_quote_data($quote_id);
        $version = $data['version'] ?? '1.0';

        $dir  = $this->ensure_upload_dir();
        $file = $dir['path'] . "/quote-{$quote_id}-v{$version}.pdf";
        $url  = $dir['url']  . "/quote-{$quote_id}-v{$version}.pdf";

        // Try mPDF
        if (class_exists('Mpdf\\Mpdf')) {
            $this->generate_with_mpdf($data, $file);
        }
        // Try FPDF / FPDF2 (via Composer setasign/fpdf)
        elseif (class_exists('FPDF')) {
            $this->generate_with_fpdf($data, $file);
        }
        // HTML fallback
        else {
            $this->generate_html_fallback($data, $file);
        }

        if (!file_exists($file)) return '';

        update_post_meta($quote_id, '_cfqm_pdf_path', $file);
        update_post_meta($quote_id, '_cfqm_pdf_url',  $url);

        Audit_Trail::instance()->log_system(Audit_Trail::EVT_PDF_GENERATED, $quote_id, [
            'version' => $version,
            'file'    => basename($file),
        ]);

        return $file;
    }

    // ─────────────────────────────────────────────────────────────────────
    // AJAX handlers
    // ─────────────────────────────────────────────────────────────────────

    public function ajax_download_pdf(): void {
        $quote_id = (int)($_GET['quote_id'] ?? 0);
        $nonce    = sanitize_text_field($_GET['nonce'] ?? '');

        if (!wp_verify_nonce($nonce, 'cfqm_pdf_' . $quote_id)) {
            wp_die('Security check failed.', 403);
        }

        // Authorise: portal session OR logged-in owner
        $ok = false;
        if (Token_Manager::instance()->get_portal_session_quote() === $quote_id) {
            $ok = true;
        } elseif (is_user_logged_in()) {
            $post = get_post($quote_id);
            $ok   = $post && ((int)$post->post_author === get_current_user_id() || current_user_can('manage_options'));
        }

        if (!$ok) wp_die('Access denied.', 403);

        $path = (string) get_post_meta($quote_id, '_cfqm_pdf_path', true);
        if (!$path || !file_exists($path)) {
            $path = $this->generate($quote_id);
        }
        if (!$path || !file_exists($path)) {
            wp_die('PDF not available.', 404);
        }

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . basename($path) . '"');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;
    }

    public function ajax_regenerate_pdf(): void {
        check_ajax_referer('cfqm_nonce', 'nonce');
        $quote_id = (int)($_POST['quote_id'] ?? 0);
        $path     = $this->generate($quote_id);
        $path ? wp_send_json_success(['path' => $path]) : wp_send_json_error();
    }

    // ─────────────────────────────────────────────────────────────────────
    // Secure download URL helper (for embedding in emails)
    // ─────────────────────────────────────────────────────────────────────

    public function get_download_url(int $quote_id): string {
        $nonce = wp_create_nonce('cfqm_pdf_' . $quote_id);
        return add_query_arg([
            'action'   => 'cfqm_download_pdf',
            'quote_id' => $quote_id,
            'nonce'    => $nonce,
        ], admin_url('admin-ajax.php'));
    }

    // ─────────────────────────────────────────────────────────────────────
    // mPDF
    // ─────────────────────────────────────────────────────────────────────

    private function generate_with_mpdf(array $data, string $file): void {
        try {
            $mpdf = new \Mpdf\Mpdf([
                'margin_top'    => 15,
                'margin_bottom' => 15,
                'margin_left'   => 15,
                'margin_right'  => 15,
            ]);
            $html = $this->render_html($data);
            $mpdf->WriteHTML($html);
            $mpdf->Output($file, 'F');
        } catch (\Throwable $e) {
            error_log('[CFQM] mPDF error: ' . $e->getMessage());
        }
    }

    // ─────────────────────────────────────────────────────────────────────
    // FPDF (basic, no HTML support — uses cell layout)
    // ─────────────────────────────────────────────────────────────────────

    private function generate_with_fpdf(array $data, string $file): void {
        try {
            $pdf = new \FPDF();
            $pdf->AddPage();
            $pdf->SetFont('Helvetica', 'B', 16);
            $pdf->Cell(0, 10, $this->latin1($data['title'] ?? 'Quote'), 0, 1);

            $pdf->SetFont('Helvetica', '', 10);
            $pdf->Cell(0, 7, 'Version: ' . ($data['version'] ?? '1.0'), 0, 1);
            $pdf->Cell(0, 7, 'Customer: ' . $this->latin1($data['customer_name'] ?? ''), 0, 1);
            $pdf->Cell(0, 7, 'Date: ' . current_time('d M Y'), 0, 1);
            $pdf->Ln(5);

            $pdf->SetFont('Helvetica', 'B', 12);
            $pdf->Cell(0, 8, 'Areas / Scope of Work', 0, 1);
            $pdf->SetFont('Helvetica', '', 10);

            foreach ($data['areas'] ?? [] as $area) {
                $pdf->SetFont('Helvetica', 'B', 10);
                $pdf->Cell(120, 7, $this->latin1($area['name'] ?? ''));
                $pdf->Cell(0, 7, 'GBP ' . number_format((float)$area['price'], 2), 0, 1);
                $pdf->SetFont('Helvetica', '', 9);
                $pdf->MultiCell(0, 5, $this->latin1(strip_tags($area['scope'] ?? '')));
                $pdf->Ln(2);
            }

            $pdf->SetFont('Helvetica', 'B', 11);
            $pdf->Ln(4);
            $pdf->Cell(120, 7, 'Sub-total');
            $pdf->Cell(0, 7, 'GBP ' . number_format((float)$data['subtotal'],    2), 0, 1);
            if (!empty($data['vat_enabled'])) {
                $pdf->Cell(120, 7, 'VAT');
                $pdf->Cell(0, 7, 'GBP ' . number_format((float)$data['vat_amount'], 2), 0, 1);
            }
            $pdf->Cell(120, 7, 'Grand Total');
            $pdf->Cell(0, 7, 'GBP ' . number_format((float)$data['grand_total'],  2), 0, 1);
            if (!empty($data['deposit_amount'])) {
                $pdf->Cell(120, 7, 'Deposit Required');
                $pdf->Cell(0, 7, 'GBP ' . number_format((float)$data['deposit_amount'], 2), 0, 1);
            }

            $pdf->Output('F', $file);
        } catch (\Throwable $e) {
            error_log('[CFQM] FPDF error: ' . $e->getMessage());
        }
    }

    // ─────────────────────────────────────────────────────────────────────
    // HTML fallback (saved as .pdf extension but actually HTML+print-CSS)
    // ─────────────────────────────────────────────────────────────────────

    private function generate_html_fallback(array $data, string $file): void {
        file_put_contents($file, $this->render_html($data));
    }

    // ─────────────────────────────────────────────────────────────────────
    // HTML template
    // ─────────────────────────────────────────────────────────────────────

    private function render_html(array $data): string {
        $logo_id  = (int) Settings::instance()->get('brand_logo_id', 0);
        $logo_src = $logo_id ? wp_get_attachment_image_src($logo_id, 'medium')[0] ?? '' : '';
        $brand    = esc_html(Settings::instance()->get('brand_name', ''));
        $accent   = esc_attr(Settings::instance()->get('brand_accent_colour', '#1a5276'));

        ob_start();
        include CFQM_TPL . 'pdf/quote-pdf.php';
        return ob_get_clean();
    }

    // ─────────────────────────────────────────────────────────────────────
    // Utilities
    // ─────────────────────────────────────────────────────────────────────

    private function ensure_upload_dir(): array {
        $uploads  = wp_upload_dir();
        $subpath  = self::UPLOAD_SUBDIR . '/' . gmdate('Y') . '/' . gmdate('m');
        $path     = $uploads['basedir'] . '/' . $subpath;
        $url      = $uploads['baseurl'] . '/' . $subpath;

        if (!is_dir($path)) {
            wp_mkdir_p($path);
            file_put_contents($path . '/.htaccess', "Options -Indexes\nDeny from all\n");
        }

        return ['path' => $path, 'url' => $url];
    }

    /** Convert UTF-8 string to Latin-1 for FPDF (which doesn't support UTF-8 natively). */
    private function latin1(string $s): string {
        return mb_convert_encoding($s, 'ISO-8859-1', 'UTF-8');
    }
}
