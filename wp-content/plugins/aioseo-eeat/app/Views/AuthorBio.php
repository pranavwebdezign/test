<?php
/**
 * View for the author archive bio block.
 *
 * @since 1.0.0
 */

// phpcs:disable VariableAnalysis.CodeAnalysis.VariableAnalysis.UndefinedVariable
// phpcs:disable Generic.Files.LineLength.MaxExceeded

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$alumniOf = [];
if ( ! empty( $data['authorMetaData']['alumniOf'] ) ) {
	foreach ( $data['authorMetaData']['alumniOf'] as $alumniOfData ) {
		if ( empty( $alumniOfData['name'] ) ) {
			continue;
		}

		$alumniOf[] = $alumniOfData['name'];
	}
}

if ( $showSampleDescription ) {
	?>
	<p class="aioseo-author-bio-site-editor-disclaimer"><?php esc_html_e( 'Below is a sample of how this block will look in posts & author archives:', 'aioseo-eeat' ); ?></p>
	<?php
}

$attributes = [ 'class' => 'aioseo-author-bio' ];
$blockProps = $this->isRenderingBlock && function_exists( 'get_block_wrapper_attributes' )
	? get_block_wrapper_attributes( $attributes ) // phpcs:ignore AIOSEO.WpFunctionUse.NewFunctions.get_block_wrapper_attributesFound
	: 'class="aioseo-author-bio"';
?>
<?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
<div <?php echo $blockProps; ?>>
	<div class="aioseo-author-bio-header">
		<?php do_action( 'aioseo_eeat_author_archive_bio_header_start', $data['authorId'] ); ?>

		<?php
		if ( $data['authorImageUrl'] ) {
			?>
			<div class="aioseo-author-bio-header-left">
				<img class="aioseo-author-bio-image" src="<?php echo esc_attr( esc_url( $data['authorImageUrl'] ) ); ?>" alt="<?php echo esc_attr( $data['labels']['authorImageAlt'] ); ?>"/>
			</div>
			<?php
		}
		?>
		<div class="aioseo-author-bio-header-right">
			<div>
				<span class="author-name"><?php echo esc_html( $data['authorName'] ); ?></span>
				<?php
				if ( ! empty( $data['authorMetaData']['jobTitle'] ) ) {
					?>
					<span class="author-job-title"><?php echo esc_html( $data['authorMetaData']['jobTitle'] ); ?></span>
					<?php
				}
				?>
			</div>

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
			if ( ! empty( $alumniOf ) ) {
				?>
				<span><?php echo esc_html( $data['labels']['alumniOf'] ); ?></span>
				<?php
				for ( $index = 0; $index < count( $alumniOf ); $index++ ) {
					$name = $alumniOf[ $index ];
					if ( isset( $alumniOf[ $index + 1 ] ) ) {
						$name .= ',';
					}

					if ( ! empty( $data['authorMetaData']['alumniOf'][ $index ]['url'] ) ) {
						?>
							<a href="<?php echo esc_attr( esc_url( $data['authorMetaData']['alumniOf'][ $index ]['url'] ) ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $name ); ?></a>
						<?php
					} else {
						?>
							<span><?php echo esc_html( $name ); ?></span>
						<?php
					}
				}
			}

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

			<?php do_action( 'aioseo_eeat_author_archive_bio_header_end', $data['authorId'] ); ?>
		</div>
	</div>

	<?php
	if ( ! empty( $data['authorMetaData']['authorBio'] ) ) {
		?>
		<div class="aioseo-author-bio-main">
			<?php do_action( 'aioseo_eeat_author_bio_main_start', $data['authorId'] ); ?>

			<?php
			echo wp_kses_post(
				! empty( $data['authorMetaData']['authorBio'] )
					? do_shortcode( aioseo()->tags->replaceTags( $data['authorMetaData']['authorBio'], get_the_ID() ) )
					: ''
			);
			?>

			<?php do_action( 'aioseo_eeat_author_bio_main_end', $data['authorId'] ); ?>
		</div>
		<?php
	}
	?>
</div>