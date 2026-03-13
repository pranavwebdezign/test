<?php
namespace AIOSEO\Plugin\Pro\Redirects\Main;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AIOSEO\Plugin\Pro\Redirects\Utils;

/**
 * Main class to run our full site redirects.
 *
 * @since 4.9.1
 */
class FullSiteRedirects {
	/**
	 * The license levels that can use full site redirects.
	 *
	 * @since 4.9.1
	 *
	 * @var array
	 */
	private $licenseLevels = [ 'pro', 'elite' ];

	/**
	 * Class constructor.
	 *
	 * @since 4.9.1
	 */
	public function __construct() {
		if ( ! aioseo()->license->hasCoreFeature( 'redirects', 'full-site-redirect' ) ) {
			return;
		}

		add_action( 'init', [ $this, 'init' ], 1 );
	}

	/**
	 * Initialize the class after AIOSEO is fully loaded.
	 *
	 * @since 4.9.1
	 *
	 * @return void
	 */
	public function init() {
		// If we are using server level redirects, return early.
		if ( aioseo()->redirects->server->valid() ) {
			return;
		}

		// Aliases always run.
		add_action( 'init', [ $this, 'aliases' ], 2 );

		// We don't need to run canonical and relocate in WP CLI.
		if ( aioseo()->helpers->isDoingWpCli() ) {
			return;
		}

		add_action( 'init', [ $this, 'relocate' ], 3 );
		add_action( 'init', [ $this, 'canonical' ], 4 );
	}

	/**
	 * Runs if relocation is enabled.
	 *
	 * @since 4.9.1
	 *
	 * @return void
	 */
	public function relocate() {
		$newDomain = $this->shouldRelocate();
		if ( ! $newDomain || ! $this->shouldRun() ) {
			return;
		}

		wp_redirect( aioseo()->redirects->helpers->makeUrl( [ $newDomain, Utils\Request::getRequestUrl() ] ), 301, 'AIOSEO' );
		exit;
	}

	/**
	 * Returns the relocation address if the website should be relocated.
	 *
	 * @since 4.9.1
	 *
	 * @return false|string The relocation address.
	 */
	public function shouldRelocate() {
		$newDomain = $this->getRelocateAddress();
		if ( ! aioseo()->redirects->options->fullSite->relocate->enabled || empty( $newDomain ) ) {
			return false;
		}

		return $newDomain;
	}

	/**
	 * Returns the relocation address validating if it's an actual URL.
	 *
	 * @since 4.9.1
	 *
	 * @return void|string The relocation address.
	 */
	public function getRelocateAddress() {
		$newDomain = aioseo()->redirects->options->fullSite->relocate->newDomain;

		if ( empty( $newDomain ) || ! aioseo()->helpers->isUrl( $newDomain ) ) {
			return;
		}

		return $newDomain;
	}

	/**
	 * Runs an alias redirect if it matches the server's current host.
	 *
	 * @since 4.9.1
	 *
	 * @return void
	 */
	public function aliases() {
		$aliases = $this->getAliases();

		if ( empty( $aliases ) ) {
			return;
		}

		$aliases = array_map( 'untrailingslashit', $aliases );

		// Prevent redirect loop if the alias domain is the same as the current site domain.
		$realHost = wp_parse_url( get_home_url(), PHP_URL_HOST );
		if ( $this->getCurrentHost() === $realHost ) {
			return;
		}

		if ( in_array( $this->getCurrentHost(), $aliases, true ) ) {
			wp_redirect( aioseo()->redirects->helpers->makeUrl( [ get_home_url(), Utils\Request::getRequestUrl() ] ), 301, 'AIOSEO' );
			exit;
		}
	}

	/**
	 * Returns if the canonical redirect can happen.
	 *
	 * @since 4.9.1
	 *
	 * @return boolean|string The canonical url if the redirect can happen.
	 */
	public function shouldCanonical() {
		if ( ! aioseo()->redirects->options->fullSite->canonical->enabled ) {
			return false;
		}

		// These return the scheme + host, ignoring the path.
		$canonicalUrl = untrailingslashit( $this->getCanonicalHostUrl() );
		$siteUrl      = untrailingslashit( aioseo()->helpers->getSiteUrl() );

		// If the canonical url is NOT configured in WP it'll create a redirect loop and we need to prevent that.
		if ( $canonicalUrl !== $siteUrl ) {
			return false;
		}

		return $canonicalUrl;
	}

	/**
	 * Apply canonical settings if they are enabled.
	 *
	 * @since 4.9.1
	 *
	 * @return void
	 */
	public function canonical() {
		$canonicalHost = $this->shouldCanonical();

		// If the canonical URL is different from the current URL then we redirect.
		if ( $canonicalHost && $canonicalHost !== $this->getCurrentHostUrl() ) {
			wp_redirect( aioseo()->redirects->helpers->makeUrl( [ get_home_url(), Utils\Request::getRequestUrl() ] ), 301, 'AIOSEO' );
			exit;
		}
	}

	/**
	 * Returns our aliases.
	 *
	 * @since 4.9.1
	 *
	 * @return array An array of aliases.
	 */
	public function getAliases() {
		$aliases = aioseo()->redirects->options->fullSite->aliases;
		foreach ( $aliases as &$alias ) {
			$alias = json_decode( $alias );
			if ( ! empty( $alias->aliasedDomain ) ) {
				// Replace or add double forward slashes so we can safely extract the host.
				$alias->aliasedDomain = preg_replace( '/^http(|s):\/\/|^(?!\/\/)/i', '//', (string) trim( $alias->aliasedDomain ) );
			}
			$alias = wp_parse_url( $alias->aliasedDomain, PHP_URL_HOST );
		}

		return array_filter( $aliases );
	}

	/**
	 * Gets our canonical configured host url.
	 *
	 * @since 4.9.1
	 *
	 * @return string The canonical host url.
	 */
	public function getCanonicalHostUrl() {
		$currentUrl = wp_parse_url( get_home_url() );

		// Start canonical url with the current url scheme.
		$canonicalUrl = $currentUrl['scheme'] . '://';

		if ( aioseo()->redirects->options->fullSite->canonical->httpToHttps ) {
			$canonicalUrl = 'https://';
		}

		$canonicalHost = $currentUrl['host'];

		$preferredDomain = aioseo()->redirects->options->fullSite->canonical->preferredDomain;
		if ( 'add-www' === $preferredDomain && ! preg_match( '/^www\./', (string) $canonicalHost ) ) {
			$canonicalHost = 'www.' . $canonicalHost;
		} elseif ( 'remove-www' === $preferredDomain && preg_match( '/^www\./', (string) $canonicalHost ) ) {
			$canonicalHost = preg_replace( '/^www\./', '', (string) $canonicalHost );
		}

		$canonicalUrl .= $canonicalHost;

		return untrailingslashit( $canonicalUrl );
	}

	/**
	 * Returns the current url being accessed.
	 *
	 * @since 4.9.1
	 *
	 * @return string The current URL.
	 */
	private function getCurrentHostUrl() {
		return untrailingslashit( $this->getCurrentScheme() . '://' . $this->getCurrentHost() );
	}

	/**
	 * Returns the current scheme.
	 *
	 * @since 4.9.1
	 *
	 * @return string The scheme.
	 */
	private function getCurrentScheme() {
		return is_ssl() ? 'https' : 'http';
	}

	/**
	 * Returns the current host.
	 *
	 * @since 4.9.1
	 *
	 * @return string The host address.
	 */
	private function getCurrentHost() {
		return untrailingslashit( Utils\Request::getRequestServerName() );
	}

	/**
	 * Returns if we should run a redirection.
	 *
	 * @since 4.9.1
	 *
	 * @return bool Should run.
	 */
	private function shouldRun() {
		if (
			is_admin() ||
			aioseo()->helpers->isDoingWpCli() ||
			aioseo()->helpers->isAjaxCronRestRequest() ||
			Utils\Request::isProtectedPath() ||
			aioseo()->redirects->redirect->isPageBuilderPreviewRequest()
		) {
			return false;
		}

		return true;
	}
}