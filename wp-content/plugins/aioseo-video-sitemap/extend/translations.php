<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable Generic.Arrays.DisallowLongArraySyntax.Found
// If the class exists already don't redeclare.
if ( ! class_exists( 'AIOSEOTranslations' ) ) {
	/**
	 * This class pulls in translations for the current addon.
	 *
	 * @since 1.0.0
	 */
	class AIOSEOTranslations {
		/**
		 * The project type.
		 *
		 * @since 1.0.0
		 *
		 * @var string
		 */
		private $type = '';

		/**
		 * The project dir slug.
		 *
		 * @since 1.0.0
		 *
		 * @var string
		 */
		private $slug = '';

		/**
		 * The GlotPress API URL.
		 *
		 * @since 1.0.0
		 *
		 * @var string
		 */
		private $apiUrl = '';

		/**
		 * Installed translations.
		 *
		 * @since 1.0.0
		 *
		 * @var array
		 */
		private static $installedTranslations = array();

		/**
		 * Available languages.
		 *
		 * @since 1.0.0
		 *
		 * @var array
		 */
		private static $availableLanguages = array();

		/**
		 * Class constructor.
		 *
		 * @param string $type   Project type. Either plugin or theme.
		 * @param string $slug   Project directory slug.
		 * @param string $apiUrl Full GlotPress API URL for the project.
		 */
		public function __construct( $type, $slug, $apiUrl ) {
			$this->type   = $type;
			$this->slug   = $slug;
			$this->apiUrl = $apiUrl;
		}

		/**
		 * Adds a new project to load translations for.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public function init() {
			if ( ! has_action( 'init', [ $this, 'registerCleanTranslationsCache' ] ) ) {
				add_action( 'init', [ $this, 'registerCleanTranslationsCache' ], 9999 );
			}

			// Clear translation cache when site or user locale changes.
			add_action( 'update_option_WPLANG', [ $this, 'clearCacheOnSiteLocaleUpdate' ], 10, 2 );
			add_action( 'update_user_meta', [ $this, 'clearCacheOnUserLocaleUpdate' ], 10, 3 );

			// Short-circuits translations API requests for private projects.
			add_filter(
				'translations_api',
				function ( $result, $requestedType, $args ) {
					if ( $this->type . 's' === $requestedType && $this->slug === $args['slug'] ) {
						return $this->getTranslations( $this->type, $args['slug'], $this->apiUrl );
					}

					return $result;
				},
				10,
				3
			);

			// Filters the translations transients to include the private plugin or theme. @see wp_get_translation_updates().
			add_filter(
				'site_transient_update_' . $this->type . 's',
				function ( $value ) {
					if ( ! $value ) {
						$value = new \stdClass();
					}

					if ( ! is_object( $value ) ) {
						// If the value isn't an object at this point, bail in order to prevent errors.
						return $value;
					}

					if ( ! isset( $value->translations ) || ! is_array( $value->translations ) ) {
						$value->translations = [];
					}

					$translations = $this->getTranslations( $this->type, $this->slug, $this->apiUrl );

					if ( ! isset( $translations[ $this->slug ]['translations'] ) ) {
						return $value;
					}

					if ( empty( self::$installedTranslations ) ) {
						self::$installedTranslations = wp_get_installed_translations( $this->type . 's' );
					}

					if ( empty( self::$availableLanguages ) ) {
						self::$availableLanguages = get_available_languages();
					}

					foreach ( (array) $translations[ $this->slug ]['translations'] as $translation ) {
						if ( in_array( $translation['language'], self::$availableLanguages, true ) ) {
							if ( isset( self::$installedTranslations[ $this->slug ][ $translation['language'] ] ) && $translation['updated'] ) {
								$local  = new DateTime( self::$installedTranslations[ $this->slug ][ $translation['language'] ]['PO-Revision-Date'] );
								$remote = new DateTime( $translation['updated'] );

								if ( $local >= $remote ) {
									continue;
								}
							}

							$translation['type'] = $this->type;
							$translation['slug'] = $this->slug;

							$value->translations[] = $translation;
						}
					}

					return $value;
				}
			);
		}

		/**
		 * Registers actions for clearing translation caches.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public function registerCleanTranslationsCache() {
			$clearPluginTranslations = function() {
				$this->cleanTranslationsCache( 'plugin' );
			};

			$clearThemeTranslations = function() {
				$this->cleanTranslationsCache( 'theme' );
			};

			add_action( 'set_site_transient_update_plugins', $clearPluginTranslations );
			add_action( 'delete_site_transient_update_plugins', $clearPluginTranslations );

			add_action( 'set_site_transient_update_themes', $clearThemeTranslations );
			add_action( 'delete_site_transient_update_themes', $clearThemeTranslations );
		}

		/**
		 * Clears existing translation cache for a given type.
		 *
		 * @since 1.0.0
		 *
		 * @param  string $type Project type. Either plugin or theme.
		 * @return void
		 */
		public function cleanTranslationsCache( $type ) {
			$transientKey = '_aioseo_translations_' . $this->slug . '_' . $type;

			if ( is_multisite() ) {
				switch_to_blog( get_network()->site_id );
			}

			$translations = get_site_transient( $transientKey );

			if ( is_multisite() ) {
				restore_current_blog();
			}

			if ( ! is_array( $translations ) ) {
				return;
			}

			// Don't delete the cache if the transient gets changed multiple times
			// during a single request. Set cache lifetime to maximum 15 seconds.
			$cacheLifespan  = 15;
			$timeNotChanged = isset( $translations['_last_checked'] ) && ( time() - $translations['_last_checked'] ) > $cacheLifespan;

			if ( ! $timeNotChanged ) {
				return;
			}

			if ( is_multisite() ) {
				switch_to_blog( get_network()->site_id );
			}

			delete_site_transient( $transientKey );

			if ( is_multisite() ) {
				restore_current_blog();
			}
		}

		/**
		 * Clears translation cache when site locale option changes.
		 *
		 * @since 1.0.0
		 *
		 * @param  mixed  $oldValue The old option value.
		 * @param  mixed  $value    The new option value.
		 * @return void
		 */
		public function clearCacheOnSiteLocaleUpdate( $oldValue, $value ) {
			if ( $oldValue !== $value ) {
				$this->clearTranslationCache();
			}
		}

		/**
		 * Clears translation cache when user locale meta changes.
		 *
		 * @since 1.0.0
		 *
		 * @param  int    $metaId   ID of updated metadata entry.
		 * @param  int    $objectId ID of the object metadata is for.
		 * @param  string $metaKey  Metadata key.
		 * @return void
		 */
		public function clearCacheOnUserLocaleUpdate( $metaId, $objectId, $metaKey ) {
			if ( 'locale' === $metaKey ) {
				$this->clearTranslationCache();
			}
		}

		/**
		 * Clears translation cache for this plugin.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		private function clearTranslationCache() {
			$transientKey = '_aioseo_translations_' . $this->slug . '_' . $this->type;

			if ( is_multisite() ) {
				switch_to_blog( get_network()->site_id );
			}

			delete_site_transient( $transientKey );

			if ( is_multisite() ) {
				restore_current_blog();
			}
		}

		/**
		 * Checks if translations are needed based on the current locale.
		 *
		 * @since 1.0.0
		 *
		 * @return bool True if translations are needed, false otherwise.
		 */
		private function needsTranslations() {
			// Return true for multisite. Checking user would be slower than fetching translations.
			if ( is_multisite() ) {
				return true;
			}

			$siteLocale = get_locale();

			// Check if site locale is non-English.
			$siteIsEnglish = ( 'en_US' === $siteLocale || 'en' === $siteLocale || 0 === strpos( $siteLocale, 'en_' ) );

			// If site locale is non-English, we need translations.
			if ( ! $siteIsEnglish ) {
				return true;
			}

			// Check if any user has a non-English locale.
			return $this->hasNonEnglishUserLocale();
		}

		/**
		 * Checks if any user on the site has a non-English locale.
		 *
		 * @since 1.0.0
		 *
		 * @return bool True if any user has a non-English locale, false otherwise.
		 */
		private function hasNonEnglishUserLocale() {
			static $hasNonEnglish = null;
			if ( null !== $hasNonEnglish ) {
				return $hasNonEnglish;
			}

			// See if a single user has a non-English locale.
			global $wpdb;
			$hasNonEnglish = (bool) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->usermeta} WHERE meta_key = %s AND meta_value != '' AND meta_value NOT LIKE %s LIMIT 1",
					'locale',
					'en%'
				)
			);

			return $hasNonEnglish;
		}

		/**
		 * Gets the translations for a given project.
		 *
		 * @since 1.0.0
		 *
		 * @param  string $type Project type. Either plugin or theme.
		 * @param  string $slug Project directory slug.
		 * @param  string $url  Full GlotPress API URL for the project.
		 * @return array        Translation data.
		 */
		public function getTranslations( $type, $slug, $url ) {
			$transientKey = '_aioseo_translations_' . $slug . '_' . $type;

			if ( is_multisite() ) {
				switch_to_blog( get_network()->site_id );
			}

			$translations = get_site_transient( $transientKey );

			if ( is_multisite() ) {
				restore_current_blog();
			}

			if ( false !== $translations && is_array( $translations ) ) {
				return $translations;
			}

			if ( ! is_array( $translations ) ) {
				$translations = [];
			}

			if ( isset( $translations[ $slug ] ) && is_array( $translations[ $slug ] ) ) {
				return $translations[ $slug ];
			}

			// Skip fetching translations if a non-English locale is being used.
			if ( ! $this->needsTranslations() ) {
				// Return here so cache doesn't get updated.
				return [];
			} else {
				$result = json_decode( wp_remote_retrieve_body( wp_remote_get( $url, [ 'timeout' => 2 ] ) ), true );
				if ( ! is_array( $result ) ) {
					$result = [];
				}
			}

			$translations[ $slug ]         = $result;
			$translations['_last_checked'] = time();

			if ( is_multisite() ) {
				switch_to_blog( get_network()->site_id );
			}

			set_site_transient( $transientKey, $translations );

			if ( is_multisite() ) {
				restore_current_blog();
			}

			return $result;
		}
	}
}