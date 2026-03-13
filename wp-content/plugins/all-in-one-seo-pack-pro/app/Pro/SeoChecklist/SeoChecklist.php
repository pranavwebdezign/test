<?php
namespace AIOSEO\Plugin\Pro\SeoChecklist;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AIOSEO\Plugin\Common\SeoChecklist\SeoChecklist as CommonSeoChecklist;

class SeoChecklist extends CommonSeoChecklist {
	/**
	 * Register the checks.
	 *
	 * @since 4.9.4
	 *
	 * @return void
	 */
	protected function registerChecks() {
		parent::registerChecks();

		$this->checks[] = [
			'name'        => 'licenseActivated',
			'title'       => __( 'Activate Your License', 'aioseo-pro' ),
			'description' => __( 'Unlock all Pro features, addons and updates by activating your AIOSEO license key.', 'aioseo-pro' ), // phpcs:ignore Generic.Files.LineLength.MaxExceeded
			'priority'    => 'high',
			'time'        => [
				'label' => __( '1 minute', 'aioseo-pro' ),
				'value' => 60
			],
			'callback'    => 'licenseActivated',
			'capability'  => 'aioseo_general_settings',
			'actions'     => [
				[
					'label' => __( 'Activate', 'aioseo-pro' ),
					'url'   => admin_url( 'admin.php?page=aioseo-settings&aioseo-scroll=aioseo-license-key-row&aioseo-highlight=aioseo-license-key-row#general-settings' )
				],
				[
					'label' => __( 'Purchase', 'aioseo-pro' ),
					'url'   => aioseo()->helpers->utmUrl( AIOSEO_MARKETING_URL . 'pricing/', 'seo-checklist', 'activate-license' )
				]
			],
			'completed'   => false,
			'dismissable' => false
		];

		$this->checks[] = [
			'name'        => 'enableIndexNow',
			'title'       => __( 'Enable IndexNow', 'aioseo-pro' ),
			'description' => __( 'Instantly notify search engines when your content changes, helping you get indexed and ranked sooner.', 'aioseo-pro' ),
			'priority'    => 'medium',
			'time'        => [
				'label' => __( 'Instant', 'aioseo-pro' ),
				'value' => 0
			],
			'callback'    => 'checkIndexNowEnabled',
			'capability'  => 'install_plugins',
			'actions'     => [
				[
					'label'    => __( 'Enable', 'aioseo-pro' ),
					'callback' => 'enableIndexNow'
				]
			],
			'completed'   => false
		];

		// Show if the user's plan includes Local SEO, and multiple locations is disabled.
		if ( $this->planIncludesAddon( 'aioseo-local-business' ) && ! aioseo()->options->localBusiness->locations->general->multiple ) {
			$this->checks[] = [
				'name'        => 'fillLocalBusinessSchema',
				'title'       => __( 'Fill Out Local Business Info', 'aioseo-pro' ),
				'description' => __( 'Add your business details and location information to help local customers find you in Google Maps and local search results.', 'aioseo-pro' ),
				'priority'    => 'medium',
				'time'        => [
					'label' => __( '10 minutes', 'aioseo-pro' ),
					'value' => 600
				],
				'callback'    => null,
				'capability'  => 'aioseo_local_seo_settings',
				'actions'     => [
					[
						'label' => __( 'Configure', 'aioseo-pro' ),
						'url'   => admin_url( 'admin.php?page=aioseo-local-seo&aioseo-scroll=aioseo-card-localBusinessInfo&aioseo-highlight=aioseo-card-localBusinessInfo' )
					],
					[
						'label'    => __( 'Mark Complete', 'aioseo-pro' ),
						'callback' => 'completeCheck'
					]
				],
				'completed'   => false
			];
		}

		// Show if the user's plan includes Image SEO.
		if ( $this->planIncludesAddon( 'aioseo-image-seo' ) ) {
			$this->checks[] = [
				'name'        => 'reviewImageSeoSettings',
				'title'       => __( 'Configure Image SEO Settings', 'aioseo-pro' ),
				'description' => __( 'Configure how your images are optimized for search engines, including alt text, titles, and file naming.', 'aioseo-pro' ),
				'priority'    => 'low',
				'time'        => [
					'label' => __( '10 minutes', 'aioseo-pro' ),
					'value' => 600
				],
				'callback'    => null,
				'capability'  => 'aioseo_search_appearance_settings',
				'actions'     => [
					[
						'label' => __( 'Open Settings', 'aioseo-pro' ),
						'url'   => admin_url( 'admin.php?page=aioseo-search-appearance#/media' )
					],
					[
						'label'    => __( 'Mark Complete', 'aioseo-pro' ),
						'callback' => 'completeCheck'
					]
				],
				'completed'   => false
			];
		}

		// Only show if llms.txt is enabled.
		if ( aioseo()->options->sitemap->llms->enable ) {
			$this->checks[] = [
				'name'        => 'enableLlmsFullTxt',
				'title'       => __( 'Enable llms-full.txt', 'aioseo-pro' ),
				'description' => __( 'Generate a comprehensive llms-full.txt file with full content for AI models.', 'aioseo-pro' ),
				'priority'    => 'low',
				'time'        => [
					'label' => __( 'Instant', 'aioseo-pro' ),
					'value' => 0
				],
				'callback'    => 'checkLlmsFullTxtEnabled',
				'capability'  => 'aioseo_sitemap_settings',
				'actions'     => [
					[
						'label'    => __( 'Enable', 'aioseo-pro' ),
						'callback' => 'enableLlmsFullTxt'
					]
				],
				'completed'   => false
			];
		}

		// Show if the user's plan includes Link Assistant and there are orphaned posts.
		if ( $this->planIncludesAddon( 'aioseo-link-assistant' ) && $this->hasOrphanedPosts() ) {
			$this->checks[] = [
				'name'        => 'fixOrphanedPosts',
				'title'       => __( 'Add Internal Links to Orphaned Posts', 'aioseo-pro' ),
				'description' => __( 'Find and link to posts that have no internal links pointing to them, making them easier for search engines to discover.', 'aioseo-pro' ),
				'priority'    => 'medium',
				'time'        => [
					'label' => __( '15 minutes', 'aioseo-pro' ),
					'value' => 900
				],
				'callback'    => null,
				'capability'  => 'aioseo_link_assistant_settings',
				'actions'     => [
					[
						'label' => __( 'Fix Posts', 'aioseo-pro' ),
						'url'   => admin_url( 'admin.php?page=aioseo-link-assistant#/links-report?orphaned-posts=1' )
					],
					[
						'label'    => __( 'Mark Complete', 'aioseo-pro' ),
						'callback' => 'completeCheck'
					]
				],
				'completed'   => false
			];
		}

		// Only show 404 log checks for Pro, Elite, Agency, or Business plans.
		if ( $this->planIncludes404Logs() ) {
			$this->checks[] = [
				'name'        => 'enable404Logs',
				'title'       => __( 'Enable 404 Logs', 'aioseo-pro' ),
				'description' => __( 'Track 404 errors on your site to identify broken links and improve user experience.', 'aioseo-pro' ),
				'priority'    => 'medium',
				'time'        => [
					'label' => __( 'Instant', 'aioseo-pro' ),
					'value' => 0
				],
				'callback'    => 'check404LogsEnabled',
				'capability'  => 'aioseo_redirects_settings',
				'actions'     => [
					[
						'label'    => __( 'Enable', 'aioseo-pro' ),
						'callback' => 'enable404Logs'
					]
				],
				'completed'   => false
			];

			$this->checks[] = [
				'name'        => 'review404Logs',
				'title'       => __( 'Check 404 Logs for Missing Pages', 'aioseo-pro' ),
				'description' => __( 'Identify and fix broken links on your site by checking which pages are returning 404 errors.', 'aioseo-pro' ),
				'priority'    => 'medium',
				'time'        => [
					'label' => __( '15 minutes', 'aioseo-pro' ),
					'value' => 900
				],
				'callback'    => null,
				'capability'  => 'aioseo_redirects_settings',
				'actions'     => [
					[
						'label' => __( 'Review Logs', 'aioseo-pro' ),
						'url'   => admin_url( 'admin.php?page=aioseo-redirects&activetab=logs-404#/logs' )
					],
					[
						'label'    => __( 'Mark Complete', 'aioseo-pro' ),
						'callback' => 'completeCheck'
					]
				],
				'completed'   => false
			];
		}
	}

	/**
	 * Check if the license is activated.
	 *
	 * @since 4.9.4
	 *
	 * @return bool
	 */
	protected function licenseActivated() {
		if ( ! is_object( aioseo()->license ) || ! method_exists( aioseo()->license, 'isActive' ) ) {
			return false;
		}

		return aioseo()->license->isActive();
	}

	/**
	 * Check if IndexNow addon is enabled.
	 *
	 * @since 4.9.4
	 *
	 * @return bool
	 */
	protected function checkIndexNowEnabled() {
		$indexNowAddon = aioseo()->addons->getAddon( 'aioseo-index-now' );

		return ! empty( $indexNowAddon->isActive );
	}

	/**
	 * Check if llms-full.txt is enabled.
	 *
	 * @since 4.9.4
	 *
	 * @return bool
	 */
	protected function checkLlmsFullTxtEnabled() {
		return aioseo()->options->sitemap->llms->enableFull;
	}

	/**
	 * Check if there are orphaned posts.
	 *
	 * @since 4.9.4
	 *
	 * @return bool
	 */
	protected function hasOrphanedPosts() {
		if ( ! function_exists( 'aioseoLinkAssistant' ) ) {
			return false;
		}

		$overviewData = aioseoLinkAssistant()->helpers->getOverviewData();

		return ! empty( $overviewData['totals']['orphanedPosts'] );
	}

	/**
	 * Check if 404 logs are enabled.
	 *
	 * @since 4.9.4
	 *
	 * @return bool
	 */
	protected function check404LogsEnabled() {
		if ( empty( aioseo()->redirects ) ) {
			return false;
		}

		return aioseo()->redirects->options->logs->log404->enabled;
	}

	/**
	 * Execute a Pro-specific action.
	 *
	 * @since 4.9.4
	 *
	 * @param  string $check  The check name.
	 * @param  string $action The action name.
	 * @return bool
	 */
	public function doAction( $check, $action ) {
		switch ( $action ) {
			case 'enableIndexNow':
				return $this->enableIndexNow();
			case 'enableLlmsFullTxt':
				return $this->enableLlmsFullTxt();
			case 'enable404Logs':
				return $this->enable404Logs();
			default:
				return parent::doAction( $check, $action );
		}
	}

	/**
	 * Enable the IndexNow addon.
	 *
	 * @since 4.9.4
	 *
	 * @return bool
	 */
	private function enableIndexNow() {
		if ( ! current_user_can( 'install_plugins' ) && ! current_user_can( 'activate_plugins' ) ) {
			return false;
		}

		return $this->activateAddon( 'aioseo-index-now' );
	}

	/**
	 * Enable llms-full.txt.
	 *
	 * @since 4.9.4
	 *
	 * @return bool
	 */
	private function enableLlmsFullTxt() {
		if ( ! current_user_can( 'aioseo_sitemap_settings' ) ) {
			return false;
		}

		aioseo()->options->sitemap->llms->enableFull = true;

		// Schedule llms-full.txt generation.
		if ( aioseo()->llms && method_exists( aioseo()->llms, 'scheduleSingleGenerationForLlmsFullTxt' ) ) {
			aioseo()->llms->scheduleSingleGenerationForLlmsFullTxt();
		}

		return true;
	}

	/**
	 * Enable 404 logs.
	 *
	 * @since 4.9.4
	 *
	 * @return bool
	 */
	private function enable404Logs() {
		if ( ! current_user_can( 'aioseo_redirects_settings' ) ) {
			return false;
		}

		if ( empty( aioseo()->redirects ) ) {
			return false;
		}

		aioseo()->redirects->options->logs->log404->enabled = true;

		return true;
	}

	/**
	 * Check if the user's plan includes a specific addon.
	 *
	 * @since 4.9.4
	 *
	 * @param  string $addonSku The addon SKU.
	 * @return bool             True if the plan includes the addon.
	 */
	private function planIncludesAddon( $addonSku ) {
		if ( ! aioseo()->license || ! aioseo()->license->isActive() ) {
			return false;
		}

		$licenseLevel = aioseo()->internalOptions->internal->license->level;
		if ( empty( $licenseLevel ) ) {
			return false;
		}

		$addonLevels = aioseo()->addons->getAddonLevels( $addonSku );

		return in_array( strtolower( $licenseLevel ), $addonLevels, true );
	}

	/**
	 * Check if the user's plan includes 404 logs.
	 *
	 * 404 logs are available for Pro, Elite, Agency, and Business plans.
	 *
	 * @since 4.9.4
	 *
	 * @return bool True if the plan includes 404 logs.
	 */
	private function planIncludes404Logs() {
		if ( ! aioseo()->license || ! aioseo()->license->isActive() ) {
			return false;
		}

		$licenseLevel = aioseo()->internalOptions->internal->license->level;
		if ( empty( $licenseLevel ) ) {
			return false;
		}

		$allowedLevels = [ 'pro', 'elite', 'agency', 'business' ];

		return in_array( strtolower( $licenseLevel ), $allowedLevels, true );
	}

	/**
	 * Activate an addon (install if needed).
	 *
	 * @since 4.9.4
	 *
	 * @param  string $addonSku The addon SKU.
	 * @return bool             True on success.
	 */
	private function activateAddon( $addonSku ) {
		$addon = aioseo()->addons->getAddon( $addonSku );

		// Already active.
		if ( ! empty( $addon->isActive ) ) {
			return true;
		}

		// Installed but not active - just activate it.
		if ( ! empty( $addon->installed ) && ! empty( $addon->basename ) ) {
			if ( ! aioseo()->addons->canActivate() ) {
				return false;
			}

			if ( ! current_user_can( 'activate_plugins' ) ) {
				return false;
			}

			$activated = activate_plugin( $addon->basename );

			return ! is_wp_error( $activated );
		}

		if ( ! current_user_can( 'install_plugins' ) ) {
			return false;
		}

		// Not installed - install and activate.
		if ( ! aioseo()->addons->canInstall() ) {
			return false;
		}

		return aioseo()->addons->installAddon( $addonSku );
	}
}