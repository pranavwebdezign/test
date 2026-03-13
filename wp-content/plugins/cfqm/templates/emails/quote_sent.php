<?php
/**
 * Email: Quote Sent → Homeowner
 * Vars: $brand_name, $trade_name, $customer_name, $portal_url, $quote_title, $areas_summary
 */
defined( 'ABSPATH' ) || exit;
ob_start();
?>
<h2>You have a new quote from <?php echo esc_html($trade_name); ?></h2>
<p>Hi <?php echo esc_html($customer_name); ?>,</p>
<p><strong><?php echo esc_html($trade_name); ?></strong> has prepared a detailed quote for your request through <?php echo esc_html($brand_name); ?>.</p>
<p>Click the button below to review your quote, see the full scope of work per area, and accept or discuss changes.</p>
<p style="text-align:center"><a href="<?php echo esc_url($portal_url); ?>" class="btn">View My Quote →</a></p>
<div class="info-box">
  <p><strong>What to expect:</strong></p>
  <p>✔ A full breakdown of work by area/room<br>
     ✔ Pricing per area with a clear scope of work<br>
     ✔ Option to accept, partially accept, or request changes</p>
</div>
<p>If you have questions, you can request amendments directly from the portal — no account required.</p>
<p>Best,<br>The <?php echo esc_html($brand_name); ?> Team</p>
<?php
$body_html = ob_get_clean();
$subject   = $subject ?? 'Your Quote';
include CFQM_TPL . 'emails/email-wrapper.php';
