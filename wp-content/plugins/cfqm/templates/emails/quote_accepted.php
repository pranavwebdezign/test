<?php
/**
 * Email: Quote Accepted → Tradesperson
 * Vars: $brand_name, $trade_name, $customer_name, $grand_total, $deposit_amount, $stripe_link, $dashboard_url
 */
defined( 'ABSPATH' ) || exit;
ob_start();
?>
<h2>🎉 Quote accepted by <?php echo esc_html($customer_name); ?></h2>
<p>Hi <?php echo esc_html($trade_name); ?>,</p>
<p>Great news! <strong><?php echo esc_html($customer_name); ?></strong> has accepted your quote.</p>
<div class="info-box">
  <p><strong>Quote Total:</strong> £<?php echo esc_html(number_format((float)($grand_total??0),2)); ?></p>
  <?php if (!empty($deposit_amount) && (float)$deposit_amount > 0) : ?>
  <p><strong>Deposit Required:</strong> £<?php echo esc_html(number_format((float)$deposit_amount,2)); ?></p>
  <?php endif; ?>
</div>
<?php if (!empty($stripe_link)) : ?>
<p>A Stripe payment link for the deposit has been created. Share it with your customer or find it in your Toolbox.</p>
<p style="text-align:center"><a href="<?php echo esc_url($stripe_link); ?>" class="btn">View Payment Link →</a></p>
<?php endif; ?>
<p><a href="<?php echo esc_url($dashboard_url); ?>">Log in to your Toolbox</a> to download the signed PDF and manage this project.</p>
<p>Best,<br>The <?php echo esc_html($brand_name); ?> Team</p>
<?php
$body_html = ob_get_clean();
include CFQM_TPL . 'emails/email-wrapper.php';
