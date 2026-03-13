<?php
namespace AIOSEO\Plugin\Addon\Eeat {
	// Exit if accessed directly.
	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	/**
	 * Main class.
	 *
	 * @since 1.0.0
	 */
	final class Eeat {
		/**
		 * Holds the instance of the plugin.
		 *
		 * @since 1.0.0
		 *
		 * @var \AIOSEO\Plugin\Addon\Eeat\Eeat
		 */
		private static $instance;

		/**
		 * Plugin version for enqueueing, etc.
		 * The value is retrieved from the version constant.
		 *
		 * @since 1.0.0
		 *
		 * @var string
		 */
		public $version = '';

		/**
		 * Whether we're in a dev environment.
		 *
		 * @since 1.0.0
		 *
		 * @var bool
		 */
		public $isDev = false;

		/**
		 * Core class instance.
		 *
		 * @since 1.0.0
		 *
		 * @var Core\Core
		 */
		public $core;

		/**
		 * Helpers class instance.
		 *
		 * @since 1.0.0
		 *
		 * @var Utils\Helpers
		 */
		public $helpers;

		/**
		 * UI class instance.
		 *
		 * @since 1.0.0
		 *
		 * @var Ui\Ui
		 */
		public $ui;

		/**
		 * Standalones class instance.
		 *
		 * @since 1.0.0
		 *
		 * @var Standalones\Standalones
		 */
		public $standalones;

		/**
		 * PersonAuthor class instance.
		 *
		 * @since 1.0.0
		 *
		 * @var Schema\Graphs\PersonAuthor
		 */
		public $personAuthor;

		/**
		 * WebPage class instance.
		 *
		 * @since 1.1.0
		 *
		 * @var Schema\Graphs\WebPage
		 */
		public $webPage;

		/**
		 * InternalOptions class instance.
		 *
		 * @since 1.0.0
		 *
		 * @var Options\InternalOptions
		 */
		public $internalOptions;

		/**
		 * Options class instance.
		 *
		 * @since 1.0.0
		 *
		 * @var Options\Options
		 */
		public $options;

		/**
		 * Updates class instance.
		 *
		 * @since 1.0.0
		 *
		 * @var Main\Updates
		 */
		public $updates;

		/**
		 * Post model class instance.
		 *
		 * @since 1.0.0
		 *
		 * @var Models\Post
		 */
		public $postModel;

		/**
		 * Utils class instance.
		 *
		 * @since 1.0.0
		 *
		 * @var Utils\Utils
		 */
		public $utils;

		/**
		 * VueSettings class instance.
		 *
		 * @since 1.0.0
		 *
		 * @var Utils\VueSettings
		 */
		public $vueSettings;

		/**
		 * API class instance.
		 *
		 * @since 1.0.0
		 *
		 * @var Api\Api
		 */
		public $api;

		/**
		 * Uninstall class instance.
		 *
		 * @since 1.2.2
		 *
		 * @var Main\Uninstall
		 */
		public $uninstall = null;

		/**
		 * Main Eeat Instance.
		 *
		 * Insures that only one instance of the addon exists in memory at any one
		 * time. Also prevents needing to define globals all over the place.
		 *
		 * @since 1.0.0
		 *
		 * @return \AIOSEO\Plugin\Addon\Eeat\Eeat
		 */
		public static function instance() {
			if ( null === self::$instance || ! self::$instance instanceof self ) {
				self::$instance = new self();
				self::$instance->constants();
				self::$instance->includes();
				self::$instance->preload();
				self::$instance->load();
			}

			return self::$instance;
		}

		/**
		 * Setup plugin constants.
		 * All the path/URL related constants are defined in main plugin file.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		private function constants() {
			$defaultHeaders = [
				'name'    => 'Plugin Name',
				'version' => 'Version',
			];

			$pluginData = get_file_data( AIOSEO_EEAT_FILE, $defaultHeaders );

			$constants = [
				'AIOSEO_EEAT_VERSION' => $pluginData['version']
			];

			foreach ( $constants as $constant => $value ) {
				if ( ! defined( $constant ) ) {
					define( $constant, $value );
				}
			}

			$this->version = AIOSEO_EEAT_VERSION;
		}

		/**
		 * Including the new files with PHP 5.3 style.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		private function includes() {
			$dependencies = [
				'/vendor/autoload.php'
			];

			foreach ( $dependencies as $path ) {
				if ( ! file_exists( AIOSEO_EEAT_DIR . $path ) ) {
					// Something is not right.
					status_header( 500 );
					wp_die( esc_html__( 'Plugin is missing required dependencies. Please contact support for more information.', 'aioseo-eeat' ) );
				}
				require AIOSEO_EEAT_DIR . $path;
			}

			$this->loadVersion();
		}

		/**
		 * Load the version of the plugin we are currently using.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		private function loadVersion() {
			if (
				! class_exists( '\Dotenv\Dotenv' ) ||
				! file_exists( AIOSEO_EEAT_DIR . '/build/.env' )
			) {
				return;
			}

			$dotenv = \Dotenv\Dotenv::createUnsafeImmutable( AIOSEO_EEAT_DIR, '/build/.env' );
			$dotenv->load();

			$version = defined( 'AIOSEO_DEV_VERSION' )
				? strtolower( AIOSEO_DEV_VERSION )
				: strtolower( getenv( 'VITE_VERSION' ) );
			if ( ! empty( $version ) ) {
				$this->isDev = true;
			}
		}

		/**
		 * Preload our classes.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public function preload() {
			$this->core      = new Core\Core();
			$this->helpers   = new Utils\Helpers();
			$this->uninstall = new Main\Uninstall();
		}

		/**
		 * Load our classes.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public function load() {
			aioseo()->helpers->loadTextDomain( 'aioseo-eeat' );

			$this->internalOptions  = new Options\InternalOptions();
			$this->options          = new Options\Options();
			$this->updates          = new Main\Updates();
			$this->utils            = new Utils\Utils();
			$this->vueSettings      = new Utils\VueSettings();
			$this->postModel        = new Models\Post();
			$this->personAuthor     = new Schema\Graphs\PersonAuthor();
			$this->webPage          = new Schema\Graphs\WebPage();
			$this->standalones      = new Standalones\Standalones();
			$this->ui               = new Ui\Ui();
			$this->api              = new Api\Api();

			aioseo()->addons->loadAddon( 'eeat', $this );
		}
	}
}

namespace {
	/**
	 * The function which returns the one Eeat instance.
	 *
	 * @since 1.0.0
	 *
	 * @return \AIOSEO\Plugin\Addon\Eeat\Eeat
	 */
	function aioseoEeat() {
		return \AIOSEO\Plugin\Addon\Eeat\Eeat::instance();
	}
}