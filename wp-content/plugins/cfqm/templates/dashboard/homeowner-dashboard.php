<?php
/**
 * Template: Homeowner Dashboard
 * Variables: $quotes (array of grouped quote data), $email (string)
 */
defined( 'ABSPATH' ) || exit;

$brand = esc_html( CFQM\Settings::instance()->get( 'brand_name', 'Fixdly' ) );

// Group quotes by project label
$projects = [];
foreach ( $quotes as $q ) {
    $label              = $q['project_label'] ?: __( 'General Queries', 'cfqm' );
    $projects[ $label ][] = $q;
}

// Active filter tab
$active_tab = sanitize_key( $_GET['tab'] ?? 'all' );
$tabs = [
    'all'      => __( 'All', 'cfqm' ),
    'pending'  => __( 'Pending', 'cfqm' ),
    'approved' => __( 'Accepted', 'cfqm' ),
    'closed'   => __( 'Closed', 'cfqm' ),
];

// Compare mode
$compare_ids = array_filter( array_map( 'intval', (array) ( $_GET['compare'] ?? [] ) ) );
?>
<div class="cfqm-hw-dashboard" id="cfqm-hw-dashboard">

  <!-- Header -->
  <div class="cfqm-hw-dashboard__header">
    <h2><?php printf( esc_html__( 'My Quotes – %s', 'cfqm' ), $brand ); ?></h2>
    <p class="cfqm-hw-dashboard__email">
      <?php printf( esc_html__( 'Showing quotes for: %s', 'cfqm' ), '<strong>' . esc_html( $email ) . '</strong>' ); ?>
    </p>
  </div>

  <!-- Summary cards -->
  <?php
  $counts = [ 'all' => count($quotes), 'pending' => 0, 'approved' => 0, 'closed' => 0 ];
  foreach ( $quotes as $q ) {
      $s = $q['status'];
      if ( in_array($s, ['approved','partial','deposit_paid'], true) )  $counts['approved']++;
      elseif ( in_array($s, ['declined','expired'], true) )             $counts['closed']++;
      else                                                              $counts['pending']++;
  }
  ?>
  <div class="cfqm-hw-stat-row">
    <?php foreach ( $tabs as $key => $label ) : ?>
      <a href="?tab=<?php echo esc_attr($key); ?>"
         class="cfqm-hw-stat-card <?php echo $active_tab === $key ? 'cfqm-hw-stat-card--active' : ''; ?>">
        <span class="cfqm-hw-stat-card__count"><?php echo esc_html($counts[$key]); ?></span>
        <span class="cfqm-hw-stat-card__label"><?php echo esc_html($label); ?></span>
      </a>
    <?php endforeach; ?>
  </div>

  <!-- Search -->
  <div class="cfqm-hw-search">
    <input type="text" id="cfqm-hw-search" placeholder="<?php esc_attr_e('Search by trade or job…','cfqm'); ?>" class="cfqm-input">
  </div>

  <!-- Compare bar -->
  <?php if ( count($compare_ids) >= 2 ) : ?>
  <div class="cfqm-compare-bar" id="cfqm-compare-bar">
    <strong><?php printf( esc_html__('Comparing %d quotes','cfqm'), count($compare_ids)); ?></strong>
    <button type="button" class="cfqm-btn cfqm-btn--primary" id="cfqm-run-compare"><?php esc_html_e('Compare Now','cfqm'); ?></button>
    <a href="?" class="cfqm-btn cfqm-btn--ghost"><?php esc_html_e('Clear','cfqm'); ?></a>
  </div>
  <?php endif; ?>

  <!-- Compare view (side-by-side) -->
  <div class="cfqm-compare-panel" id="cfqm-compare-panel" style="display:none">
    <h3><?php esc_html_e('Quote Comparison','cfqm'); ?></h3>
    <div class="cfqm-compare-grid" id="cfqm-compare-grid">
      <?php foreach ( $compare_ids as $cid ) :
        $cdata = CFQM\Quote_Builder::instance()->get_full_quote_data($cid);
        if (!$cdata) continue;
        $c_total   = (float)$cdata['grand_total'];
        $c_deposit = (float)$cdata['deposit_amount'];
        $c_sent    = $cdata['sent_at'] ?? '';
        ?>
        <div class="cfqm-compare-col">
          <h4><?php echo esc_html($cdata['trade_name'] ?? __('Quote','cfqm')); ?></h4>
          <p><strong><?php esc_html_e('Total','cfqm'); ?>:</strong> £<?php echo esc_html(number_format($c_total,2)); ?></p>
          <p><strong><?php esc_html_e('Deposit','cfqm'); ?>:</strong> £<?php echo esc_html(number_format($c_deposit,2)); ?></p>
          <p><strong><?php esc_html_e('Sent','cfqm'); ?>:</strong> <?php echo $c_sent ? esc_html(date('d M Y',strtotime($c_sent))) : '—'; ?></p>
          <p><strong><?php esc_html_e('Areas','cfqm'); ?>:</strong> <?php echo esc_html(count($cdata['areas'])); ?></p>
          <a href="<?php echo esc_url(CFQM\Settings::instance()->portal_url($cdata['portal_token']??'')); ?>"
             class="cfqm-btn cfqm-btn--primary" style="margin-top:8px">
            <?php esc_html_e('Open Quote','cfqm'); ?>
          </a>
        </div>
      <?php endforeach; ?>
    </div>
    <?php // Highlight cheapest ?>
    <p class="cfqm-compare-hint"><?php esc_html_e('✦ Lowest total price highlighted in green.','cfqm'); ?></p>
  </div>

  <!-- Project groups -->
  <?php if ( empty($quotes) ) : ?>
    <div class="cfqm-empty-state">
      <p><?php esc_html_e('No quotes found for your email address.','cfqm'); ?></p>
    </div>
  <?php else : ?>

    <?php foreach ( $projects as $project_name => $project_quotes ) : ?>
    <div class="cfqm-project-group" data-project="<?php echo esc_attr($project_name); ?>">
      <div class="cfqm-project-group__header">
        <h3 class="cfqm-project-group__title"><?php echo esc_html($project_name); ?></h3>
        <span class="cfqm-project-group__count"><?php printf(esc_html__('%d quote(s)','cfqm'),count($project_quotes)); ?></span>
      </div>

      <div class="cfqm-quotes-list">
        <?php foreach ( $project_quotes as $q ) :
          $status   = $q['status'] ?? 'sent';
          $tab_show = match(true) {
              in_array($status,['approved','partial','deposit_paid'],true) => 'approved',
              in_array($status,['declined','expired'],true)                => 'closed',
              default                                                       => 'pending',
          };
          $show = $active_tab === 'all' || $active_tab === $tab_show;
          ?>
          <div class="cfqm-quote-card <?php echo !$show ? 'cfqm-quote-card--hidden' : ''; ?>"
               data-status="<?php echo esc_attr($status); ?>"
               data-tab="<?php echo esc_attr($tab_show); ?>"
               data-searchtext="<?php echo esc_attr(strtolower(($q['trade_name']??'').' '.($q['job_desc']??''))); ?>">

            <div class="cfqm-quote-card__info">
              <div class="cfqm-quote-card__provider">
                <?php echo esc_html($q['trade_name'] ?? __('Tradesperson','cfqm')); ?>
              </div>
              <div class="cfqm-quote-card__desc"><?php echo esc_html(wp_trim_words($q['job_desc']??'',12)); ?></div>
              <div class="cfqm-quote-card__meta">
                <span class="cfqm-badge cfqm-badge--<?php echo esc_attr($status); ?>">
                  <?php echo esc_html(ucfirst(str_replace('_',' ',$status))); ?>
                </span>
                <span>£<?php echo esc_html(number_format((float)($q['grand_total']??0),2)); ?></span>
                <?php if (!empty($q['sent_at'])) : ?>
                  <span><?php echo esc_html(date('d M Y',strtotime($q['sent_at']))); ?></span>
                <?php endif; ?>
              </div>
            </div>

            <div class="cfqm-quote-card__actions">
              <a href="<?php echo esc_url(CFQM\Settings::instance()->portal_url($q['portal_token']??'')); ?>"
                 class="cfqm-btn cfqm-btn--primary cfqm-btn--sm">
                <?php esc_html_e('View Quote','cfqm'); ?>
              </a>
              <?php if ( in_array($q['status'],['sent','viewed','pending_amendment'],true) ) : ?>
                <label class="cfqm-compare-check">
                  <input type="checkbox" class="cfqm-compare-checkbox"
                         value="<?php echo esc_attr($q['quote_id']); ?>"
                         <?php checked(in_array((int)$q['quote_id'],$compare_ids,true)); ?>>
                  <?php esc_html_e('Compare','cfqm'); ?>
                </label>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endforeach; ?>
  <?php endif; ?>

  <!-- New query CTA -->
  <?php $query_page = (int) CFQM\Settings::instance()->get('page_query_form'); ?>
  <?php if ($query_page) : ?>
  <div class="cfqm-hw-new-query">
    <a href="<?php echo esc_url(get_permalink($query_page)); ?>" class="cfqm-btn cfqm-btn--outline">
      + <?php esc_html_e('Submit a New Query','cfqm'); ?>
    </a>
  </div>
  <?php endif; ?>

</div><!-- .cfqm-hw-dashboard -->
