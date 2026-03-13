<?php
namespace AIOSEO\Plugin\Pro\Redirects;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main class.
 *
 * @since 4.9.1
 */
class Redirects {
	/**
	 * Plugin version for enqueueing, etc.
	 * The value is retrieved from the version constant.
	 *
	 * @since 4.9.1
	 *
	 * @var string
	 */
	public $version = '';

	/**
	 * InternalOptions class instance.
	 *
	 * @since 4.9.1
	 *
	 * @var Utils\InternalOptions
	 */
	public $internalOptions = null;

	/**
	 * Options class instance.
	 *
	 * @since 4.9.1
	 *
	 * @var Utils\Options
	 */
	public $options = null;

	/**
	 * Helpers class instance.
	 *
	 * @since 4.9.1
	 *
	 * @var Utils\Helpers
	 */
	public $helpers = null;

	/**
	 * Monitor class instance.
	 *
	 * @since 4.9.1
	 *
	 * @var Main\Monitor
	 */
	public $monitor = null;

	/**
	 * ImportExport class instance.
	 *
	 * @since 4.9.1
	 *
	 * @var ImportExport\ImportExport
	 */
	public $importExport = null;

	/**
	 * Server class instance.
	 *
	 * @since 4.9.1
	 *
	 * @var Main\Server\Unknown|Main\Server\Apache|Main\Server\Nginx
	 */
	public $server = null;

	/**
	 * Redirect class instance.
	 *
	 * @since 4.9.1
	 *
	 * @var Main\Redirect
	 */
	public $redirect = null;

	/**
	 * FullSiteRedirects class instance.
	 *
	 * @since 4.9.1
	 *
	 * @var Main\FullSiteRedirects
	 */
	public $fullSiteRedirects = null;

	/**
	 * HttpHeaders class instance.
	 *
	 * @since 4.9.1
	 *
	 * @var Main\HttpHeaders
	 */
	public $httpHeaders = null;

	/**
	 * Cache class instance.
	 *
	 * @since 4.9.1
	 *
	 * @var Utils\Cache
	 */
	public $cache = null;

	/**
	 * Redirect404 class instance.
	 *
	 * @since 4.9.1
	 *
	 * @var Main\Redirect404
	 */
	public $redirect404 = null;

	/**
	 * Usage class instance.
	 *
	 * @since 4.9.1
	 *
	 * @var Admin\Usage
	 */
	public $usage = null;

	/**
	 * Post redirect class instance.
	 *
	 * @since 4.9.1
	 *
	 * @var Main\PostRedirect
	 */
	public $postRedirect = null;

	/**
	 * Filters class instance.
	 *
	 * @since 4.9.1
	 *
	 * @var Main\Filters
	 */
	public $filters = null;

	/**
	 * Class constructor.
	 *
	 * @since 4.9.1
	 */
	public function __construct() {
		$this->filters           = new Main\Filters();
		$this->internalOptions   = new Utils\InternalOptions();
		$this->options           = new Utils\Options();
		$this->helpers           = new Utils\Helpers();
		$this->cache             = new Utils\Cache();
		$this->monitor           = new Main\Monitor();
		$this->importExport      = new ImportExport\ImportExport();
		$this->server            = Main\Server\Server::getServer();
		$this->redirect          = new Main\Redirect();
		$this->fullSiteRedirects = new Main\FullSiteRedirects();
		$this->httpHeaders       = new Main\HttpHeaders();
		$this->redirect404       = new Main\Redirect404();
		$this->usage             = new Admin\Usage();
		$this->postRedirect      = new Main\PostRedirect();
	}
}