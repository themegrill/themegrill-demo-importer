<?php
/**
 * Main application class for ThemeGrill Starter Templates.
 *
 * @package ThemeGrill\StarterTemplates
 * @since   2.0.0
 */

namespace ThemeGrill\StarterTemplates;

use Psr\Log\LoggerInterface;
use ThemeGrill\StarterTemplates\Controllers\V1\{ImportController, SitesController};
use ThemeGrill\StarterTemplates\Services\{ActivationService, DeactivationService};
use ThemeGrill\StarterTemplates\Traits\Hooks;

class App {

	use Hooks;

	private static ?\ThemeGrill\StarterTemplates\App $instance = null;

	private bool $booted = false;

	private LoggerInterface $logger;

	/**
	 * Get the singleton instance of the App class.
	 *
	 * @since 2.0.0
	 * @return \ThemeGrill\StarterTemplates\App The singleton instance.
	 */
	public static function getInstance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Boot the application by initializing components and registering hooks.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function boot() {
		if ( $this->booted ) {
			return;
		}

		$this->doAction( 'themegrill:starter-templates:app-boot', $this );

		$this->logger = Logger::getInstance();

		new Admin();

		$this->registerHooks();
		$this->booted = true;

		$this->doAction( 'themegrill:starter-templates:app-booted', $this );
	}

	/**
	 * Register WordPress hooks and actions for the application.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function registerHooks() {
		$this->doAction( 'themegrill:starter-templates:register-hooks', $this );

		$this->addAction( 'init', [ $this, 'onInit' ], 0 );
		$this->addAction( 'rest_api_init', [ $this, 'onRestApiInit' ] );
		register_activation_hook( THEMEGRILL_STARTER_TEMPLATES_PLUGIN_FILE, [ ActivationService::class, 'activate' ] );
		register_deactivation_hook( THEMEGRILL_STARTER_TEMPLATES_PLUGIN_FILE, [ DeactivationService::class, 'deactivate' ] );

		$this->doAction( 'themegrill:starter-templates:hooks-registered', $this );
	}

	/**
	 * Handle the WordPress 'init' action.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function onInit() {
		$this->doAction( 'themegrill:starter-templates:app-init', $this );

		$this->loadTextDomain();
		$this->registerScriptsStyles();

		$this->doAction( 'themegrill:starter-templates:app-initialized', $this );
	}

	/**
	 * Handle the WordPress 'rest_api_init' action.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function onRestApiInit() {
		$controllers = $this->applyFilters( 'themegrill:starter-templates:rest-controllers', $this->getControllers() );

		foreach ( $controllers as $controller ) {
			( new $controller( $this->logger ) )->register_routes();
		}

		$this->doAction( 'themegrill:starter-templates:rest-api-ready', $controllers );
	}

	/**
	 * Load the plugin text domain for translations.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	private function loadTextDomain() {
		$this->doAction( 'themegrill:starter-templates:load-textdomain' );

		load_plugin_textdomain( 'themegrill-demo-importer', false, THEMEGRILL_STARTER_TEMPLATES_PLUGIN_DIR . '/languages' );

		$this->doAction( 'themegrill:starter-templates:textdomain-loaded' );
	}

	/**
	 * Get the list of REST API controllers.
	 *
	 * @since 2.0.0
	 * @return array Array of controller class names.
	 */
	private function getControllers(): array {
		return [
			SitesController::class,
			ImportController::class,
		];
	}

	/**
	 * Register JavaScript and CSS files for the plugin.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	private function registerScriptsStyles() {
		$this->doAction( 'themegrill:starter-templates:register-scripts-styles' );

		$assetFile = THEMEGRILL_STARTER_TEMPLATES_PLUGIN_DIR . '/dist/starter-templates.asset.php';
		$asset     = file_exists( $assetFile ) ? require $assetFile : [
			'dependencies' => [],
			'version'      => time(),
		];
		$isDev     = defined( 'THEMEGRILL_STARTER_TEMPLATES_DEVELOPMENT' ) && THEMEGRILL_STARTER_TEMPLATES_DEVELOPMENT;
		$rtl       = ! $isDev && is_rtl() ? '-rtl' : '';

		$baseUri = $isDev ? 'http://localhost:8887' : THEMEGRILL_STARTER_TEMPLATES_PLUGIN_DIR_URL . '/dist';

		$asset   = $this->applyFilters( 'themegrill:starter-templates:asset-config', $asset );
		$baseUri = $this->applyFilters( 'themegrill:starter-templates:assets-base-uri', $baseUri );

		if ( $isDev ) {
			wp_register_script(
				'themegrill-starter-templates-runtime',
				'http://localhost:8887/runtime.js',
				[],
				md5( time() . wp_rand( 0, 1000 ) ),
				true
			);
		}

		wp_register_script(
			'themegrill-starter-templates',
			$baseUri . '/starter-templates.js',
			$isDev ? array_merge( $asset['dependencies'], [ 'themegrill-starter-templates-runtime' ] ) : $asset['dependencies'],
			$asset['version'],
			true
		);

		wp_register_style(
			'themegrill-starter-templates',
			$baseUri . "/starter-templates$rtl.css",
			[],
			$asset['version']
		);

		$this->doAction( 'themegrill:starter-templates:scripts-styles-registered', $asset, $baseUri );
	}
}
