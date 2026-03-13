<?php
namespace AIOSEO\Plugin\Addon\VideoSitemap\VideoSitemap;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles video URL extraction from other plugins/themes.
 *
 * @since 1.1.4
 */
class ThirdParty {
	/**
	 * The post object.
	 *
	 * @since 1.1.4
	 *
	 * @var \WP_Post|object
	 */
	private $post;

	/**
	 * Returns all video URLs for the given post that we can extract from other plugins/themes.
	 *
	 * @since 1.1.4
	 *
	 * @param  \WP_Post|object $post The post object.
	 * @return array                 The video URLs.
	 */
	public function getVideoUrls( $post ) {
		$this->post = $post;

		$methodNames = [
			'bricks',
			'elementor',
			'oxygen'
		];

		$videoUrls = [];
		foreach ( $methodNames as $methodName ) {
			$videoUrls = array_merge(
				$videoUrls,
				$this->{$methodName}()
			);
		}

		return array_unique( $videoUrls );
	}

	/**
	 * Returns a list of video URLs for Bricks.
	 *
	 * @since 1.1.24
	 *
	 * @return array The video URLs.
	 */
	private function bricks() {
		$bricksIntegration = aioseo()->standalone->pageBuilderIntegrations['bricks'] ?? null;
		if ( empty( $bricksIntegration ) || ! $bricksIntegration->isBuiltWith( $this->post->ID ) ) {
			return [];
		}

		if ( ! class_exists( '\Bricks\Database' ) ) {
			return [];
		}

		$bricksData = \Bricks\Database::get_data( $this->post->ID, 'content' );
		if ( empty( $bricksData ) || ! is_array( $bricksData ) ) {
			return [];
		}

		$videoUrls = [];
		foreach ( $bricksData as $element ) {
			$url = $this->extractBricksVideoFromElement( $element );
			if ( $url ) {
				$videoUrls[] = $url;
			}
		}

		return array_values( array_unique( $videoUrls ) );
	}

	/**
	 * Extracts video URL from a Bricks element if it's a video element.
	 *
	 * @since 1.1.24
	 *
	 * @param  array       $element The Bricks element data.
	 * @return string|null          The video URL or null if not a video element.
	 */
	private function extractBricksVideoFromElement( $element ) {
		// Check if this is a video element.
		if ( ! isset( $element['name'] ) || 'video' !== $element['name'] ) {
			return null;
		}

		return $this->extractBricksVideoUrl( $element['settings'] ?? [] );
	}

	/**
	 * Extracts the video URL from Bricks video element settings based on the selected videoType.
	 *
	 * @since 1.1.24
	 *
	 * @param  array       $settings The video element settings.
	 * @return string|null           The video URL or null if not found.
	 */
	private function extractBricksVideoUrl( $settings ) {
		// Get the selected video type (defaults to 'youtube' in Bricks).
		$videoType = $settings['videoType'] ?? 'youtube';

		switch ( $videoType ) {
			case 'youtube':
				// YouTube can be stored as full URL or just the ID.
				// Note: Bricks uses 'youTubeId' (capital T), not 'youtubeId'.
				$youTubeId = $settings['youTubeId'] ?? '';
				if ( ! empty( $youTubeId ) ) {
					// If it's already a URL, return it; otherwise construct the URL.
					if ( filter_var( $youTubeId, FILTER_VALIDATE_URL ) ) {
						return $youTubeId;
					}

					return 'https://www.youtube.com/watch?v=' . $youTubeId;
				}
				break;

			case 'vimeo':
				$vimeoId = $settings['vimeoId'] ?? '';
				if ( ! empty( $vimeoId ) ) {
					// If it's already a URL, return it; otherwise construct the URL.
					if ( filter_var( $vimeoId, FILTER_VALIDATE_URL ) ) {
						return $vimeoId;
					}

					return 'https://vimeo.com/' . $vimeoId;
				}
				break;

			case 'media':
				// Media library video - get the URL from the media object.
				$media = $settings['media'] ?? [];
				if ( ! empty( $media['url'] ) ) {
					return $media['url'];
				}
				// Fallback: try to get URL from attachment ID.
				if ( ! empty( $media['id'] ) ) {
					return wp_get_attachment_url( $media['id'] );
				}
				break;

			case 'file':
				// External file URL.
				$fileUrl = $settings['fileUrl'] ?? '';
				if ( ! empty( $fileUrl ) ) {
					return $fileUrl;
				}
				break;

			case 'meta':
				// Meta field - would need to resolve the meta value.
				// This is complex as it depends on dynamic data; skip for now.
				break;

			default:
				// Unknown video type - return null to skip.
				break;
		}

		return null;
	}

	/**
	 * Returns a list of video URLs for Oxygen.
	 *
	 * @since 1.1.24
	 *
	 * @return array The video URLs.
	 */
	private function oxygen() {
		$oxygenIntegration = aioseo()->standalone->pageBuilderIntegrations['oxygen'] ?? null;
		if ( empty( $oxygenIntegration ) || ! $oxygenIntegration->isBuiltWith( $this->post->ID ) ) {
			return [];
		}

		$renderedContent = $oxygenIntegration->processContent( $this->post->ID );
		if ( empty( $renderedContent ) ) {
			return [];
		}

		$videoUrls = [];

		preg_match_all( '#<iframe[^>]+src=["\']([^"\']+)["\']#i', $renderedContent, $iframeMatches );
		if ( ! empty( $iframeMatches[1] ) ) {
			$videoUrls = array_merge( $videoUrls, $iframeMatches[1] );
		}

		preg_match_all( '#<(?:video|source)[^>]+src=["\']([^"\']+)["\']#i', $renderedContent, $videoTagMatches );
		if ( ! empty( $videoTagMatches[1] ) ) {
			foreach ( $videoTagMatches[1] as $url ) {
				$videoUrls[] = aioseo()->helpers->makeUrlAbsolute( $url );
			}
		}

		return array_values( array_unique( array_filter( $videoUrls ) ) );
	}

	/**
	 * Returns a list of video URLs for Elementor.
	 * We support the regular Video component out-of-the-box but need custom support for the Video Playlist component.
	 *
	 * @since 1.1.4
	 *
	 * @return array The video URLs.
	 */
	private function elementor() {
		$postId = $this->post->ID;
		if ( ! aioseo()->standalone->pageBuilderIntegrations['elementor']->isBuiltWith( $postId ) ) {
			return [];
		}

		$elementorData = get_post_meta( $postId, '_elementor_data', true );
		if ( empty( $elementorData ) ) {
			return [];
		}

		$elementorData = json_decode( $elementorData );
		if ( empty( $elementorData ) ) {
			return [];
		}

		$widgets = [];
		foreach ( $elementorData as $section ) {
			$widgets = array_merge(
				$widgets,
				$this->getWidgets( $section )
			);
		}

		$videoUrls = [];
		foreach ( $widgets as $widget ) {
			$videoUrls = array_merge(
				$videoUrls,
				$this->extractElementorVideo( $widget )
			);
		}

		return $videoUrls;
	}

	/**
	 * Extract videos from elementor Video widget.
	 *
	 * @since 1.1.18
	 *
	 * @param  object $data The data of the video widget.
	 * @return array        The array of video urls found.
	 */
	private function extractElementorVideo( $data ) {
		$videoUrls = [];

		$type = $data->settings->video_type ?? $data->settings->type ?? ( ! empty( $data->settings->show_image_overlay ) ? 'overlay' : '' );

		// Default sample urls for all providers.
		$defaultUrls = [
			'youtube'     => 'https://www.youtube.com/watch?v=XHOmBV4js_E',
			'vimeo'       => 'https://vimeo.com/235215203',
			'dailymotion' => 'https://www.dailymotion.com/video/x6tqhqb',
		];

		if ( empty( $type ) ) {
			$videoUrls[] = $data->settings->youtube_url;

			return $videoUrls;
		}

		// Recurse widgets that have tabs.
		if ( ! empty( $data->settings->tabs ) ) {
			foreach ( $data->settings->tabs as $tab ) {
				$videoUrls = array_merge(
					$videoUrls,
					$this->extractElementorVideo( $tab )
				);
			}

			return $videoUrls;
		}

		// Check type of video and extract the URL.
		switch ( $type ) {
			case 'vimeo':
				$videoUrls[] = $data->settings->vimeo_url;
				break;
			case 'dailymotion':
				$videoUrls[] = $data->settings->dailymotion_url;
				break;
			case 'videopress':
				$isInsertUrl   = isset( $data->settings->insert_url ) && 'yes' === $data->settings->insert_url;
				$hasVideopress = isset( $data->settings->videopress_url );
				$hasHostedUrl  = isset( $data->settings->hosted_url->url );

				if ( $isInsertUrl ) {
					$videoUrls[] = $hasVideopress ? $data->settings->videopress_url : ( $hasHostedUrl ? $data->settings->hosted_url->url : '' );
				} else {
					$videoUrls[] = $hasHostedUrl ? $data->settings->hosted_url->url : $data->settings->videopress_url;
				}
				break;
			case 'hosted':
				$videoUrls[] = ( isset( $data->settings->insert_url ) && 'yes' === $data->settings->insert_url ) ? $data->settings->external_url->url ?? '' : $data->settings->hosted_url->url ?? '';
				break;
			case 'overlay':
				if ( $data->settings->youtube_url !== $defaultUrls['youtube'] ) {
					$videoUrls[] = $data->settings->youtube_url;
					break;
				}

				if ( $data->settings->vimeo_url !== $defaultUrls['vimeo'] ) {
					$videoUrls[] = $data->settings->vimeo_url;
					break;
				}

				if ( $data->settings->dailymotion_url !== $defaultUrls['dailymotion'] ) {
					$videoUrls[] = $data->settings->dailymotion_url;
					break;
				}

				break;
			default:
				$videoUrls[] = $data->settings->youtube_url;
				break;
		}

		return array_values( array_filter( $videoUrls ) );
	}

	/**
	 * Returns all widgets that are nested inside the given Elementor element.
	 *
	 * @since 1.1.4
	 *
	 * @param  object $element The Elementor object.
	 * @return array           The nested widgets.
	 */
	private function getWidgets( $element ) {
		if ( ! isset( $element->elements ) ) {
			return [];
		}

		$allowedWidgets = [ 'video-playlist', 'video' ];
		$widgets = [];

		// Use recursion to grab all nested widgets that are grandchildren (or even deeper).
		foreach ( $element->elements as $childElement ) {
			// Grab all video playlist widgets that are children of the current element.
			if ( 'widget' === $childElement->elType && in_array( $childElement->widgetType, $allowedWidgets, true ) ) {
				$widgets[] = $childElement;
			}

			// Use recursion to grab all nested widgets that are grandchildren (or even deeper).
			$widgets = array_merge(
				$widgets,
				$this->getWidgets( $childElement )
			);
		}

		return $widgets;
	}
}