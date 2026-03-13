<?php
/**
 * Email wrapper template.
 * Include this in each email template as a function call or ob_start wrapper.
 * Variables expected: $subject, $body_html, $brand
 */
defined( 'ABSPATH' ) || exit;
$brand     = CFQM\Settings::instance()->get('brand_name','Fixdly');
$brand_url = home_url('/');
$year      = date('Y');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?php echo esc_html($subject ?? ''); ?></title>
  <style>
    body{margin:0;padding:0;background:#f4f6f9;font-family:Arial,sans-serif;font-size:15px;color:#333}
    .email-wrap{max-width:600px;margin:32px auto}
    .email-header{background:#1a5276;padding:24px 32px;text-align:center}
    .email-header__brand{color:#fff;font-size:24px;font-weight:bold;text-decoration:none}
    .email-body{background:#fff;padding:32px;border:1px solid #e0e6ed}
    .email-body h2{font-size:20px;margin-bottom:16px;color:#1a5276}
    .email-body p{margin-bottom:12px;line-height:1.6}
    .email-body .btn{display:inline-block;background:#1a5276;color:#fff;padding:12px 28px;border-radius:4px;text-decoration:none;font-weight:bold;margin:12px 0}
    .email-body .info-box{background:#f0f6fb;border-left:4px solid #2e86c1;padding:12px 16px;margin:16px 0;border-radius:2px}
    .email-body .info-box p{margin:4px 0}
    .email-footer{background:#e8edf3;padding:16px 32px;text-align:center;font-size:12px;color:#888;border:1px solid #e0e6ed;border-top:none}
  </style>
</head>
<body>
<div class="email-wrap">
  <div class="email-header">
    <a href="<?php echo esc_url($brand_url); ?>" class="email-header__brand"><?php echo esc_html($brand); ?></a>
  </div>
  <div class="email-body">
    <?php echo $body_html ?? ''; // pre-escaped by caller ?>
  </div>
  <div class="email-footer">
    <p>© <?php echo esc_html($year); ?> <?php echo esc_html($brand); ?>. All rights reserved.</p>
    <p>You're receiving this because your email is associated with a quote on <?php echo esc_html($brand); ?>.</p>
  </div>
</div>
</body>
</html>
