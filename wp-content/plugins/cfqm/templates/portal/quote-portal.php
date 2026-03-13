<?php
/**
 * Template: Customer Quote Portal
 *
 * Variables available:
 *   $data     (array)  – full quote data from Quote_Builder::get_full_quote_data()
 *   $timeline (array)  – event log from Audit_Trail::get_quote_timeline()
 *   $status   (string) – current quote status
 *   $quote_id (int)
 */
defined('ABSPATH') || exit;

$brand        = esc_html(CFQM\Settings::instance()->get('brand_name'));
$trade_name   = '';
$tid = (int) get_post_meta($quote_id, '_cfqm_trade_id', true);
if ($tid) $trade_name = esc_html(get_the_title($tid));

$iteration_used  = (int)($data['iteration_used']  ?? 0);
$iteration_limit = (int)($data['iteration_limit'] ?? 5);
$can_amend       = ($iteration_used < $iteration_limit)
    && in_array($status, ['sent','viewed'], true);

$areas     = $data['areas'] ?? [];
$terminal  = in_array($status, ['approved','partial','declined','deposit_paid'], true);

$pdf_url = CFQM\PDF_Generator::instance()->get_download_url($quote_id);
?>
<div class="cfqm-portal" id="cfqm-portal">
    <?php // ── Header ──────────────────────────────────────────────── ?>
    <div class="cfqm-portal__header">
        <?php
        $logo_id = (int) CFQM\Settings::instance()->get('brand_logo_id');
        if ($logo_id): ?>
            <img src="<?php echo esc_url(wp_get_attachment_image_url($logo_id, 'medium')); ?>"
                 alt="<?php echo $brand; ?>" class="cfqm-portal__logo">
        <?php endif; ?>
        <div class="cfqm-portal__header-info">
            <h1 class="cfqm-portal__title">
                Quote from <?php echo $trade_name ?: $brand; ?>
            </h1>
            <p class="cfqm-portal__meta">
                <span class="cfqm-badge cfqm-badge--<?php echo esc_attr($status); ?>">
                    <?php echo esc_html(ucwords(str_replace('_', ' ', $status))); ?>
                </span>
                Version <?php echo esc_html($data['version'] ?? '1.0'); ?>
                &nbsp;·&nbsp;
                Prepared for <strong><?php echo esc_html($data['customer_name']); ?></strong>
                &nbsp;·&nbsp;
                <?php echo esc_html(date('d M Y', strtotime($data['sent_at'] ?? 'now'))); ?>
            </p>
        </div>
        <a href="<?php echo esc_url($pdf_url); ?>" class="cfqm-btn cfqm-btn--outline" target="_blank">
            ⬇ Download PDF
        </a>
    </div>

    <?php // ── Status banners ───────────────────────────────────────── ?>
    <?php if ($status === 'pending_amendment'): ?>
        <div class="cfqm-alert cfqm-alert--info">
            Your amendment request has been received (<?php echo $iteration_used; ?>/<?php echo $iteration_limit; ?> used).
            The tradesperson will respond shortly.
        </div>
    <?php elseif ($status === 'approved' || $status === 'partial'): ?>
        <div class="cfqm-alert cfqm-alert--success">
            ✓ You have accepted this quote.
            <?php if (!empty($data['stripe_link'])): ?>
                <a href="<?php echo esc_url($data['stripe_link']); ?>" class="cfqm-btn cfqm-btn--primary" style="margin-left:1rem;">
                    Pay Deposit (£<?php echo number_format($data['deposit_amount'], 2); ?>)
                </a>
            <?php endif; ?>
        </div>
    <?php elseif ($status === 'deposit_paid'): ?>
        <div class="cfqm-alert cfqm-alert--success">✓ Deposit paid. The tradesperson has been notified.</div>
    <?php elseif ($status === 'declined'): ?>
        <div class="cfqm-alert cfqm-alert--warning">You have declined this quote.</div>
    <?php elseif ($status === 'expired'): ?>
        <div class="cfqm-alert cfqm-alert--danger">This quote has expired. Please request a new one.</div>
    <?php endif; ?>

    <?php // ── Areas ────────────────────────────────────────────────── ?>
    <div class="cfqm-portal__areas">
        <h2>Scope of Work</h2>

        <?php if (!$terminal): ?>
            <p class="cfqm-help-text">
                <strong>Included areas</strong> are required and pre-selected.
                <strong>Optional areas</strong> can be selected or deselected.
                Your payable total updates automatically.
            </p>
        <?php endif; ?>

        <form id="cfqm-portal-form" class="cfqm-areas-form">
            <?php foreach ($areas as $area):
                $is_optional = ($area['area_type'] === 'optional');
                $checked     = 'checked';
                ?>
                <div class="cfqm-area <?php echo $is_optional ? 'cfqm-area--optional' : 'cfqm-area--included'; ?>"
                     data-price="<?php echo esc_attr($area['price']); ?>">

                    <div class="cfqm-area__header">
                        <?php if (!$terminal): ?>
                            <label class="cfqm-area__checkbox-label">
                                <input type="checkbox"
                                       name="selected_areas[]"
                                       value="<?php echo esc_attr($area['id']); ?>"
                                       <?php echo $checked; ?>
                                       <?php echo !$is_optional ? 'disabled' : ''; ?>>
                                <span class="cfqm-area__name"><?php echo esc_html($area['name']); ?></span>
                            </label>
                        <?php else: ?>
                            <span class="cfqm-area__name"><?php echo esc_html($area['name']); ?></span>
                        <?php endif; ?>

                        <span class="cfqm-area__type-badge <?php echo $is_optional ? 'optional' : 'included'; ?>">
                            <?php echo $is_optional ? 'Optional' : 'Included'; ?>
                        </span>
                        <span class="cfqm-area__price">
                            £<?php echo number_format($area['price'], 2); ?>
                        </span>
                    </div>

                    <?php if ($area['scope']): ?>
                        <div class="cfqm-area__scope">
                            <?php echo wp_kses_post($area['scope']); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($area['materials']): ?>
                        <div class="cfqm-area__materials">
                            <strong>Materials:</strong> <?php echo esc_html($area['materials']); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($area['validity_date']): ?>
                        <div class="cfqm-area__validity">
                            Valid until: <?php echo esc_html(date('d M Y', strtotime($area['validity_date']))); ?>
                        </div>
                    <?php endif; ?>

                    <?php
                    // Area photos
                    $photo_ids = is_array($area['photo_ids']) ? $area['photo_ids'] : json_decode($area['photo_ids'] ?? '[]', true);
                    if (!empty($photo_ids)): ?>
                        <div class="cfqm-area__photos">
                            <?php foreach ($photo_ids as $photo_id): ?>
                                <a href="<?php echo esc_url(wp_get_attachment_url($photo_id)); ?>" target="_blank">
                                    <?php echo wp_get_attachment_image($photo_id, 'thumbnail'); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </form>
    </div>

    <?php // ── Totals ────────────────────────────────────────────────── ?>
    <div class="cfqm-portal__totals" id="cfqm-totals">
        <div class="cfqm-totals-table">
            <div class="cfqm-totals-row">
                <span>Subtotal</span>
                <span id="cfqm-display-subtotal">£<?php echo number_format($data['subtotal'], 2); ?></span>
            </div>
            <?php if ($data['vat_enabled']): ?>
                <div class="cfqm-totals-row">
                    <span>VAT (<?php echo esc_html($data['vat_rate']); ?>%)</span>
                    <span id="cfqm-display-vat">£<?php echo number_format($data['vat_amount'], 2); ?></span>
                </div>
            <?php endif; ?>
            <div class="cfqm-totals-row cfqm-totals-row--total">
                <span>Total</span>
                <span id="cfqm-display-total"><strong>£<?php echo number_format($data['grand_total'], 2); ?></strong></span>
            </div>
            <?php if ($data['deposit_type'] !== 'none' && $data['deposit_amount'] > 0): ?>
                <div class="cfqm-totals-row cfqm-totals-row--deposit">
                    <span>Deposit Required (<?php echo esc_html($data['deposit_percent']); ?>%)</span>
                    <span id="cfqm-display-deposit">£<?php echo number_format($data['deposit_amount'], 2); ?></span>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php // ── Actions ─────────────────────────────────────────────── ?>
    <?php if (!$terminal): ?>
        <div class="cfqm-portal__actions">

            <?php // E-signature ?>
            <div class="cfqm-esign-block" id="cfqm-esign-block">
                <h3>Accept & E-Sign</h3>
                <p>By typing your name below, you confirm acceptance of the selected areas and their scope of work.</p>
                <div class="cfqm-esign-inputs">
                    <input type="text" id="cfqm-signature" placeholder="Type your full name"
                           class="cfqm-input cfqm-input--signature">
                    <input type="date" id="cfqm-sig-date" value="<?php echo date('Y-m-d'); ?>"
                           class="cfqm-input">
                    <label class="cfqm-checkbox-label">
                        <input type="checkbox" id="cfqm-sig-confirm">
                        I confirm I have read the scope of work for each selected area.
                    </label>
                </div>
                <div class="cfqm-action-buttons">
                    <button type="button" id="cfqm-btn-accept" class="cfqm-btn cfqm-btn--success">
                        ✓ Accept Quote
                    </button>
                    <button type="button" id="cfqm-btn-decline" class="cfqm-btn cfqm-btn--danger">
                        ✗ Decline
                    </button>
                </div>
            </div>

            <?php // Amendment request ?>
            <?php if ($can_amend): ?>
                <div class="cfqm-amendment-block" id="cfqm-amendment-block">
                    <h3>Request Amendment
                        <small>(<?php echo $iteration_used; ?>/<?php echo $iteration_limit; ?> used)</small>
                    </h3>
                    <p>Tick the areas you'd like to discuss and add a note for the tradesperson.</p>

                    <div class="cfqm-amendment-areas">
                        <?php foreach ($areas as $area):
                            if ($area['area_type'] !== 'optional') continue; ?>
                            <label class="cfqm-checkbox-label">
                                <input type="checkbox" name="amendment_areas[]"
                                       value="<?php echo esc_attr($area['id']); ?>"
                                       class="cfqm-amendment-area-check">
                                <?php echo esc_html($area['name']); ?>
                                (£<?php echo number_format($area['price'], 2); ?>)
                            </label>
                        <?php endforeach; ?>
                    </div>

                    <textarea id="cfqm-amendment-message" class="cfqm-textarea"
                              placeholder="Describe your amendment request…" rows="4"></textarea>

                    <button type="button" id="cfqm-btn-amend" class="cfqm-btn cfqm-btn--secondary">
                        Submit Amendment Request
                    </button>
                </div>
            <?php elseif ($iteration_used >= $iteration_limit): ?>
                <div class="cfqm-alert cfqm-alert--warning">
                    Amendment limit reached (<?php echo $iteration_used; ?>/<?php echo $iteration_limit; ?>).
                    Please contact the tradesperson directly to discuss further changes.
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php // ── Event timeline ───────────────────────────────────────── ?>
    <?php if (!empty($timeline)): ?>
        <div class="cfqm-portal__timeline">
            <h3>Quote Activity</h3>
            <ul class="cfqm-timeline">
                <?php foreach ($timeline as $evt): ?>
                    <li class="cfqm-timeline__item cfqm-timeline__item--<?php echo esc_attr($evt->event_type); ?>">
                        <span class="cfqm-timeline__label">
                            <?php echo esc_html(CFQM\Audit_Trail::instance()->event_label($evt->event_type)); ?>
                        </span>
                        <span class="cfqm-timeline__date">
                            <?php echo esc_html(date('d M Y H:i', strtotime($evt->created_at))); ?>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

</div><!-- .cfqm-portal -->

<script type="text/html" id="cfqm-quote-data">
    <?php echo wp_json_encode([
        'quoteId'    => $quote_id,
        'vatEnabled' => (bool)$data['vat_enabled'],
        'vatRate'    => (float)$data['vat_rate'],
        'depositPct' => (float)$data['deposit_percent'],
    ]); ?>
</script>
