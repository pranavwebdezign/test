<?php
/**
 * Email: Query Full → Late Tradesperson
 * Vars: $brand_name, $trade_name, $hw_postcode, $job_desc
 */
defined( 'ABSPATH' ) || exit;
ob_start();
?>
<h2>Job Update: Quote slots filled</h2>
<p>Hi <?php echo esc_html($trade_name); ?>,</p>
<p>The job enquiry near <strong><?php echo esc_html($hw_postcode ?? ''); ?></strong> has now received the maximum number of quotes.</p>
<p>Your profile is still active and you'll be notified of the next matching opportunity in your area.</p>
<p>If the homeowner declines all submitted quotes, the job will be reopened and you may get a second chance.</p>
<p>Best,<br>The <?php echo esc_html($brand_name); ?> Team</p>
<?php
$body_html = ob_get_clean();
include CFQM_TPL . 'emails/email-wrapper.php';
