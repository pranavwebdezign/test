<?php defined( 'ABSPATH' ) || exit;
/** @var WP_Post $post */
$qid = $post->ID;
$status   = get_post_meta($qid,'_cfqm_status',true) ?: 'draft';
$version  = get_post_meta($qid,'_cfqm_version',true) ?: '1.0';
$iter_u   = (int)get_post_meta($qid,'_cfqm_iteration_used',true);
$iter_l   = (int)get_post_meta($qid,'_cfqm_iteration_limit',true);
$cname    = get_post_meta($qid,'_cfqm_customer_name',true);
$cemail   = get_post_meta($qid,'_cfqm_customer_email',true);
$cphone   = get_post_meta($qid,'_cfqm_customer_phone',true);
$total    = (float)get_post_meta($qid,'_cfqm_grand_total',true);
$deposit  = (float)get_post_meta($qid,'_cfqm_deposit_amount',true);
$token    = get_post_meta($qid,'_cfqm_portal_token',true);
$stripe   = get_post_meta($qid,'_cfqm_stripe_link',true);
$sent_at  = get_post_meta($qid,'_cfqm_sent_at',true);
$signed   = get_post_meta($qid,'_cfqm_signed_at',true);
$sig_name = get_post_meta($qid,'_cfqm_signature',true);
$areas    = get_posts(['post_type'=>'cfqm_quote_area','post_parent'=>$qid,'posts_per_page'=>-1,'post_status'=>'publish']);
$timeline = CFQM\Audit_Trail::instance()->get_quote_timeline($qid);
$at_label = CFQM\Audit_Trail::instance();
?>
<style>
.cfqm-qmeta{display:grid;grid-template-columns:1fr 1fr;gap:10px 24px}
.cfqm-qmeta label{font-weight:600;display:block;margin-bottom:2px;color:#23282d}
.cfqm-qmeta .full{grid-column:1/-1}
.cfqm-timeline{margin:0;padding:0;list-style:none}
.cfqm-timeline li{padding:6px 0 6px 16px;border-left:3px solid #2271b1;margin-left:8px;font-size:13px;position:relative}
.cfqm-timeline li::before{content:'';position:absolute;left:-6px;top:10px;width:9px;height:9px;background:#2271b1;border-radius:50%}
.cfqm-timeline .evt-time{color:#888;font-size:11px;margin-left:8px}
.cfqm-areas-table{width:100%;border-collapse:collapse;font-size:13px}
.cfqm-areas-table th{background:#f0f0f0;padding:6px 10px;text-align:left;border:1px solid #ddd}
.cfqm-areas-table td{padding:6px 10px;border:1px solid #ddd}
</style>

<div class="cfqm-qmeta">
  <!-- Customer -->
  <div>
    <label><?php esc_html_e('Customer','cfqm'); ?></label>
    <p><?php echo esc_html($cname ?: '—'); ?><br>
      <?php if ($cemail) echo '<a href="mailto:'.esc_attr($cemail).'">'.esc_html($cemail).'</a>'; ?>
      <?php if ($cphone) echo ' · '.esc_html($cphone); ?>
    </p>
  </div>

  <!-- Status / Version -->
  <div>
    <label><?php esc_html_e('Status','cfqm'); ?></label>
    <p><span class="cfqm-badge cfqm-badge--<?php echo esc_attr($status); ?>"><?php echo esc_html(ucfirst(str_replace('_',' ',$status))); ?></span>
      &nbsp; v<?php echo esc_html($version); ?>
      <?php if ($iter_l) echo '&nbsp;|&nbsp; Iterations: '.esc_html($iter_u).'/'.esc_html($iter_l); ?>
    </p>
  </div>

  <!-- Totals -->
  <div>
    <label><?php esc_html_e('Grand Total','cfqm'); ?></label>
    <p style="font-size:18px;font-weight:bold">£<?php echo esc_html(number_format($total,2)); ?></p>
  </div>
  <div>
    <label><?php esc_html_e('Deposit','cfqm'); ?></label>
    <p>£<?php echo esc_html(number_format($deposit,2)); ?>
      <?php if ($stripe) echo ' — <a href="'.esc_url($stripe).'" target="_blank">Stripe Link</a>'; ?>
    </p>
  </div>

  <!-- Dates -->
  <?php if ($sent_at) : ?>
  <div>
    <label><?php esc_html_e('Sent','cfqm'); ?></label>
    <p><?php echo esc_html($sent_at); ?></p>
  </div>
  <?php endif; ?>
  <?php if ($signed) : ?>
  <div>
    <label><?php esc_html_e('Signed','cfqm'); ?></label>
    <p><?php echo esc_html($signed); ?> <?php if ($sig_name) echo '— <em>'.esc_html($sig_name).'</em>'; ?></p>
  </div>
  <?php endif; ?>

  <!-- Portal token -->
  <?php if ($token) : ?>
  <div class="full">
    <label><?php esc_html_e('Portal Link','cfqm'); ?></label>
    <p><input type="text" value="<?php echo esc_attr(CFQM\Settings::instance()->portal_url($token)); ?>" class="large-text" readonly onclick="this.select()"></p>
  </div>
  <?php endif; ?>

  <!-- Areas -->
  <?php if ($areas) : ?>
  <div class="full">
    <label><?php esc_html_e('Quote Areas','cfqm'); ?></label>
    <table class="cfqm-areas-table">
      <thead>
        <tr><th><?php esc_html_e('Area','cfqm'); ?></th><th><?php esc_html_e('Type','cfqm'); ?></th><th><?php esc_html_e('Price','cfqm'); ?></th><th><?php esc_html_e('Valid Until','cfqm'); ?></th></tr>
      </thead>
      <tbody>
        <?php foreach ($areas as $a) :
          $atype  = get_post_meta($a->ID,'_cfqm_area_type',true) ?: 'included';
          $aprice = (float)get_post_meta($a->ID,'_cfqm_area_price',true);
          $avalid = get_post_meta($a->ID,'_cfqm_area_validity',true);
          ?>
          <tr>
            <td><?php echo esc_html($a->post_title); ?></td>
            <td><span class="cfqm-badge cfqm-badge--<?php echo esc_attr($atype); ?>"><?php echo esc_html(ucfirst($atype)); ?></span></td>
            <td>£<?php echo esc_html(number_format($aprice,2)); ?></td>
            <td><?php echo esc_html($avalid ?: '—'); ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>

  <!-- Timeline -->
  <?php if ($timeline) : ?>
  <div class="full">
    <label><?php esc_html_e('Event Timeline','cfqm'); ?></label>
    <ul class="cfqm-timeline">
      <?php foreach ($timeline as $ev) : ?>
        <li>
          <strong><?php echo esc_html($at_label->event_label($ev->event_type)); ?></strong>
          <span class="evt-time"><?php echo esc_html($ev->created_at); ?></span>
          — <?php echo esc_html(ucfirst($ev->actor_type)); ?>
          <?php if ($ev->version) echo ' · v'.esc_html($ev->version); ?>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
  <?php endif; ?>
</div>
