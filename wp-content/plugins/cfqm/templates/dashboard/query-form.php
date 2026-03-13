<?php
/**
 * Template: Homeowner Guest Query Submission Form
 * No login required.
 */
defined( 'ABSPATH' ) || exit;
$brand      = esc_html( CFQM\Settings::instance()->get( 'brand_name', 'Fixdly' ) );
$categories = get_terms( [ 'taxonomy' => 'cfqm_category', 'hide_empty' => false, 'orderby' => 'name' ] );
?>
<div class="cfqm-query-form" id="cfqm-query-form">
  <div class="cfqm-query-form__inner">

    <h2><?php printf( esc_html__( 'Get Quotes from Local %s Professionals', 'cfqm' ), $brand ); ?></h2>
    <p><?php esc_html_e( 'Tell us about your job and up to 5 local tradespeople will send you a detailed quote — free and no obligation.', 'cfqm' ); ?></p>

    <form id="cfqm-query-submit-form" class="cfqm-form" novalidate>
      <?php wp_nonce_field( 'cfqm_query_submit', 'cfqm_qf_nonce' ); ?>

      <!-- Trade category -->
      <div class="cfqm-form__group">
        <label for="cfqm-qf-category" class="cfqm-form__label cfqm-form__label--required">
          <?php esc_html_e( 'Type of Work', 'cfqm' ); ?>
        </label>
        <select name="category_id" id="cfqm-qf-category" class="cfqm-input" required>
          <option value=""><?php esc_html_e( '— Select trade —', 'cfqm' ); ?></option>
          <?php if ( ! is_wp_error( $categories ) ) : ?>
            <?php foreach ( $categories as $cat ) : ?>
              <option value="<?php echo esc_attr( $cat->term_id ); ?>">
                <?php echo esc_html( $cat->name ); ?>
              </option>
            <?php endforeach; ?>
          <?php endif; ?>
        </select>
      </div>

      <!-- Postcode -->
      <div class="cfqm-form__group">
        <label for="cfqm-qf-postcode" class="cfqm-form__label cfqm-form__label--required">
          <?php esc_html_e( 'Your Postcode', 'cfqm' ); ?>
        </label>
        <input type="text" name="postcode" id="cfqm-qf-postcode"
               class="cfqm-input" placeholder="e.g. SW1A 1AA" required maxlength="10">
        <p class="cfqm-form__hint"><?php esc_html_e( 'Used to match you with local tradespeople. UK postcodes only.', 'cfqm' ); ?></p>
      </div>

      <!-- Job description -->
      <div class="cfqm-form__group">
        <label for="cfqm-qf-desc" class="cfqm-form__label cfqm-form__label--required">
          <?php esc_html_e( 'Describe the Job', 'cfqm' ); ?>
        </label>
        <textarea name="job_desc" id="cfqm-qf-desc" class="cfqm-textarea"
                  rows="5" required placeholder="<?php esc_attr_e( 'Please describe the work you need done, including any relevant details such as room size, existing issues, or materials you have in mind…', 'cfqm' ); ?>"></textarea>
      </div>

      <!-- Project label -->
      <div class="cfqm-form__group">
        <label for="cfqm-qf-label" class="cfqm-form__label">
          <?php esc_html_e( 'Project Name (optional)', 'cfqm' ); ?>
        </label>
        <input type="text" name="project_label" id="cfqm-qf-label"
               class="cfqm-input" placeholder="<?php esc_attr_e( 'e.g. Home Extension, Bathroom Refurb', 'cfqm' ); ?>">
        <p class="cfqm-form__hint"><?php esc_html_e( 'Helps you organise multiple quotes in your dashboard.', 'cfqm' ); ?></p>
      </div>

      <hr class="cfqm-form__divider">
      <h3><?php esc_html_e( 'Your Contact Details', 'cfqm' ); ?></h3>

      <div class="cfqm-form__row">
        <div class="cfqm-form__group cfqm-form__group--half">
          <label for="cfqm-qf-name" class="cfqm-form__label cfqm-form__label--required">
            <?php esc_html_e( 'Full Name', 'cfqm' ); ?>
          </label>
          <input type="text" name="hw_name" id="cfqm-qf-name" class="cfqm-input" required>
        </div>
        <div class="cfqm-form__group cfqm-form__group--half">
          <label for="cfqm-qf-phone" class="cfqm-form__label">
            <?php esc_html_e( 'Phone (optional)', 'cfqm' ); ?>
          </label>
          <input type="tel" name="hw_phone" id="cfqm-qf-phone" class="cfqm-input">
        </div>
      </div>

      <div class="cfqm-form__group">
        <label for="cfqm-qf-email" class="cfqm-form__label cfqm-form__label--required">
          <?php esc_html_e( 'Email Address', 'cfqm' ); ?>
        </label>
        <input type="email" name="hw_email" id="cfqm-qf-email" class="cfqm-input" required
               placeholder="you@example.com" autocomplete="email">
        <p class="cfqm-form__hint"><?php esc_html_e( 'Quotes will be sent to this address. We\'ll never share it without your permission.', 'cfqm' ); ?></p>
      </div>

      <div class="cfqm-form__group">
        <label class="cfqm-checkbox-label">
          <input type="checkbox" name="gdpr_consent" id="cfqm-qf-gdpr" required>
          <?php printf(
            esc_html__( "I agree to %s's Privacy Policy and consent to local tradespeople contacting me about this query.", 'cfqm' ),
            $brand
          ); ?>
        </label>
      </div>

      <div class="cfqm-form__group">
        <button type="submit" id="cfqm-qf-submit" class="cfqm-btn cfqm-btn--primary cfqm-btn--full">
          <?php esc_html_e( 'Get My Free Quotes', 'cfqm' ); ?>
          <span class="cfqm-spinner" id="cfqm-qf-spinner" style="display:none"></span>
        </button>
      </div>

      <p class="cfqm-query-form__disclaimer">
        <?php esc_html_e( 'By submitting you agree that up to 5 local tradespeople will receive your query details and contact you with a quote. No registration required.', 'cfqm' ); ?>
      </p>
    </form>

    <!-- Success state -->
    <div id="cfqm-qf-success" class="cfqm-query-form__success" style="display:none">
      <div class="cfqm-success-icon">✓</div>
      <h3><?php esc_html_e( 'Query submitted!', 'cfqm' ); ?></h3>
      <p><?php esc_html_e( 'We\'ve notified local tradespeople who match your job. You\'ll receive quotes by email shortly.', 'cfqm' ); ?></p>
      <p><a href="#" id="cfqm-qf-reset" class="cfqm-btn cfqm-btn--outline"><?php esc_html_e( 'Submit another query', 'cfqm' ); ?></a></p>
    </div>

    <!-- Error state -->
    <div id="cfqm-qf-error" class="cfqm-alert cfqm-alert--error" style="display:none"></div>

  </div>
</div>
