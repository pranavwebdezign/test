<?php defined( 'ABSPATH' ) || exit;
/** @var WP_Post $post */
$meta = [
    '_cfqm_user_id'      => (int) get_post_meta($post->ID,'_cfqm_user_id',true),
    '_cfqm_postcode'     => get_post_meta($post->ID,'_cfqm_postcode',true),
    '_cfqm_radius_miles' => (int)(get_post_meta($post->ID,'_cfqm_radius_miles',true) ?: 25),
    '_cfqm_phone'        => get_post_meta($post->ID,'_cfqm_phone',true),
    '_cfqm_email'        => get_post_meta($post->ID,'_cfqm_email',true),
    '_cfqm_address'      => get_post_meta($post->ID,'_cfqm_address',true),
];
$lat   = get_post_meta($post->ID,'_cfqm_lat',true);
$lng   = get_post_meta($post->ID,'_cfqm_lng',true);
$score = get_post_meta($post->ID,'_cfqm_activity_score',true);
$plan  = CFQM\Subscription_Tiers::instance()->get_user_plan((int)$meta['_cfqm_user_id']);
?>
<style>.cfqm-meta-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px 24px}.cfqm-meta-grid label{font-weight:600;display:block;margin-bottom:4px}.cfqm-meta-grid .full-width{grid-column:1/-1}</style>
<div class="cfqm-meta-grid">

  <div class="full-width">
    <label><?php esc_html_e('Linked WordPress User','cfqm'); ?></label>
    <select name="_cfqm_user_id" style="width:100%">
      <option value="0"><?php esc_html_e('— Not linked —','cfqm'); ?></option>
      <?php foreach (get_users(['role__in'=>['subscriber','administrator']]) as $u) : ?>
        <option value="<?php echo esc_attr($u->ID); ?>" <?php selected($meta['_cfqm_user_id'],$u->ID); ?>>
          <?php echo esc_html("$u->display_name ($u->user_email)"); ?>
        </option>
      <?php endforeach; ?>
    </select>
    <?php if ($plan) : ?>
      <p style="margin:4px 0 0;color:#444">
        <?php printf(esc_html__('Plan: %s','cfqm'), '<strong>'.esc_html(CFQM\Subscription_Tiers::instance()->plan_label($plan)).'</strong>'); ?>
      </p>
    <?php endif; ?>
  </div>

  <div>
    <label><?php esc_html_e('Business Postcode','cfqm'); ?></label>
    <input type="text" name="_cfqm_postcode" value="<?php echo esc_attr($meta['_cfqm_postcode']); ?>" class="regular-text" placeholder="e.g. SW1A 1AA">
    <?php if ($lat && $lng) : ?>
      <p style="margin:4px 0 0;color:#666;font-size:12px"><?php printf('Geocoded: %.5f, %.5f',floatval($lat),floatval($lng)); ?></p>
    <?php endif; ?>
  </div>

  <div>
    <label><?php esc_html_e('Work Radius (miles)','cfqm'); ?></label>
    <input type="number" name="_cfqm_radius_miles" value="<?php echo esc_attr($meta['_cfqm_radius_miles']); ?>" min="1" max="200" class="small-text">
  </div>

  <div>
    <label><?php esc_html_e('Contact Phone','cfqm'); ?></label>
    <input type="text" name="_cfqm_phone" value="<?php echo esc_attr($meta['_cfqm_phone']); ?>" class="regular-text">
  </div>

  <div>
    <label><?php esc_html_e('Contact Email','cfqm'); ?></label>
    <input type="email" name="_cfqm_email" value="<?php echo esc_attr($meta['_cfqm_email']); ?>" class="regular-text">
  </div>

  <div class="full-width">
    <label><?php esc_html_e('Business Address','cfqm'); ?></label>
    <textarea name="_cfqm_address" rows="2" style="width:100%"><?php echo esc_textarea($meta['_cfqm_address']); ?></textarea>
  </div>

  <?php if ($score !== '') : ?>
  <div>
    <label><?php esc_html_e('Activity Score','cfqm'); ?></label>
    <span style="font-size:18px;font-weight:bold"><?php echo esc_html(number_format(floatval($score),1)); ?></span>/100
    <p style="margin:4px 0 0;color:#666;font-size:12px"><?php esc_html_e('Auto-updated hourly. Used for query prioritisation.','cfqm'); ?></p>
  </div>
  <?php endif; ?>

</div>
