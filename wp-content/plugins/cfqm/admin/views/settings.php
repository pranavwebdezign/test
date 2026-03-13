<?php defined( 'ABSPATH' ) || exit;
$s = CFQM\Settings::instance();
$pages = get_pages();
?>
<div class="wrap cfqm-settings">
  <h1><?php esc_html_e( 'Quote Manager — Settings', 'cfqm' ); ?></h1>

  <?php if ( isset( $_GET['settings-updated'] ) ) : ?>
    <div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Settings saved.', 'cfqm' ); ?></p></div>
  <?php endif; ?>

  <form method="post" action="options.php">
    <?php settings_fields( 'cfqm_settings_group' ); ?>

    <!-- ── Brand ─────────────────────────────────────────────────── -->
    <h2 class="cfqm-section-title">🎨 <?php esc_html_e( 'Brand Configuration', 'cfqm' ); ?></h2>
    <table class="form-table">
      <tr>
        <th><?php esc_html_e( 'Brand Name', 'cfqm' ); ?></th>
        <td><input type="text" name="cfqm_settings[brand_name]" value="<?php echo esc_attr( $s->get('brand_name') ); ?>" class="regular-text">
          <p class="description"><?php esc_html_e( 'Appears in emails and PDFs (e.g. "Fixdly" or "LeafAndLush").', 'cfqm' ); ?></p>
        </td>
      </tr>
      <tr>
        <th><?php esc_html_e( 'Industry', 'cfqm' ); ?></th>
        <td>
          <select name="cfqm_settings[brand_industry]">
            <option value="trades" <?php selected($s->get('brand_industry'),'trades'); ?>><?php esc_html_e('Trades','cfqm'); ?></option>
            <option value="landscaping" <?php selected($s->get('brand_industry'),'landscaping'); ?>><?php esc_html_e('Landscaping','cfqm'); ?></option>
          </select>
        </td>
      </tr>
      <tr>
        <th><?php esc_html_e( 'Tradesperson Label', 'cfqm' ); ?></th>
        <td><input type="text" name="cfqm_settings[brand_tradesperson]" value="<?php echo esc_attr( $s->get('brand_tradesperson','Tradesperson') ); ?>" class="regular-text">
          <p class="description"><?php esc_html_e( 'Label used in UI / emails (e.g. "Tradesperson", "Landscaper").', 'cfqm' ); ?></p>
        </td>
      </tr>
      <tr>
        <th><?php esc_html_e( 'Homeowner Label', 'cfqm' ); ?></th>
        <td><input type="text" name="cfqm_settings[brand_homeowner]" value="<?php echo esc_attr( $s->get('brand_homeowner','Homeowner') ); ?>" class="regular-text"></td>
      </tr>
    </table>

    <!-- ── Stripe ────────────────────────────────────────────────── -->
    <h2 class="cfqm-section-title">💳 <?php esc_html_e( 'Stripe Payments', 'cfqm' ); ?></h2>
    <table class="form-table">
      <tr>
        <th><?php esc_html_e( 'Mode', 'cfqm' ); ?></th>
        <td>
          <select name="cfqm_settings[stripe_mode]">
            <option value="test" <?php selected($s->get('stripe_mode'),'test'); ?>><?php esc_html_e('Test','cfqm'); ?></option>
            <option value="live" <?php selected($s->get('stripe_mode'),'live'); ?>><?php esc_html_e('Live','cfqm'); ?></option>
          </select>
        </td>
      </tr>
      <tr>
        <th><?php esc_html_e( 'Test Publishable Key', 'cfqm' ); ?></th>
        <td><input type="text" name="cfqm_settings[stripe_test_pk]" value="<?php echo esc_attr($s->get('stripe_test_pk')); ?>" class="regular-text" placeholder="pk_test_…"></td>
      </tr>
      <tr>
        <th><?php esc_html_e( 'Test Secret Key', 'cfqm' ); ?></th>
        <td><input type="password" name="cfqm_settings[stripe_test_sk]" value="<?php echo esc_attr($s->get('stripe_test_sk')); ?>" class="regular-text" placeholder="sk_test_…"></td>
      </tr>
      <tr>
        <th><?php esc_html_e( 'Live Publishable Key', 'cfqm' ); ?></th>
        <td><input type="text" name="cfqm_settings[stripe_live_pk]" value="<?php echo esc_attr($s->get('stripe_live_pk')); ?>" class="regular-text" placeholder="pk_live_…"></td>
      </tr>
      <tr>
        <th><?php esc_html_e( 'Live Secret Key', 'cfqm' ); ?></th>
        <td><input type="password" name="cfqm_settings[stripe_live_sk]" value="<?php echo esc_attr($s->get('stripe_live_sk')); ?>" class="regular-text" placeholder="sk_live_…"></td>
      </tr>
      <tr>
        <th><?php esc_html_e( 'Webhook Secret', 'cfqm' ); ?></th>
        <td><input type="password" name="cfqm_settings[stripe_webhook_secret]" value="<?php echo esc_attr($s->get('stripe_webhook_secret')); ?>" class="regular-text" placeholder="whsec_…">
          <p class="description"><?php printf(
            esc_html__('Webhook endpoint: %s', 'cfqm'),
            '<code>' . esc_url(rest_url('cfqm/v1/stripe-webhook')) . '</code>'
          ); ?></p>
        </td>
      </tr>
    </table>

    <!-- ── Email ─────────────────────────────────────────────────── -->
    <h2 class="cfqm-section-title">✉️ <?php esc_html_e( 'Email', 'cfqm' ); ?></h2>
    <table class="form-table">
      <tr>
        <th><?php esc_html_e( '"From" Name', 'cfqm' ); ?></th>
        <td><input type="text" name="cfqm_settings[email_from_name]" value="<?php echo esc_attr($s->get('email_from_name')); ?>" class="regular-text"></td>
      </tr>
      <tr>
        <th><?php esc_html_e( '"From" Address', 'cfqm' ); ?></th>
        <td><input type="email" name="cfqm_settings[email_from_address]" value="<?php echo esc_attr($s->get('email_from_address')); ?>" class="regular-text" placeholder="quotes@fixdly.com"></td>
      </tr>
    </table>

    <!-- ── Geo-matching ──────────────────────────────────────────── -->
    <h2 class="cfqm-section-title">📍 <?php esc_html_e( 'Geo-Matching', 'cfqm' ); ?></h2>
    <table class="form-table">
      <tr>
        <th><?php esc_html_e( 'Query Pool Size', 'cfqm' ); ?></th>
        <td><input type="number" name="cfqm_settings[query_pool_size]" value="<?php echo esc_attr($s->get('query_pool_size',10)); ?>" min="1" max="50" class="small-text">
          <p class="description"><?php esc_html_e('Top N tradespeople notified per homeowner query. Default: 10.','cfqm'); ?></p>
        </td>
      </tr>
      <tr>
        <th><?php esc_html_e( 'Response Cap', 'cfqm' ); ?></th>
        <td><input type="number" name="cfqm_settings[response_cap]" value="<?php echo esc_attr($s->get('response_cap',5)); ?>" min="1" max="20" class="small-text">
          <p class="description"><?php esc_html_e('First N responses accepted per query (3–5 recommended). Default: 5.','cfqm'); ?></p>
        </td>
      </tr>
    </table>

    <!-- ── Pages ─────────────────────────────────────────────────── -->
    <h2 class="cfqm-section-title">📄 <?php esc_html_e( 'WordPress Pages', 'cfqm' ); ?></h2>
    <p class="description" style="margin-bottom:12px"><?php esc_html_e('Create pages with the appropriate shortcodes, then assign them here.','cfqm'); ?></p>
    <table class="form-table">
      <?php
      $page_settings = [
        'page_portal'          => ['Customer Quote Portal',    '[cfqm_quote_portal]'],
        'page_dashboard_hw'    => ['Homeowner Dashboard',      '[cfqm_homeowner_dashboard]'],
        'page_dashboard_trade' => ['Trades Dashboard',         '[cfqm_trades_dashboard]'],
        'page_query_form'      => ['Homeowner Query Form',     '[cfqm_query_form]'],
        'page_magic_link'      => ['Magic Link / Login Page',  '[cfqm_magic_link_form]'],
      ];
      foreach ( $page_settings as $key => [$label, $shortcode] ) : ?>
      <tr>
        <th><?php echo esc_html($label); ?></th>
        <td>
          <select name="cfqm_settings[<?php echo esc_attr($key); ?>]">
            <option value="0"><?php esc_html_e('— Select —','cfqm'); ?></option>
            <?php foreach ($pages as $p) : ?>
              <option value="<?php echo esc_attr($p->ID); ?>" <?php selected($s->get($key,0),$p->ID); ?>>
                <?php echo esc_html($p->post_title); ?>
              </option>
            <?php endforeach; ?>
          </select>
          <code style="margin-left:8px"><?php echo esc_html($shortcode); ?></code>
        </td>
      </tr>
      <?php endforeach; ?>
    </table>

    <?php submit_button( __( 'Save Settings', 'cfqm' ) ); ?>
  </form>
</div>
