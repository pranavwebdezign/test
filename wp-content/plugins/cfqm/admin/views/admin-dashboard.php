<?php defined( 'ABSPATH' ) || exit;
global $wpdb;

// Stats
$total_quotes   = wp_count_posts('cfqm_quote');
$total_trades   = wp_count_posts('cfqm_trade_profile');
$total_queries  = wp_count_posts('cfqm_hw_query');
$recent_quotes  = get_posts([
    'post_type'      => 'cfqm_quote',
    'post_status'    => 'publish',
    'posts_per_page' => 10,
    'orderby'        => 'date',
    'order'          => 'DESC',
]);
?>
<div class="wrap cfqm-admin-dashboard">
  <h1>🔧 <?php esc_html_e('Quote Manager — Dashboard','cfqm'); ?></h1>

  <!-- Stat Cards -->
  <div class="cfqm-stat-cards">
    <div class="cfqm-stat-card">
      <span class="cfqm-stat-value"><?php echo esc_html((int)$total_quotes->publish); ?></span>
      <span class="cfqm-stat-label"><?php esc_html_e('Active Quotes','cfqm'); ?></span>
    </div>
    <div class="cfqm-stat-card">
      <span class="cfqm-stat-value"><?php echo esc_html((int)$total_trades->publish); ?></span>
      <span class="cfqm-stat-label"><?php esc_html_e('Trade Profiles','cfqm'); ?></span>
    </div>
    <div class="cfqm-stat-card">
      <span class="cfqm-stat-value"><?php echo esc_html((int)$total_queries->publish); ?></span>
      <span class="cfqm-stat-label"><?php esc_html_e('HO Queries','cfqm'); ?></span>
    </div>
    <div class="cfqm-stat-card cfqm-stat-card--events">
      <?php
      $event_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}cfqm_events");
      ?>
      <span class="cfqm-stat-value"><?php echo esc_html($event_count); ?></span>
      <span class="cfqm-stat-label"><?php esc_html_e('Audit Events','cfqm'); ?></span>
    </div>
  </div>

  <!-- Recent Quotes -->
  <h2><?php esc_html_e('Recent Quotes','cfqm'); ?></h2>
  <?php if ( $recent_quotes ) : ?>
  <table class="wp-list-table widefat fixed striped">
    <thead>
      <tr>
        <th><?php esc_html_e('Quote','cfqm'); ?></th>
        <th><?php esc_html_e('Customer','cfqm'); ?></th>
        <th><?php esc_html_e('Status','cfqm'); ?></th>
        <th><?php esc_html_e('Version','cfqm'); ?></th>
        <th><?php esc_html_e('Iterations','cfqm'); ?></th>
        <th><?php esc_html_e('Date','cfqm'); ?></th>
        <th><?php esc_html_e('Actions','cfqm'); ?></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ( $recent_quotes as $q ) :
        $status  = get_post_meta($q->ID,'_cfqm_status',true) ?: 'draft';
        $version = get_post_meta($q->ID,'_cfqm_version',true) ?: '1.0';
        $used    = (int)get_post_meta($q->ID,'_cfqm_iteration_used',true);
        $limit   = (int)get_post_meta($q->ID,'_cfqm_iteration_limit',true);
        $cname   = get_post_meta($q->ID,'_cfqm_customer_name',true);
        ?>
        <tr>
          <td><a href="<?php echo esc_url(get_edit_post_link($q->ID)); ?>"><?php echo esc_html($q->post_title ?: '#'.$q->ID); ?></a></td>
          <td><?php echo esc_html($cname ?: '—'); ?></td>
          <td><span class="cfqm-badge cfqm-badge--<?php echo esc_attr($status); ?>"><?php echo esc_html(ucfirst(str_replace('_',' ',$status))); ?></span></td>
          <td>v<?php echo esc_html($version); ?></td>
          <td><?php echo $limit ? esc_html("$used/$limit") : '—'; ?></td>
          <td><?php echo esc_html(get_the_date('d M Y', $q)); ?></td>
          <td><a href="<?php echo esc_url(get_edit_post_link($q->ID)); ?>"><?php esc_html_e('Edit','cfqm'); ?></a></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php else : ?>
    <p><?php esc_html_e('No quotes yet.','cfqm'); ?></p>
  <?php endif; ?>

  <!-- Quick Links -->
  <h2><?php esc_html_e('Quick Links','cfqm'); ?></h2>
  <p>
    <a class="button button-primary" href="<?php echo esc_url(admin_url('post-new.php?post_type=cfqm_trade_profile')); ?>"><?php esc_html_e('+ New Trade Profile','cfqm'); ?></a>
    &nbsp;
    <a class="button" href="<?php echo esc_url(admin_url('edit.php?post_type=cfqm_hw_query')); ?>"><?php esc_html_e('View Queries','cfqm'); ?></a>
    &nbsp;
    <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=cfqm-settings')); ?>"><?php esc_html_e('Settings','cfqm'); ?></a>
  </p>

  <!-- Plugin info -->
  <p style="color:#888;font-size:12px;margin-top:24px">
    <?php printf( esc_html__('ChangeFluent Quote Manager v%s — powering Fixdly.com','cfqm'), esc_html(CFQM_VERSION) ); ?>
  </p>
</div>
