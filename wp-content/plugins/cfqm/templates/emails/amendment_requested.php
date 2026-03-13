<?php
/**
 * Email: Amendment Requested → Tradesperson
 * Vars: $brand_name, $trade_name, $customer_name, $amendment_message, $iteration_used, $iteration_limit, $dashboard_url
 */
defined( 'ABSPATH' ) || exit;
ob_start();
?>
<h2>Amendment request from <?php echo esc_html($customer_name); ?></h2>
<p>Hi <?php echo esc_html($trade_name); ?>,</p>
<p><strong><?php echo esc_html($customer_name); ?></strong> has reviewed your quote and would like to request some changes.</p>
<?php if (!empty($amendment_message)) : ?>
<div class="info-box">
  <p><strong>Their message:</strong></p>
  <p><?php echo nl2br(esc_html($amendment_message)); ?></p>
</div>
<?php endif; ?>
<p>Iteration <?php echo esc_html($iteration_used ?? 1); ?> of <?php echo esc_html($iteration_limit ?? 5); ?> used.</p>
<p>Log in to your Toolbox to review the request and either reissue the quote, reject with a message, or approve as-is.</p>
<p style="text-align:center"><a href="<?php echo esc_url($dashboard_url); ?>" class="btn">Review Amendment →</a></p>
<p>Best,<br>The <?php echo esc_html($brand_name); ?> Team</p>
<?php
$body_html = ob_get_clean();
include CFQM_TPL . 'emails/email-wrapper.php';
