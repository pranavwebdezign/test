<?php
/**
 * View for the reviewer tooltip.
 *
 * @since 1.0.0
 */

// phpcs:disable VariableAnalysis.CodeAnalysis.VariableAnalysis.UndefinedVariable

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// If the excerpt contains an #author_bio tag, replace it here since it will otherwise be replaced with the
// author and not the reviewer.
if ( ! empty( $data['reviewerMetaData']['authorExcerpt'] ) ) {
	$bio = get_the_author_meta( 'description', $data['reviewerId'] );
	$data['reviewerMetaData']['authorExcerpt'] = str_replace( '#author_bio', $bio, $data['reviewerMetaData']['authorExcerpt'] );
}

$reviewerNameClasses = [
	'aioseo-reviewer-name',
	$data['attributes']['showTooltip'] ? 'aioseo-tooltip-underline' : ''
];

$attributes = [ 'class' => 'aioseo-reviewer' ];
$blockProps = $this->isRenderingBlock && function_exists( 'get_block_wrapper_attributes' )
	? get_block_wrapper_attributes( $attributes ) // phpcs:ignore AIOSEO.WpFunctionUse.NewFunctions.get_block_wrapper_attributesFound
	: 'class="aioseo-reviewer"';
?>
<?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
<span <?php echo $blockProps; ?>>
	<?php do_action( 'aioseo_eeat_reviewer_tooltip_start', $data['reviewerId'] ); ?>

	<?php
	if ( $data['attributes']['showLabel'] ) {
		?>
		<span class="aioseo-reviewer-text"><?php echo esc_html( $data['labels']['reviewedBy'] ); ?></span>
		<?php
	}

	if ( $data['reviewerImageUrl'] && $data['attributes']['showImage'] ) {
		?>
		<img class="aioseo-reviewer-image" src="<?php echo esc_attr( esc_url( $data['reviewerImageUrl'] ) ); ?>" alt="<?php echo esc_html( $data['labels']['reviewerImageAlt'] ); ?>"/>
		<?php
	}
	?>
	<span class="<?php esc_attr_e( implode( ' ', $reviewerNameClasses ) ); //phpcs:ignore AIOSEO.Wp.I18n ?>">
		<?php echo esc_html( $data['reviewerName'] ); ?>

		<?php
		if ( $data['attributes']['showTooltip'] ) {
			?>
			<span class="aioseo-reviewer-tooltip">
				<svg
					xmlns="http://www.w3.org/2000/svg"
					width="24"
					height="14"
					viewBox="0 0 24 14"
					fill="none"
				>
					<path
						d="M1.20711 11.5L10.9393 1.76777C11.5251 1.18198 12.4749 1.18198 13.0607 1.76777L22.7929 11.5H1.20711Z"
						fill="white"
						stroke="black"
					/>
					<path
						d="M10.5858 2.91421L0 13.5H24L13.4142 2.91421C12.6332 2.13317 11.3668 2.13317 10.5858 2.91421Z"
						fill="white"
					/>
				</svg>

				<div class="aioseo-reviewer-tooltip-content">
					<div class="aioseo-reviewer-tooltip-header">
						<?php do_action( 'aioseo_eeat_reviewer_tooltip_header', $data['reviewerId'] ); ?>

						<?php
						if ( $data['reviewerImageUrl'] ) {
							?>
							<img
								class="aioseo-reviewer-tooltip-image"
								src="<?php echo esc_attr( esc_url( $data['reviewerImageUrl'] ) ); ?>"
								alt="<?php echo esc_html( $data['labels']['reviewerImageAlt'] ); ?>"
							/>
							<?php
						}
						?>

						<span><?php echo esc_html( $data['reviewerName'] ); ?></span>
					</div>

					<div class="aioseo-reviewer-tooltip-main">
						<?php do_action( 'aioseo_eeat_reviewer_tooltip_main_start', $data['reviewerId'] ); ?>

						<?php
						echo wp_kses_post(
							! empty( $data['reviewerMetaData']['authorExcerpt'] )
								? aioseo()->tags->replaceTags( $data['reviewerMetaData']['authorExcerpt'], get_the_ID() )
								: ''
						);
						?>

						<?php do_action( 'aioseo_eeat_reviewer_tooltip_main_end', $data['reviewerId'] ); ?>
					</div>

					<div class="aioseo-reviewer-tooltio-footer">
						<?php do_action( 'aioseo_eeat_reviewer_tooltip_footer', $data['reviewerId'] ); ?>

						<?php
						if ( $data['reviewerUrl'] && ! empty( $data['attributes']['showBioLink'] ) ) {
							?>
							<div class="reviewer-bio-link">
								<a href="<?php echo esc_attr( esc_url( $data['reviewerUrl'] ) ); ?>"><?php echo esc_html( $data['labels']['seeFullBio'] ); ?></a>

								<a
									class="reviewer-bio-link-caret"
									href="<?php echo esc_attr( esc_url( $data['reviewerUrl'] ) ); ?>"
									aria-label="<?php echo esc_attr_e( 'See Full Bio', 'aioseo-eeat' ); ?>"
								>
									<svg
										xmlns="http://www.w3.org/2000/svg"
										width="16"
										height="17"
										viewBox="0 0 16 17"
									>
										<path
											d="M5.52978 5.44L8.58312 8.5L5.52979 11.56L6.46979 12.5L10.4698 8.5L6.46978 4.5L5.52978 5.44Z"
										/>
									</svg>
								</a>
							</div>
							<?php
						}
						?>
					</div>
				</div>
			</span>
			<?php
		}
		?>
	</span>

	<?php do_action( 'aioseo_eeat_reviewer_tooltip_end', $data['reviewerId'] ); ?>
</span>