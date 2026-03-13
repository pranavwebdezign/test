<?php
/**
 * Template: Magic Link / Homeowner Login Form
 * Variable: $message (string) – optional success/error message
 */
defined( 'ABSPATH' ) || exit;
$brand = esc_html( CFQM\Settings::instance()->get( 'brand_name', 'Fixdly' ) );
?>
<div class="cfqm-magic-link-form" id="cfqm-magic-link-form">

  <div class="cfqm-magic-link-form__inner">
    <h2 class="cfqm-magic-link-form__title">
      <?php printf( esc_html__( 'View Your %s Quotes', 'cfqm' ), $brand ); ?>
    </h2>
    <p class="cfqm-magic-link-form__intro">
      <?php esc_html_e( 'Enter the email address you used when submitting your query. We\'ll send you a secure link to view all your quotes — no password needed.', 'cfqm' ); ?>
    </p>

    <?php if ( ! empty( $message ) ) : ?>
      <div class="cfqm-alert cfqm-alert--info cfqm-magic-link-form__message">
        <?php echo esc_html( $message ); ?>
      </div>
    <?php endif; ?>

    <form class="cfqm-form" id="cfqm-magic-link-request-form" novalidate>
      <?php wp_nonce_field( 'cfqm_magic_link', 'cfqm_ml_nonce' ); ?>

      <div class="cfqm-form__group">
        <label for="cfqm-ml-email" class="cfqm-form__label">
          <?php esc_html_e( 'Your Email Address', 'cfqm' ); ?>
        </label>
        <input type="email"
               id="cfqm-ml-email"
               name="email"
               class="cfqm-input cfqm-input--lg"
               placeholder="you@example.com"
               required
               autocomplete="email">
      </div>

      <div class="cfqm-form__group">
        <button type="submit" id="cfqm-ml-submit" class="cfqm-btn cfqm-btn--primary cfqm-btn--full">
          <?php esc_html_e( 'Send My Quotes Link', 'cfqm' ); ?>
          <span class="cfqm-spinner" id="cfqm-ml-spinner" style="display:none"></span>
        </button>
      </div>

      <p class="cfqm-magic-link-form__note">
        <?php esc_html_e( 'The link is valid for 24 hours. Check your spam folder if it doesn\'t arrive within a minute.', 'cfqm' ); ?>
      </p>
    </form>

    <div id="cfqm-ml-success" class="cfqm-alert cfqm-alert--success" style="display:none">
      <strong><?php esc_html_e('Link sent!','cfqm'); ?></strong>
      <?php esc_html_e('Check your inbox for a link to view your quotes.','cfqm'); ?>
    </div>

    <div id="cfqm-ml-error" class="cfqm-alert cfqm-alert--error" style="display:none"></div>
  </div>

</div>
