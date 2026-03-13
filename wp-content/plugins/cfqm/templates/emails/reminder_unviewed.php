<?php
/**
 * Email: 48h Unviewed Reminder → Homeowner
 * Vars: $brand_name, $customer_name, $trade_name, $portal_url
 */
defined( 'ABSPATH' ) || exit;
ob_start();
?>
<h2>You have a quote waiting for you</h2>
<p>Hi <?php echo esc_html($customer_name); ?>,</p>
<p>Just a friendly reminder — <strong><?php echo esc_html($trade_name); ?></strong> sent you a quote 2 days ago and it's waiting for your review.</p>
<p>Click below to view the full breakdown and either accept, request changes, or decline.</p>
<p style="text-align:center"><a href="<?php echo esc_url($portal_url); ?>" class="btn">View My Quote →</a></p>
<p>Best,<br>The <?php echo esc_html($brand_name); ?> Team</p>
<?php
$body_html = ob_get_clean();
include CFQM_TPL . 'emails/email-wrapper.php';
