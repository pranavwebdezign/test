<?php
/**
 * Email: Magic Link → Homeowner Dashboard
 * Vars: $brand_name, $magic_url
 */
defined( 'ABSPATH' ) || exit;
ob_start();
?>
<h2>Your <?php echo esc_html($brand_name); ?> Dashboard Link</h2>
<p>Hi there,</p>
<p>You requested a link to view your quotes dashboard. Click below to access all your quotes — no password required.</p>
<p style="text-align:center"><a href="<?php echo esc_url($magic_url); ?>" class="btn">View My Quotes →</a></p>
<div class="info-box">
  <p>⏱ This link is valid for <strong>24 hours</strong>. After that, simply request a new one from the login page.</p>
</div>
<p>If you did not request this email, you can safely ignore it.</p>
<p>Best,<br>The <?php echo esc_html($brand_name); ?> Team</p>
<?php
$body_html = ob_get_clean();
include CFQM_TPL . 'emails/email-wrapper.php';
