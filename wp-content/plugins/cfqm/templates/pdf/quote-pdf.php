<?php
/**
 * Template: Quote PDF (HTML layout)
 * Variables: $data (array) – full quote data from Quote_Builder::get_full_quote_data()
 *
 * Rendered to PDF by PDF_Generator via FPDF/DOMPDF or printed as HTML fallback.
 */
defined( 'ABSPATH' ) || exit;

$brand     = CFQM\Settings::instance()->get('brand_name','Fixdly');
$logo_id   = (int) CFQM\Settings::instance()->get('brand_logo_id');
$logo_src  = $logo_id ? get_attached_file($logo_id) : '';
$logo_url  = $logo_id ? wp_get_attachment_image_url($logo_id,'medium') : '';

$areas     = $data['areas']    ?? [];
$subtotal  = (float)($data['subtotal']        ?? 0);
$vat_amt   = (float)($data['vat_amount']      ?? 0);
$grand     = (float)($data['grand_total']     ?? 0);
$deposit   = (float)($data['deposit_amount']  ?? 0);
$vat_en    = !empty($data['vat_enabled']);
$dep_pct   = (float)($data['deposit_percent'] ?? 0);
$validity  = $data['quote_validity']          ?? '';
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Quote – <?php echo esc_html($data['customer_name'] ?? ''); ?></title>
<style>
  * { margin:0; padding:0; box-sizing:border-box; font-family:Arial, sans-serif; font-size:11pt; color:#222; }
  body { padding:24pt; }

  /* ── Header ────────────────────────────────────────────── */
  .pdf-header { display:flex; justify-content:space-between; align-items:flex-start; border-bottom:3pt solid #1a5276; padding-bottom:16pt; margin-bottom:20pt; }
  .pdf-header__logo img { max-height:56pt; max-width:180pt; }
  .pdf-header__info { text-align:right; }
  .pdf-header__brand { font-size:18pt; font-weight:bold; color:#1a5276; }
  .pdf-header__sub { color:#666; font-size:9pt; margin-top:4pt; }

  /* ── Meta ──────────────────────────────────────────────── */
  .pdf-meta { display:flex; gap:24pt; margin-bottom:20pt; }
  .pdf-meta__col { flex:1; background:#f5f7fa; padding:12pt; border-radius:4pt; }
  .pdf-meta__col h4 { font-size:9pt; text-transform:uppercase; color:#666; margin-bottom:6pt; letter-spacing:.5pt; }
  .pdf-meta__col p { line-height:1.5; }

  /* ── Version / Status bar ──────────────────────────────── */
  .pdf-version-bar { background:#1a5276; color:#fff; padding:6pt 12pt; margin-bottom:20pt; font-size:10pt; display:flex; gap:24pt; }
  .pdf-version-bar span { }

  /* ── Areas table ───────────────────────────────────────── */
  .pdf-areas-table { width:100%; border-collapse:collapse; margin-bottom:20pt; }
  .pdf-areas-table th { background:#1a5276; color:#fff; padding:8pt 10pt; text-align:left; font-size:9pt; }
  .pdf-areas-table td { padding:8pt 10pt; border-bottom:1pt solid #e8e8e8; vertical-align:top; }
  .pdf-areas-table tr:nth-child(even) td { background:#f9fbfd; }
  .pdf-areas-table .area-scope { font-size:9pt; color:#555; margin-top:4pt; line-height:1.5; }
  .pdf-areas-table .area-type { display:inline-block; padding:1pt 5pt; border-radius:3pt; font-size:8pt; font-weight:bold; }
  .pdf-areas-table .area-type--included { background:#d5f5e3; color:#1e6e42; }
  .pdf-areas-table .area-type--optional  { background:#d6eaf8; color:#1a5276; }

  /* ── Totals ────────────────────────────────────────────── */
  .pdf-totals { float:right; width:220pt; margin-bottom:32pt; }
  .pdf-totals table { width:100%; border-collapse:collapse; }
  .pdf-totals td { padding:5pt 8pt; border-bottom:1pt solid #eee; }
  .pdf-totals td:last-child { text-align:right; font-weight:bold; }
  .pdf-totals .grand-total td { font-size:12pt; color:#1a5276; border-top:2pt solid #1a5276; border-bottom:none; }

  /* ── Terms / Notes ─────────────────────────────────────── */
  .pdf-terms { clear:both; border-top:1pt solid #ddd; padding-top:16pt; font-size:9pt; color:#666; margin-bottom:20pt; }
  .pdf-terms h4 { color:#333; margin-bottom:4pt; }

  /* ── E-signature block ─────────────────────────────────── */
  .pdf-esign { border:1pt solid #ccc; padding:16pt; margin-bottom:20pt; }
  .pdf-esign h4 { margin-bottom:12pt; }
  .pdf-esign__row { display:flex; gap:32pt; margin-bottom:16pt; }
  .pdf-esign__field { flex:1; }
  .pdf-esign__field label { font-size:9pt; color:#888; display:block; margin-bottom:4pt; }
  .pdf-esign__field .esign-line { border-bottom:1pt solid #333; height:20pt; width:100%; }

  /* ── Footer ────────────────────────────────────────────── */
  .pdf-footer { border-top:1pt solid #ddd; padding-top:10pt; font-size:8pt; color:#999; text-align:center; margin-top:32pt; }
</style>
</head>
<body>

<!-- ── HEADER ─────────────────────────────────────────────────────── -->
<div class="pdf-header">
  <div class="pdf-header__logo">
    <?php if ($logo_url) : ?>
      <img src="<?php echo esc_attr($logo_url); ?>" alt="<?php echo esc_attr($brand); ?>">
    <?php else : ?>
      <span class="pdf-header__brand"><?php echo esc_html($brand); ?></span>
    <?php endif; ?>
  </div>
  <div class="pdf-header__info">
    <div class="pdf-header__brand">QUOTE</div>
    <div class="pdf-header__sub">
      Ref: #<?php echo esc_html($data['quote_id'] ?? ''); ?><br>
      Issued: <?php echo esc_html(date('d F Y', strtotime($data['sent_at'] ?? 'now'))); ?><br>
      <?php if ($validity) echo 'Valid until: ' . esc_html(date('d F Y', strtotime($validity))); ?>
    </div>
  </div>
</div>

<!-- ── VERSION BAR ─────────────────────────────────────────────────── -->
<div class="pdf-version-bar">
  <span><strong>Version</strong> v<?php echo esc_html($data['version'] ?? '1.0'); ?></span>
  <span><strong>Status</strong> <?php echo esc_html(ucfirst(str_replace('_',' ',$data['status']??'draft'))); ?></span>
  <?php if ($data['iteration_limit']??0) : ?>
    <span><strong>Iterations</strong> <?php echo esc_html(($data['iteration_used']??0).'/'.($data['iteration_limit']??0)); ?></span>
  <?php endif; ?>
</div>

<!-- ── META COLUMNS ────────────────────────────────────────────────── -->
<div class="pdf-meta">
  <div class="pdf-meta__col">
    <h4><?php esc_html_e('Prepared For','cfqm'); ?></h4>
    <p><strong><?php echo esc_html($data['customer_name'] ?? ''); ?></strong><br>
       <?php echo esc_html($data['customer_email'] ?? ''); ?>
       <?php if (!empty($data['customer_phone'])) echo '<br>'.esc_html($data['customer_phone']); ?>
    </p>
  </div>
  <div class="pdf-meta__col">
    <h4><?php esc_html_e('Prepared By','cfqm'); ?></h4>
    <p><strong><?php echo esc_html($data['trade_name'] ?? $brand); ?></strong>
       <?php if (!empty($data['trade_email'])) echo '<br>'.esc_html($data['trade_email']); ?>
       <?php if (!empty($data['trade_phone'])) echo '<br>'.esc_html($data['trade_phone']); ?>
       <?php if (!empty($data['trade_address'])) echo '<br>'.nl2br(esc_html($data['trade_address'])); ?>
    </p>
  </div>
  <?php if (!empty($data['project_label'])) : ?>
  <div class="pdf-meta__col">
    <h4><?php esc_html_e('Project','cfqm'); ?></h4>
    <p><?php echo esc_html($data['project_label']); ?></p>
  </div>
  <?php endif; ?>
</div>

<!-- ── AREAS TABLE ─────────────────────────────────────────────────── -->
<table class="pdf-areas-table">
  <thead>
    <tr>
      <th style="width:25%"><?php esc_html_e('Area / Room','cfqm'); ?></th>
      <th style="width:35%"><?php esc_html_e('Scope of Work','cfqm'); ?></th>
      <th style="width:10%"><?php esc_html_e('Type','cfqm'); ?></th>
      <th style="width:15%"><?php esc_html_e('Valid Until','cfqm'); ?></th>
      <th style="width:15%; text-align:right"><?php esc_html_e('Price','cfqm'); ?></th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($areas as $area) : ?>
    <tr>
      <td><strong><?php echo esc_html($area['name']); ?></strong></td>
      <td>
        <?php echo nl2br(esc_html($area['scope_text']??'')); ?>
        <?php if (!empty($area['materials'])) echo '<div class="area-scope"><em>Materials: '.esc_html($area['materials']).'</em></div>'; ?>
        <?php if (!empty($area['labour_hours'])) echo '<div class="area-scope"><em>Est. labour: '.esc_html($area['labour_hours']).' hrs</em></div>'; ?>
      </td>
      <td><span class="area-type area-type--<?php echo esc_attr($area['area_type']??'included'); ?>">
        <?php echo esc_html(ucfirst($area['area_type']??'Included')); ?>
      </span></td>
      <td><?php echo !empty($area['validity_date']) ? esc_html(date('d M Y',strtotime($area['validity_date']))) : '—'; ?></td>
      <td style="text-align:right">£<?php echo esc_html(number_format((float)($area['price']??0),2)); ?></td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<!-- ── TOTALS ──────────────────────────────────────────────────────── -->
<div class="pdf-totals">
  <table>
    <tr><td><?php esc_html_e('Subtotal','cfqm'); ?></td><td>£<?php echo esc_html(number_format($subtotal,2)); ?></td></tr>
    <?php if ($vat_en) : ?>
    <tr><td><?php printf(esc_html__('VAT (%s%%)','cfqm'),(float)($data['vat_rate']??20)); ?></td><td>£<?php echo esc_html(number_format($vat_amt,2)); ?></td></tr>
    <?php endif; ?>
    <tr class="grand-total">
      <td><strong><?php esc_html_e('Grand Total','cfqm'); ?></strong></td>
      <td>£<?php echo esc_html(number_format($grand,2)); ?></td>
    </tr>
    <?php if ($deposit > 0) : ?>
    <tr><td><?php printf(esc_html__('Deposit Required (%s%%)','cfqm'),$dep_pct); ?></td><td>£<?php echo esc_html(number_format($deposit,2)); ?></td></tr>
    <?php endif; ?>
  </table>
</div>

<!-- ── TERMS ───────────────────────────────────────────────────────── -->
<div class="pdf-terms">
  <h4><?php esc_html_e('Terms & Notes','cfqm'); ?></h4>
  <p><?php printf(
    esc_html__('This quote is prepared by %s. Prices shown are valid until the expiry dates listed against each area. Additional work outside this scope will require a separate agreement. Deposit (where applicable) is payable to confirm booking.','cfqm'),
    esc_html($data['trade_name'] ?? $brand)
  ); ?></p>
</div>

<!-- ── E-SIGNATURE ─────────────────────────────────────────────────── -->
<?php
$sig   = $data['signature'] ?? '';
$sigat = $data['signed_at'] ?? '';
?>
<div class="pdf-esign">
  <h4><?php esc_html_e('Acceptance & E-Signature','cfqm'); ?></h4>
  <?php if ($sig && $sigat) : ?>
    <p style="margin-bottom:12pt;color:#1e6e42"><strong>✓ <?php esc_html_e('Accepted','cfqm'); ?></strong></p>
    <div class="pdf-esign__row">
      <div class="pdf-esign__field">
        <label><?php esc_html_e('Customer Signature','cfqm'); ?></label>
        <div class="esign-line"><?php echo esc_html($sig); ?></div>
      </div>
      <div class="pdf-esign__field">
        <label><?php esc_html_e('Date','cfqm'); ?></label>
        <div class="esign-line"><?php echo esc_html(date('d F Y',strtotime($sigat))); ?></div>
      </div>
    </div>
  <?php else : ?>
    <div class="pdf-esign__row">
      <div class="pdf-esign__field">
        <label><?php esc_html_e('Customer Signature','cfqm'); ?></label>
        <div class="esign-line"></div>
      </div>
      <div class="pdf-esign__field">
        <label><?php esc_html_e('Date','cfqm'); ?></label>
        <div class="esign-line"></div>
      </div>
    </div>
  <?php endif; ?>
</div>

<!-- ── FOOTER ──────────────────────────────────────────────────────── -->
<div class="pdf-footer">
  <?php printf(
    esc_html__('Quote generated by %s · %s · Document reference: Quote #%s v%s','cfqm'),
    esc_html($brand),
    esc_html(date('d M Y')),
    esc_html($data['quote_id'] ?? ''),
    esc_html($data['version'] ?? '1.0')
  ); ?>
</div>

</body>
</html>
