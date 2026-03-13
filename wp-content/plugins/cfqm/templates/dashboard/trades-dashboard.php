<?php
/**
 * Template: Tradesperson Dashboard (Toolbox)
 * Variables: $user_id, $plan, $usage, $limit
 */
defined( 'ABSPATH' ) || exit;

$uid    = get_current_user_id();
$plan   = CFQM\Subscription_Tiers::instance()->get_user_plan($uid);
$usage  = CFQM\Subscription_Tiers::instance()->get_monthly_usage($uid);
$qlimit = CFQM\Subscription_Tiers::instance()->get_limit($plan,'quotes_per_month');

// Fetch quotes
$all_quotes = get_posts([
    'post_type'      => 'cfqm_quote',
    'post_author'    => $uid,
    'post_status'    => 'publish',
    'posts_per_page' => -1,
    'meta_query'     => [],
]);

// Categorise
$pipeline   = [];
$amendments = [];
$awaiting   = [];

foreach ($all_quotes as $q) {
    $status = get_post_meta($q->ID,'_cfqm_status',true) ?: 'draft';
    $data   = [
        'id'       => $q->ID,
        'title'    => $q->post_title ?: '#'.$q->ID,
        'cname'    => get_post_meta($q->ID,'_cfqm_customer_name',true),
        'cemail'   => get_post_meta($q->ID,'_cfqm_customer_email',true),
        'status'   => $status,
        'version'  => get_post_meta($q->ID,'_cfqm_version',true) ?: '1.0',
        'iter_u'   => (int)get_post_meta($q->ID,'_cfqm_iteration_used',true),
        'iter_l'   => (int)get_post_meta($q->ID,'_cfqm_iteration_limit',true),
        'total'    => (float)get_post_meta($q->ID,'_cfqm_grand_total',true),
        'deposit'  => (float)get_post_meta($q->ID,'_cfqm_deposit_amount',true),
        'stripe'   => get_post_meta($q->ID,'_cfqm_stripe_link',true),
        'sent_at'  => get_post_meta($q->ID,'_cfqm_sent_at',true),
        'date'     => $q->post_date,
        'portal'   => CFQM\Settings::instance()->portal_url(get_post_meta($q->ID,'_cfqm_portal_token',true)),
    ];
    if ( $status === 'pending_amendment' ) $amendments[] = $data;
    if ( in_array($status,['approved','partial'],true) && $data['deposit'] > 0 && !get_post_meta($q->ID,'_cfqm_stripe_intent_id',true) ) $awaiting[] = $data;
    if ( !in_array($status,['declined','expired'],true) ) $pipeline[] = $data;
}

// Win rate
$approved_count = count(array_filter($pipeline,fn($q)=>in_array($q['status'],['approved','partial','deposit_paid'],true)));
$sent_count     = count(array_filter($pipeline,fn($q)=>$q['status']!=='draft'));
$win_rate       = $sent_count > 0 ? round(($approved_count/$sent_count)*100) : 0;

$active_tab     = sanitize_key($_GET['tab'] ?? 'pipeline');
?>
<div class="cfqm-trades-dashboard" id="cfqm-trades-dashboard">

  <!-- Header -->
  <div class="cfqm-trades-dashboard__header">
    <h2><?php esc_html_e( 'Toolbox', 'cfqm' ); ?></h2>
    <div class="cfqm-plan-badge">
      <?php printf( esc_html__( 'Plan: %s', 'cfqm' ),
        '<strong>' . esc_html( CFQM\Subscription_Tiers::instance()->plan_label($plan) ) . '</strong>' ); ?>
      &nbsp;·&nbsp;
      <?php printf( esc_html__( '%d / %d quotes this month', 'cfqm' ), $usage, $qlimit ); ?>
    </div>
  </div>

  <!-- Stat cards -->
  <div class="cfqm-stat-cards">
    <div class="cfqm-stat-card">
      <span class="cfqm-stat-value"><?php echo esc_html(count($pipeline)); ?></span>
      <span class="cfqm-stat-label"><?php esc_html_e('Active Quotes','cfqm'); ?></span>
    </div>
    <div class="cfqm-stat-card cfqm-stat-card--warning">
      <span class="cfqm-stat-value"><?php echo esc_html(count($amendments)); ?></span>
      <span class="cfqm-stat-label"><?php esc_html_e('Pending Amendments','cfqm'); ?></span>
    </div>
    <div class="cfqm-stat-card cfqm-stat-card--success">
      <span class="cfqm-stat-value"><?php echo esc_html($win_rate); ?>%</span>
      <span class="cfqm-stat-label"><?php esc_html_e('Win Rate','cfqm'); ?></span>
    </div>
    <div class="cfqm-stat-card cfqm-stat-card--info">
      <span class="cfqm-stat-value"><?php echo esc_html(count($awaiting)); ?></span>
      <span class="cfqm-stat-label"><?php esc_html_e('Awaiting Deposit','cfqm'); ?></span>
    </div>
  </div>

  <!-- Create quote CTA -->
  <?php if ( CFQM\Subscription_Tiers::instance()->can_create_quote($uid) ) : ?>
    <div class="cfqm-create-quote-cta">
      <button type="button" id="cfqm-btn-new-quote" class="cfqm-btn cfqm-btn--primary">
        + <?php esc_html_e( 'Create New Quote', 'cfqm' ); ?>
      </button>
    </div>
  <?php else : ?>
    <div class="cfqm-alert cfqm-alert--warning">
      <?php printf(esc_html__('Monthly quote limit reached (%d/%d). Upgrade to Pro for more.','cfqm'),$usage,$qlimit); ?>
    </div>
  <?php endif; ?>

  <!-- Tabs -->
  <div class="cfqm-tabs" role="tablist">
    <?php $tabs = ['pipeline'=>__('Pipeline','cfqm'),'amendments'=>__('Amendments','cfqm'),'awaiting'=>__('Awaiting Deposit','cfqm')]; ?>
    <?php foreach ($tabs as $key => $label) : ?>
      <a href="?tab=<?php echo esc_attr($key); ?>"
         class="cfqm-tab <?php echo $active_tab===$key?'cfqm-tab--active':''; ?>"
         role="tab">
        <?php echo esc_html($label); ?>
        <?php if ($key==='amendments'&&count($amendments)) echo '<span class="cfqm-tab-badge">'.count($amendments).'</span>'; ?>
      </a>
    <?php endforeach; ?>
  </div>

  <!-- ── Tab: Pipeline ─────────────────────────────────────────────── -->
  <?php if ( $active_tab === 'pipeline' ) : ?>
  <div class="cfqm-tab-panel" id="tab-pipeline">
    <?php if ( empty($pipeline) ) : ?>
      <div class="cfqm-empty-state"><p><?php esc_html_e('No active quotes yet. Create your first one above.','cfqm'); ?></p></div>
    <?php else : ?>
    <table class="cfqm-table">
      <thead>
        <tr>
          <th><?php esc_html_e('Customer','cfqm'); ?></th>
          <th><?php esc_html_e('Status','cfqm'); ?></th>
          <th><?php esc_html_e('Version','cfqm'); ?></th>
          <th><?php esc_html_e('Iterations','cfqm'); ?></th>
          <th><?php esc_html_e('Total','cfqm'); ?></th>
          <th><?php esc_html_e('Sent','cfqm'); ?></th>
          <th><?php esc_html_e('Actions','cfqm'); ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($pipeline as $q) : ?>
        <tr>
          <td>
            <strong><?php echo esc_html($q['cname'] ?: '—'); ?></strong>
            <?php if ($q['cemail']) echo '<br><small>'.esc_html($q['cemail']).'</small>'; ?>
          </td>
          <td><span class="cfqm-badge cfqm-badge--<?php echo esc_attr($q['status']); ?>">
            <?php echo esc_html(ucfirst(str_replace('_',' ',$q['status']))); ?>
          </span></td>
          <td>v<?php echo esc_html($q['version']); ?></td>
          <td><?php echo $q['iter_l'] ? esc_html($q['iter_u'].'/'.$q['iter_l']) : '—'; ?></td>
          <td>£<?php echo esc_html(number_format($q['total'],2)); ?></td>
          <td><?php echo $q['sent_at'] ? esc_html(date('d M Y',strtotime($q['sent_at']))) : '—'; ?></td>
          <td class="cfqm-table__actions">
            <a href="<?php echo esc_url(get_edit_post_link($q['id'])); ?>" class="cfqm-btn cfqm-btn--ghost cfqm-btn--sm"><?php esc_html_e('Edit','cfqm'); ?></a>
            <a href="<?php echo esc_url($q['portal']); ?>" target="_blank" class="cfqm-btn cfqm-btn--ghost cfqm-btn--sm"><?php esc_html_e('Preview','cfqm'); ?></a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>

  <!-- ── Tab: Amendments ───────────────────────────────────────────── -->
  <?php elseif ( $active_tab === 'amendments' ) : ?>
  <div class="cfqm-tab-panel" id="tab-amendments">
    <?php if ( empty($amendments) ) : ?>
      <div class="cfqm-empty-state"><p><?php esc_html_e('No pending amendment requests.','cfqm'); ?></p></div>
    <?php else : ?>
      <?php foreach ($amendments as $q) :
        $history = json_decode((string)get_post_meta($q['id'],'_cfqm_amendment_history',true),true) ?: [];
        $latest  = end($history);
        ?>
        <div class="cfqm-amendment-card">
          <div class="cfqm-amendment-card__header">
            <strong><?php echo esc_html($q['cname'] ?: '#'.$q['id']); ?></strong>
            <span class="cfqm-badge cfqm-badge--pending_amendment"><?php esc_html_e('Amendment Requested','cfqm'); ?></span>
            <span style="color:#888"><?php printf(esc_html__('Iteration %d/%d','cfqm'),$q['iter_u'],$q['iter_l']); ?></span>
          </div>
          <?php if ($latest) : ?>
          <div class="cfqm-amendment-card__request">
            <p><strong><?php esc_html_e('Request:','cfqm'); ?></strong> <?php echo esc_html($latest['message'] ?: '—'); ?></p>
            <p><strong><?php esc_html_e('Areas flagged:','cfqm'); ?></strong>
              <?php
              $area_names = [];
              foreach ((array)($latest['areas']??[]) as $aid) {
                  $ap = get_post($aid);
                  if ($ap) $area_names[] = $ap->post_title;
              }
              echo $area_names ? esc_html(implode(', ',$area_names)) : '—';
              ?>
            </p>
          </div>
          <?php endif; ?>
          <div class="cfqm-amendment-card__actions">
            <button type="button"
                    class="cfqm-btn cfqm-btn--primary cfqm-btn--sm cfqm-amendment-action"
                    data-quote-id="<?php echo esc_attr($q['id']); ?>"
                    data-action="reissue">
              <?php esc_html_e('Edit & Reissue','cfqm'); ?>
            </button>
            <button type="button"
                    class="cfqm-btn cfqm-btn--ghost cfqm-btn--sm cfqm-amendment-action"
                    data-quote-id="<?php echo esc_attr($q['id']); ?>"
                    data-action="approve_as_is">
              <?php esc_html_e('Approve As-Is','cfqm'); ?>
            </button>
            <button type="button"
                    class="cfqm-btn cfqm-btn--danger cfqm-btn--sm cfqm-amendment-reject"
                    data-quote-id="<?php echo esc_attr($q['id']); ?>">
              <?php esc_html_e('Reject','cfqm'); ?>
            </button>
          </div>
          <!-- Reject form (hidden) -->
          <div class="cfqm-reject-form" id="cfqm-reject-<?php echo esc_attr($q['id']); ?>" style="display:none">
            <textarea class="cfqm-textarea cfqm-reject-message" rows="3"
                      placeholder="<?php esc_attr_e('Explain why you are declining this amendment request…','cfqm'); ?>"></textarea>
            <button type="button"
                    class="cfqm-btn cfqm-btn--danger cfqm-btn--sm cfqm-amendment-action"
                    data-quote-id="<?php echo esc_attr($q['id']); ?>"
                    data-action="reject">
              <?php esc_html_e('Send Rejection','cfqm'); ?>
            </button>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <!-- ── Tab: Awaiting Deposit ─────────────────────────────────────── -->
  <?php elseif ( $active_tab === 'awaiting' ) : ?>
  <div class="cfqm-tab-panel" id="tab-awaiting">
    <?php if ( empty($awaiting) ) : ?>
      <div class="cfqm-empty-state"><p><?php esc_html_e('No accepted quotes awaiting deposit.','cfqm'); ?></p></div>
    <?php else : ?>
    <table class="cfqm-table">
      <thead>
        <tr>
          <th><?php esc_html_e('Customer','cfqm'); ?></th>
          <th><?php esc_html_e('Total','cfqm'); ?></th>
          <th><?php esc_html_e('Deposit','cfqm'); ?></th>
          <th><?php esc_html_e('Payment Link','cfqm'); ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($awaiting as $q) : ?>
        <tr>
          <td><?php echo esc_html($q['cname'] ?: '—'); ?></td>
          <td>£<?php echo esc_html(number_format($q['total'],2)); ?></td>
          <td>£<?php echo esc_html(number_format($q['deposit'],2)); ?></td>
          <td>
            <?php if ($q['stripe']) : ?>
              <a href="<?php echo esc_url($q['stripe']); ?>" target="_blank" class="cfqm-btn cfqm-btn--sm cfqm-btn--success"><?php esc_html_e('Stripe Link ↗','cfqm'); ?></a>
              <button type="button" class="cfqm-btn cfqm-btn--sm cfqm-btn--ghost cfqm-copy-link" data-url="<?php echo esc_attr($q['stripe']); ?>"><?php esc_html_e('Copy','cfqm'); ?></button>
            <?php else : ?>
              <button type="button" class="cfqm-btn cfqm-btn--sm cfqm-btn--primary cfqm-create-payment-link" data-quote-id="<?php echo esc_attr($q['id']); ?>"><?php esc_html_e('Create Stripe Link','cfqm'); ?></button>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <!-- Quote Builder modal placeholder -->
  <div id="cfqm-quote-builder-modal" class="cfqm-modal" style="display:none">
    <div class="cfqm-modal__overlay" id="cfqm-modal-overlay"></div>
    <div class="cfqm-modal__dialog">
      <div class="cfqm-modal__header">
        <h3 id="cfqm-modal-title"><?php esc_html_e('Quote Builder','cfqm'); ?></h3>
        <button type="button" id="cfqm-modal-close" class="cfqm-modal__close">✕</button>
      </div>
      <div class="cfqm-modal__body" id="cfqm-modal-body">
        <!-- Loaded dynamically via JS -->
      </div>
    </div>
  </div>

</div><!-- .cfqm-trades-dashboard -->
