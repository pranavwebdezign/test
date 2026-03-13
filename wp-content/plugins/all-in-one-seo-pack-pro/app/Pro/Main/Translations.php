<?php
namespace AIOSEO\Plugin\Pro\Main;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use DateTime;

/**
 * Translations class.
 *
 * @since 4.0.0
 */
class Translations {
	/**
	 * List of available translations.
	 *
	 * @since 4.0.0
	 *
	 * @var array
	 */
	private static $installedTranslations = [];

	/**
	 * List of available languages.
	 *
	 * @since 4.0.0
	 *
	 * @var array[string]
	 */
	private static $availableLanguages = [];

	/**
	 * Static cache for translations to prevent repeated database queries within the same request.
	 *
	 * @since 4.9.1.1
	 *
	 * @var array
	 */
	private static $translationsCache = [];

	/**
	 * The project type.
	 *
	 * @since 4.2.7
	 *
	 * @var string
	 */
	private $type = '';

	/**
	 * The project slug.
	 *
	 * @since 4.2.7
	 *
	 * @var string
	 */
	private $slug = '';

	/**
	 * The API URL.
	 *
	 * @since 4.2.7
	 *
	 * @var string
	 */
	private $apiUrl = '';

	/**
	 * Class constructor.
	 *
	 * @param string $type   The project type ("plugin" or "theme").
	 * @param string $slug   The project directory slug.
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
	 * @since 4.0.0
	 *
	 * @return void
	 */
	public function init() {
		if ( ! has_action( 'init', [ $this, 'registerCleanTranslationsCache' ] ) ) {
			add_action( 'init', [ $this, 'registerCleanTranslationsCache' ], 9999 );
		}

		// Clear translation cache when site or user locale changes.
		add_action( 'update_option_WPLANG', [ $this, 'clearCacheOnSiteLocaleUpdate' ], 10, 2 );
		add_action( 'update_user_meta', [ $this, 'clearCacheOnUserLocaleUpdate' ], 10, 4 );

		// Short-circuits translations API requests for private projects.
		add_filter(
			'translations_api',
			function ( $result, $requestedType = '', $args = [] ) {
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
	 * @since 4.0.0
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
	 * @since 4.0.0
	 *
	 * @param  string $type Project type. Either plugin or theme.
	 * @return void
	 */
	public function cleanTranslationsCache( $type ) {
		$transientKey = 'translations_' . $this->slug . '_' . $type;
		$translations = aioseo()->core->networkCache->get( $transientKey );

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

		aioseo()->core->networkCache->delete( $transientKey );
		// Clear static cache as well.
		unset( self::$translationsCache[ $transientKey ] );
	}

	/**
	 * Clears translation cache when site locale option changes.
	 *
	 * @since 4.9.1
	 *
	 * @param  mixed  $oldValue The old option value.
	 * @param  mixed  $value    The new option value.
	 * @return void
	 */
	public function clearCacheOnSiteLocaleUpdate( $oldValue, $value ) {
		if ( $oldValue !== $value ) {
			$this->clearTranslationsCache();
		}
	}

	/**
	 * Clears translation cache when user locale meta changes.
	 *
	 * @since 4.9.1
	 *
	 * @param  int    $metaId     ID of updated metadata entry.
	 * @param  int    $objectId   ID of the object metadata is for.
	 * @param  string $metaKey    Metadata key.
	 * @param  mixed  $prevValue  The previous value.
	 * @return void
	 */
	public function clearCacheOnUserLocaleUpdate( $metaId, $objectId, $metaKey, $prevValue ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		if ( 'locale' === $metaKey ) {
			$this->clearTranslationsCache();
		}
	}

	/**
	 * Clears all translation caches for this plugin/theme.
	 *
	 * @since 4.9.1
	 *
	 * @return void
	 */
	private function clearTranslationsCache() {
		$transientKey = 'translations_' . $this->slug . '_' . $this->type;
		aioseo()->core->networkCache->delete( $transientKey );
		// Clear static cache as well.
		unset( self::$translationsCache[ $transientKey ] );
	}

	/**
	 * Checks if translations are needed based on the current locale.
	 *
	 * @since 4.9.1
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
	 * @since 4.9.1
	 *
	 * @return bool True if any user has a non-English locale, false otherwise.
	 */
	private function hasNonEnglishUserLocale() {
		static $hasNonEnglish = null;
		if ( null !== $hasNonEnglish ) {
			return $hasNonEnglish;
		}

		if ( ! empty( aioseo()->core->cache ) ) {
			$hasNonEnglish = aioseo()->core->cache->get( 'has_non_english_user_locale' );
			if ( null !== $hasNonEnglish ) {
				return $hasNonEnglish;
			}
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

		if ( ! empty( aioseo()->core->cache ) ) {
			aioseo()->core->cache->update( 'has_non_english_user_locale', $hasNonEnglish, HOUR_IN_SECONDS );
		}

		return $hasNonEnglish;
	}

	/**
	 * Gets the translations for a given project.
	 *
	 * @since 4.0.0
	 *
	 * @param  string $type Project type. Either plugin or theme.
	 * @param  string $slug Project directory slug.
	 * @param  string $url  Full GlotPress API URL for the project.
	 * @return array        Translation data.
	 */
	public function getTranslations( $type, $slug, $url ) {
		$transientKey = 'translations_' . $slug . '_' . $type;

		// Check static cache first to prevent repeated database queries within the same request.
		if ( isset( self::$translationsCache[ $transientKey ] ) ) {
			return self::$translationsCache[ $transientKey ];
		}

		$translations = aioseo()->core->networkCache->get( $transientKey );

		if ( null !== $translations && is_array( $translations ) ) {
			self::$translationsCache[ $transientKey ] = $translations;

			return $translations;
		}

		if ( ! is_array( $translations ) ) {
			$translations = [];
		}

		if ( isset( $translations[ $slug ] ) && is_array( $translations[ $slug ] ) ) {
			self::$translationsCache[ $transientKey ] = $translations[ $slug ];

			return $translations[ $slug ];
		}

		// Only fetch translations if a non-English locale is being used.
		if ( ! $this->needsTranslations() ) {
			self::$translationsCache[ $transientKey ] = [];

			// Return here so that the cache is not updated.
			return [];
		} else {
			$result = json_decode( wp_remote_retrieve_body( wp_remote_get( $url, [ 'timeout' => 2 ] ) ), true );
			if ( ! is_array( $result ) ) {
				$result = [];
			}
		}

		$translations[ $slug ]         = $result;
		$translations['_last_checked'] = time();

		aioseo()->core->networkCache->update( $transientKey, $translations, 0 );

		self::$translationsCache[ $transientKey ] = $result;

		return $result;
	}
}