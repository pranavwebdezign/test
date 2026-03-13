<?php
/**
 * Email: Amendment Rejected → Homeowner
 * Vars: $brand_name, $customer_name, $trade_name, $trade_message, $portal_url
 */
defined( 'ABSPATH' ) || exit;
ob_start();
?>
<h2>Your amendment request has been reviewed</h2>
<p>Hi <?php echo esc_html($customer_name); ?>,</p>
<p><strong><?php echo esc_html($trade_name); ?></strong> has reviewed your amendment request and has responded.</p>
<?php if (!empty($trade_message)) : ?>
<div class="info-box">
  <p><strong>Message from <?php echo esc_html($trade_name); ?>:</strong></p>
  <p><?php echo nl2br(esc_html($trade_message)); ?></p>
</div>
<?php endif; ?>
<p>The original quote remains available. You can still accept, decline, or request further changes (if iterations remain) from your quote portal.</p>
<p style="text-align:center"><a href="<?php echo esc_url($portal_url); ?>" class="btn">View My Quote →</a></p>
<p>Best,<br>The <?php echo esc_html($brand_name); ?> Team</p>
<?php
$body_html = ob_get_clean();
include CFQM_TPL . 'emails/email-wrapper.php';
