<?php
/**
 * View for the author bio block.
 *
 * @since 1.0.0
 */

// phpcs:disable VariableAnalysis.CodeAnalysis.VariableAnalysis.UndefinedVariable

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( $showSampleDescription ) {
	?>
	<p class="aioseo-author-bio-compact-site-editor-disclaimer"><?php esc_html_e( 'Below is a sample of how this block will look in posts & author archives:', 'aioseo-eeat' ); ?></p>
	<?php
}

$attributes = [ 'class' => 'aioseo-author-bio-compact' ];
$blockProps = $this->isRenderingBlock && function_exists( 'get_block_wrapper_attributes' )
	? get_block_wrapper_attributes( $attributes ) // phpcs:ignore AIOSEO.WpFunctionUse.NewFunctions.get_block_wrapper_attributesFound
	: 'class="aioseo-author-bio-compact"';
?>
<?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
<div <?php echo $blockProps; ?>>
	<?php
	if ( $data['authorImageUrl'] ) {
		?>
		<div class="aioseo-author-bio-compact-left">
			<img class="aioseo-author-bio-compact-image" src="<?php echo esc_attr( esc_url( $data['authorImageUrl'] ) ); ?>" alt="<?php echo esc_attr( $data['labels']['authorImageAlt'] ); ?>"/>
		</div>
		<?php
	}
	?>
	<div class="aioseo-author-bio-compact-right">
		<div class="aioseo-author-bio-compact-header">
			<span class="author-name"><?php echo esc_html( $data['authorName'] ); ?></span>
			<?php
			if ( ! empty( $data['authorMetaData']['jobTitle'] ) ) {
				?>
				<span class="author-job-title"><?php echo esc_html( $data['authorMetaData']['jobTitle'] ); ?></span>
				<?php
			}
			?>
		</div>

		<div class="aioseo-author-bio-compact-main">
			<?php do_action( 'aioseo_eeat_author_bio_main_start', $data['authorId'] ); ?>

			<?php
			echo wp_kses_post(
				! empty( $data['authorMetaData']['authorExcerpt'] )
					? aioseo()->tags->replaceTags( $data['authorMetaData']['authorExcerpt'], get_the_ID() )
					: ''
			);
			?>

			<?php
			if ( $data['authorUrl'] && ! empty( $data['attributes']['showBioLink'] ) ) {
				?>
				<div class="author-bio-link">
					<a href="<?php echo esc_attr( esc_url( $data['authorUrl'] ) ); ?>"><?php echo esc_html( $data['labels']['seeFullBio'] ); ?></a>

					<a href="<?php echo esc_attr( esc_url( $data['authorUrl'] ) ); ?>" aria-label="<?php esc_attr_e( 'See Full Bio', 'aioseo-eeat' ); ?>">
						<svg
							xmlns="http://www.w3.org/2000/svg"
							width="16"
							height="17"
							viewBox="0 0 16 17"
							fill="none"
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

			<?php do_action( 'aioseo_eeat_author_bio_main_end', $data['authorId'] ); ?>
		</div>

		<div class="aioseo-author-bio-compact-footer">
			<?php do_action( 'aioseo_eeat_author_bio_footer_start', $data['authorId'] ); ?>

			<?php
			if ( ! empty( $data['authorMetaData']['knowsAbout'] ) ) {
				?>
				<div class="author-expertises">
				<?php
				foreach ( $data['authorMetaData']['knowsAbout'] as $expertise ) {
					?>
					<span class="author-expertise"><?php echo esc_html( $expertise['label'] ); ?></span>
					<?php
				}
				?>
				</div>
				<?php
			}
			?>

			<?php
			if ( ! empty( $data['socialUrls'] ) ) {
				?>
				<div class="author-socials">
				<?php
				foreach ( $data['socialUrls'] as $network => $url ) {
					if (
						! $url ||
						! in_array( $network, array_keys( $data['socialIcons'] ), true ) ||
						empty( $data['socialIcons'][ $network ] )
					) {
						continue;
					}
					?>
					<a
						class="aioseo-social-icon-<?php echo esc_attr( $network ); ?>"
						href="<?php echo esc_attr( esc_url( $url ) ); ?>"
						rel="noopener" target="_blank"
						aria-label="<?php echo esc_attr( $network ); ?>"
					>
						<img src="<?php echo esc_attr( $data['socialIcons'][ $network ] ); ?>" alt="<?php echo esc_attr( $data['labels']['socialsIconAlt'] ); ?>"/>
					</a>
					<?php
				}
				?>
				</div>
				<?php
			}
			?>

			<?php do_action( 'aioseo_eeat_author_bio_footer_end', $data['authorId'] ); ?>
		</div>
	</div>
</div>