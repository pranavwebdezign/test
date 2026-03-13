<?php defined( 'ABSPATH' ) || exit;
/** @var WP_Post $post */
$meta = [
    'name'     => get_post_meta($post->ID,'_cfqm_hw_name',true),
    'email'    => get_post_meta($post->ID,'_cfqm_hw_email',true),
    'phone'    => get_post_meta($post->ID,'_cfqm_hw_phone',true),
    'postcode' => get_post_meta($post->ID,'_cfqm_hw_postcode',true),
    'lat'      => get_post_meta($post->ID,'_cfqm_hw_lat',true),
    'lng'      => get_post_meta($post->ID,'_cfqm_hw_lng',true),
    'desc'     => get_post_meta($post->ID,'_cfqm_hw_job_desc',true),
    'status'   => get_post_meta($post->ID,'_cfqm_hw_status',true),
    'label'    => get_post_meta($post->ID,'_cfqm_project_label',true),
    'at'       => get_post_meta($post->ID,'_cfqm_submitted_at',true),
];
$matched   = json_decode((string)get_post_meta($post->ID,'_cfqm_matched_trades',true),true) ?: [];
$responses = json_decode((string)get_post_meta($post->ID,'_cfqm_responses',true),true) ?: [];
$category  = wp_get_post_terms($post->ID,'cfqm_category');
?>
<style>
.cfqm-query-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px 24px}
.cfqm-query-grid label{font-weight:600;display:block;margin-bottom:2px}
.cfqm-query-grid .full{grid-column:1/-1}
.cfqm-badge{display:inline-block;padding:2px 8px;border-radius:3px;font-size:12px;font-weight:600;text-transform:uppercase}
.cfqm-badge--open{background:#d5f5e3;color:#1e6e42}
.cfqm-badge--quotes_full{background:#fdebd0;color:#a04000}
.cfqm-badge--reopened{background:#d6eaf8;color:#1a5276}
.cfqm-badge--closed{background:#f5f5f5;color:#666}
</style>

<div class="cfqm-query-grid">
  <div>
    <label><?php esc_html_e('Name','cfqm'); ?></label>
    <p><?php echo esc_html($meta['name'] ?: '—'); ?></p>
  </div>
  <div>
    <label><?php esc_html_e('Email','cfqm'); ?></label>
    <p><?php echo $meta['email'] ? '<a href="mailto:'.esc_attr($meta['email']).'">'.esc_html($meta['email']).'</a>' : '—'; ?></p>
  </div>
  <div>
    <label><?php esc_html_e('Phone','cfqm'); ?></label>
    <p><?php echo esc_html($meta['phone'] ?: '—'); ?></p>
  </div>
  <div>
    <label><?php esc_html_e('Postcode','cfqm'); ?></label>
    <p><?php echo esc_html($meta['postcode'] ?: '—'); ?>
      <?php if ($meta['lat'] && $meta['lng']) echo '<br><small style="color:#888">'.esc_html(number_format(floatval($meta['lat']),5)).', '.esc_html(number_format(floatval($meta['lng']),5)).'</small>'; ?>
    </p>
  </div>
  <div>
    <label><?php esc_html_e('Trade Category','cfqm'); ?></label>
    <p><?php echo $category && !is_wp_error($category) ? esc_html($category[0]->name) : '—'; ?></p>
  </div>
  <div>
    <label><?php esc_html_e('Status','cfqm'); ?></label>
    <p><span class="cfqm-badge cfqm-badge--<?php echo esc_attr($meta['status']); ?>"><?php echo esc_html(ucfirst(str_replace('_',' ',$meta['status'] ?: 'open'))); ?></span></p>
  </div>
  <div>
    <label><?php esc_html_e('Project Label','cfqm'); ?></label>
    <p><?php echo esc_html($meta['label'] ?: '—'); ?></p>
  </div>
  <div>
    <label><?php esc_html_e('Submitted','cfqm'); ?></label>
    <p><?php echo esc_html($meta['at'] ?: get_the_date('d M Y H:i',$post->ID)); ?></p>
  </div>
  <div class="full">
    <label><?php esc_html_e('Job Description','cfqm'); ?></label>
    <p style="white-space:pre-wrap;background:#f9f9f9;padding:8px;border:1px solid #ddd"><?php echo esc_html($meta['desc'] ?: '—'); ?></p>
  </div>
  <div class="full">
    <label><?php printf(esc_html__('Matched Trades (%d)','cfqm'),count($matched)); ?></label>
    <?php if ($matched) : ?>
      <ul style="margin:0;list-style:disc;padding-left:20px">
        <?php foreach ($matched as $tid) :
          $tpost = get_post($tid);
          $responded = isset($responses[$tid]);
          ?>
          <li>
            <?php echo $tpost ? esc_html($tpost->post_title) : '#'.esc_html($tid); ?>
            <?php if ($responded) echo ' <span style="color:green">✔ Responded (Quote #'.esc_html($responses[$tid]).')</span>'; ?>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php else : ?>
      <p><?php esc_html_e('None matched yet.','cfqm'); ?></p>
    <?php endif; ?>
  </div>
</div>
