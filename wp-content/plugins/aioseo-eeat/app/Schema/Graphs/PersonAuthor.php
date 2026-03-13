<?php
namespace AIOSEO\Plugin\Addon\Eeat\Schema\Graphs;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builds our schema.
 *
 * @since 1.0.0
 */
class PersonAuthor {
	/**
	 * Filter the graphs that need to be output.
	 *
	 * @since 1.0.0
	 *
	 * @param  int   $userId The user ID.
	 * @param  array $data   The graphs that need to be output.
	 * @return array         A list of graphs that need to be output.
	 */
	public function get( $userId, $data ) {
		$userId = (int) $userId;
		if ( ! $userId ) {
			return $data;
		}

		if ( ! aioseo()->license->hasAddonFeature( 'aioseo-eeat', 'author-info' ) ) {
			return $data;
		}

		$authorMetaData = aioseoEeat()->helpers->getAuthorMetaData( $userId );
		if (
			empty( $authorMetaData ) ||
			( isset( $authorMetaData['enabled'] ) && ! $authorMetaData['enabled'] )
		) {
			return $data;
		}

		if ( ! empty( $authorMetaData['authorBio'] ) ) {
			$data['description'] = wp_strip_all_tags( do_shortcode( $authorMetaData['authorBio'] ) );
		}

		if ( ! empty( $authorMetaData['jobTitle'] ) ) {
			$data['jobTitle'] = $authorMetaData['jobTitle'];
		}

		if ( ! empty( $authorMetaData['alumniOf'] ) ) {
			$alumniOf = [];
			foreach ( $authorMetaData['alumniOf'] as $alumniOfData ) {
				if ( empty( $alumniOfData['name'] ) ) {
					continue;
				}

				$alumniOf[] = [
					'@type'  => 'EducationalOrganization',
					'name'   => $alumniOfData['name'],
					'sameAs' => $alumniOfData['url']
				];
			}

			$data['alumniOf'] = $alumniOf;
		}

		if ( ! empty( $authorMetaData['knowsAbout'] ) ) {
			$globalKnowsAbout = aioseoEeat()->options->eeat->globalKnowsAbout;
			$knowsAbout       = [];
			foreach ( $authorMetaData['knowsAbout'] as $expertise ) {
				$expertise['value'] = aioseo()->helpers->decodeHtmlEntities( $expertise['value'] );

				// Loop through the global knows about values and check if the value is in there.
				foreach ( $globalKnowsAbout as $globalKnowsAboutValue ) {
					$globalKnowsAboutValue['name'] = aioseo()->helpers->decodeHtmlEntities( $globalKnowsAboutValue['name'] );

					if ( $expertise['value'] === $globalKnowsAboutValue['name'] ) {
						$sameAsUrls = ! empty( $globalKnowsAboutValue['sameAsUrls'] ) ? json_decode( $globalKnowsAboutValue['sameAsUrls'] ) : [];
						$sameAsUrls = array_column( $sameAsUrls, 'value' );

						$knowsAbout[] = array_filter( [
							'@type'  => 'Thing',
							'name'   => sanitize_text_field( $globalKnowsAboutValue['name'] ),
							'url'    => esc_url( $globalKnowsAboutValue['url'] ),
							'sameAs' => $sameAsUrls
						] );
					}
				}
			}

			$data['knowsAbout'] = $knowsAbout;
		}

		if ( ! empty( $authorMetaData['award'] ) ) {
			$data['award'] = $this->processJsonValues( $authorMetaData['award'] );
		}

		if ( ! empty( $authorMetaData['knowsLanguage'] ) ) {
			$data['knowsLanguage'] = $this->processJsonValues( $authorMetaData['knowsLanguage'] );
		}

		if ( empty( $authorMetaData['authorImage'] ) ) {
			$authorMetaData['authorImage'] = get_avatar_url( $userId );
		}

		$data['image'] = [
			'@type' => 'ImageObject',
			'url'   => $authorMetaData['authorImage']
		];

		return $data;
	}

	/**
	 * Processes JSON encoded array of values for schema properties.
	 *
	 * @since 1.2.0
	 *
	 * @param  string $jsonData The JSON encoded data.
	 * @return array            Array of sanitized values.
	 */
	private function processJsonValues( $jsonData ) {
		$values     = [];
		$parsedData = json_decode( $jsonData ?? '' ) ?: [];
		if ( ! is_array( $parsedData ) ) {
			return $values;
		}

		foreach ( $parsedData as $item ) {
			if ( isset( $item->value ) && is_string( $item->value ) ) {
				$values[] = sanitize_text_field( aioseo()->helpers->decodeHtmlEntities( $item->value ) );
			}
		}

		return $values;
	}
}