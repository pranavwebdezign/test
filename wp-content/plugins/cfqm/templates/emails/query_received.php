<?php
/**
 * Email: Query Received → Tradesperson
 * Vars: $brand_name, $trade_name, $hw_name, $hw_postcode, $job_desc, $category_name, $dashboard_url
 */
defined( 'ABSPATH' ) || exit;
ob_start();
?>
<h2>New job enquiry near <?php echo esc_html($hw_postcode ?? ''); ?></h2>
<p>Hi <?php echo esc_html($trade_name); ?>,</p>
<p>A homeowner in your area has submitted a job enquiry via <strong><?php echo esc_html($brand_name); ?></strong>. Log in to your Toolbox to review it and send your quote.</p>
<div class="info-box">
  <p><strong>Job Summary</strong></p>
  <p><strong>Category:</strong> <?php echo esc_html($category_name ?? ''); ?></p>
  <p><strong>Location:</strong> <?php echo esc_html($hw_postcode ?? ''); ?></p>
  <p><strong>Description:</strong> <?php echo esc_html(wp_trim_words($job_desc ?? '', 30)); ?></p>
</div>
<p><strong>⚡ Act fast:</strong> Only the first <?php echo esc_html($response_cap ?? 5); ?> tradespeople to respond will be invited to quote. The rest will be notified when slots are full.</p>
<p style="text-align:center"><a href="<?php echo esc_url($dashboard_url); ?>" class="btn">Go to My Toolbox →</a></p>
<p>Best,<br>The <?php echo esc_html($brand_name); ?> Team</p>
<?php
$body_html = ob_get_clean();
include CFQM_TPL . 'emails/email-wrapper.php';
