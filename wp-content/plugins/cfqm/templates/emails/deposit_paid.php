<?php
/**
 * Email: Deposit Paid → Tradesperson
 * Vars: $brand_name, $trade_name, $customer_name, $deposit_amount, $dashboard_url
 */
defined( 'ABSPATH' ) || exit;
ob_start();
?>
<h2>💳 Deposit received from <?php echo esc_html($customer_name); ?></h2>
<p>Hi <?php echo esc_html($trade_name); ?>,</p>
<p><strong><?php echo esc_html($customer_name); ?></strong> has paid the deposit for their accepted quote.</p>
<div class="info-box">
  <p><strong>Deposit Amount:</strong> £<?php echo esc_html(number_format((float)($deposit_amount??0),2)); ?></p>
  <p><strong>Status:</strong> Payment confirmed by Stripe</p>
</div>
<p>You can view the updated quote status in your Toolbox.</p>
<p style="text-align:center"><a href="<?php echo esc_url($dashboard_url); ?>" class="btn">Go to Toolbox →</a></p>
<p>Best,<br>The <?php echo esc_html($brand_name); ?> Team</p>
<?php
$body_html = ob_get_clean();
include CFQM_TPL . 'emails/email-wrapper.php';
