<?php
/**
 * Email: Query Reopened → Tradesperson
 * Vars: $brand_name, $trade_name, $hw_postcode, $job_desc, $dashboard_url
 */
defined( 'ABSPATH' ) || exit;
ob_start();
?>
<h2>Job Reopened — Quotes Needed Again</h2>
<p>Hi <?php echo esc_html($trade_name); ?>,</p>
<p>Good news: the homeowner for the job near <strong><?php echo esc_html($hw_postcode ?? ''); ?></strong> has declined all previous quotes and the job has been reopened.</p>
<p>You can now submit your quote. Log in to your Toolbox to respond.</p>
<p style="text-align:center"><a href="<?php echo esc_url($dashboard_url); ?>" class="btn">View the Job →</a></p>
<p>Best,<br>The <?php echo esc_html($brand_name); ?> Team</p>
<?php
$body_html = ob_get_clean();
include CFQM_TPL . 'emails/email-wrapper.php';
