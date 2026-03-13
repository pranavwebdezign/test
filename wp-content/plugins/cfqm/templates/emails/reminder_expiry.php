<?php
/**
 * Email: 24h Expiry Reminder → Homeowner
 * Vars: $brand_name, $customer_name, $trade_name, $portal_url, $validity_date
 */
defined( 'ABSPATH' ) || exit;
ob_start();
?>
<h2>⚠️ Your quote expires tomorrow</h2>
<p>Hi <?php echo esc_html($customer_name); ?>,</p>
<p>Your quote from <strong><?php echo esc_html($trade_name); ?></strong> is set to expire on <strong><?php echo $validity_date ? esc_html(date('d F Y',strtotime($validity_date))) : 'tomorrow'; ?></strong>.</p>
<p>Act now to secure the prices in your quote before it expires. Once expired, the tradesperson may revise their pricing.</p>
<p style="text-align:center"><a href="<?php echo esc_url($portal_url); ?>" class="btn">View & Accept Quote →</a></p>
<p>Best,<br>The <?php echo esc_html($brand_name); ?> Team</p>
<?php
$body_html = ob_get_clean();
include CFQM_TPL . 'emails/email-wrapper.php';
