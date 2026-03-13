<?php
namespace AIOSEO\Plugin\Pro\Redirects\Main;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AIOSEO\Plugin\Pro\Redirects\Utils;
use AIOSEO\Plugin\Pro\Redirects\Models;
use AIOSEO\Plugin\Pro\Redirects\Utils\WpUri;

/**
 * Main class to run our 404 redirects.
 *
 * @since 4.9.1
 */
class Redirect404 {
	/**
	 * Class constructor.
	 *
	 * @since 4.9.1
	 */
	public function __construct() {
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
		if ( ! aioseo()->redirects->options->advanced404s->enabled ) {
			return;
		}

		if ( apply_filters( 'aioseo_redirects_use_alternate_404_hook', false, Utils\Request::getRequestUrl() ) ) {
			add_filter( 'template_include', [ $this, 'templateInclude' ], 10 );
		} else {
			// Maybe redirect 404s.
			add_action( 'template_redirect', [ $this, 'templateRedirect' ], 2000 );

			// Maybe short-circuit WP's redirect guess.
			add_filter( 'pre_redirect_guess_404_permalink', [ $this, 'redirectGuess' ] );
		}
	}

	/**
	 * Short-circuit WP's redirect guess.
	 *
	 * @since 4.9.1
	 *
	 * @param  string|null $guessUrl The pre guess url.
	 * @return string                The guess url.
	 */
	public function redirectGuess( $guessUrl ) {
		if ( ! Utils\Request::isRedirectTest() ) {
			$this->maybeRedirect();
		}

		return $guessUrl;
	}

	/**
	 * Intercept the request and redirect after logs have been saved.
	 *
	 * @since 4.9.1
	 *
	 * @return void
	 */
	public function templateRedirect() {
		// Skip redirecting if it's a redirect test.
		if ( ! is_404() || Utils\Request::isRedirectTest() || 200 === http_response_code() ) {
			return;
		}

		$this->maybeRedirect();
	}

	/**
	 * Tries to redirect a 404.
	 *
	 * @since 4.9.1
	 *
	 * @return void
	 */
	public function maybeRedirect() {
		// Check if we should really redirect 404s.
		if ( $this->avoid404Redirect() ) {
			return;
		}

		// Smart slug redirect.
		if ( aioseo()->redirects->options->advanced404s->redirectToSmart ) {
			$this->smartRedirect();
		}

		// We'll try redirecting to the parent first.
		if ( aioseo()->redirects->options->advanced404s->redirectToParent ) {
			$this->parentRedirect();
		}

		if ( aioseo()->redirects->options->advanced404s->redirectDefaultEnabled ) {
			switch ( aioseo()->redirects->options->advanced404s->redirectDefault ) {
				// Try redirecting to a Custom URL
				case 'custom':
					$this->customRedirect();
					break;
				// Last fallback is to the home page.
				case 'home':
					aioseo()->redirects->helpers->do404Redirect( get_home_url(), 'HOME' );
					break;
			}
		}
	}

	/**
	 * Smart redirect.
	 *
	 * @since 4.9.1
	 *
	 * @return void
	 */
	private function smartRedirect() {
		// Separate the path.
		$path = array_filter( explode( '/', Utils\Request::getRequestUrl() ) );

		// Get the last item which should be always the post name/slug.
		$slug = array_pop( $path );

		if ( empty( $slug ) ) {
			return;
		}

		// Look in the database if there's any post_type with the exact same name/slug.
		$post = aioseo()->core->db->start( 'posts' )
			->select( 'ID, post_type' )
			->where( [
				'post_name'   => $slug,
				'post_status' => 'publish'
			] )
			->run()
			->result();

		if (
			empty( $post ) ||
			empty( $post[0]->ID ) ||
			! aioseo()->helpers->isPostTypePublic( $post[0]->post_type )
		) {
			return;
		}

		$permalink = get_permalink( $post[0]->ID );
		if ( empty( $permalink ) ) {
			return;
		}

		// Let's redirect to the found post URL.
		aioseo()->redirects->helpers->do404Redirect( $permalink, 'SMART' );
		die;
	}

	/**
	 * Tries to redirect to a custom url.
	 *
	 * @since 4.9.1
	 *
	 * @return void
	 */
	private function customRedirect() {
		$url = aioseo()->redirects->options->advanced404s->redirectToCustomUrl;
		if ( empty( $url ) ) {
			return;
		}

		$url = Utils\Request::formatTargetUrl( $url );

		// Prevent redirect loop.
		if ( Utils\Request::formatTargetUrl( Utils\Request::getRequestUrl() ) === $url ) {
			return;
		}

		aioseo()->redirects->helpers->do404Redirect( $url, 'CUSTOM' );
	}

	/**
	 * Tries to redirect to a stored parent reference.
	 *
	 * @since 4.9.1
	 *
	 * @return void
	 */
	private function parentRedirect() {
		if ( ! aioseo()->license->hasCoreFeature( 'redirects', 'advanced-404-settings' ) ) {
			return;
		}

		$requestUrl  = Utils\Request::getRequestUrl();
		$redirect404 = Models\Redirect404::getRedirectByUrl( $requestUrl );
		if ( ! $redirect404->exists() ) {
			return;
		}

		$redirectParent = new RedirectParent404( $redirect404 );

		// Redirects below are in order of importance.
		// 1. First the parent post.
		$redirectParent->postParentRedirect();

		// 2. Second the parent term.
		$redirectParent->termParentRedirect();

		// 3. WooCommerce redirect to the shop page.
		$redirectParent->woocommerceParentRedirect();

		// 4. Lastly, let's try the post type archive.
		$redirectParent->postTypeArchiveRedirect();
	}

	/**
	 * Check if we should avoid redirecting 404s.
	 *
	 * @since 4.9.1
	 *
	 * @return bool
	 */
	public function avoid404Redirect() {
		$avoid      = false;
		$requestUrl = Utils\Request::getRequestUrl();

		// Check html sitemap dedicated page.
		if (
			aioseo()->options->sitemap->html->enable &&
			rtrim( WpUri::excludeHomeUrl( aioseo()->options->sitemap->html->pageUrl ), '/' ) === rtrim( $requestUrl, '/' )
		) {
			$avoid = true;
		}

		// We don't want to redirect 404 urls that will be redirected by WordPress.
		if ( in_array( untrailingslashit( $requestUrl ), WpUri::getRedirectAdminPaths(), true ) ) {
			$avoid = true;
		}

		return apply_filters( 'aioseo_redirects_avoid_advanced_404_redirect', $avoid, $requestUrl );
	}

	/**
	 * Alternate hook for 404 redirects.
	 *
	 * @since 4.9.1
	 *
	 * @param  string $template The template.
	 * @return string
	 */
	public function templateInclude( $template ) {
		$this->templateRedirect();

		return $template;
	}
}