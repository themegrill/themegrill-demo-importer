<?php

namespace ThemeGrill\Demo\Importer;

use ThemeGrill\Demo\Importer\Controllers\AiController;
use ThemeGrill\Demo\Importer\Controllers\ImportController;
use ThemeGrill\Demo\Importer\Controllers\SiteController;
use ThemeGrill\Demo\Importer\Traits\Singleton;
use ThemeGrill\Demo\Importer\Validators\DemoConfigValidator;
use WP_Error;
use WP_Query;
use WP_REST_Response;

class RestApi {
	use Singleton;

	protected $namespace = 'tg-demo-importer/v1';
	private $importController;

	/**
	 * Initialize REST API functionality
	 */
	protected function init() {
		$this->importController = new ImportController();
		add_action( 'rest_api_init', array( $this, 'register_api_endpoints' ) );
	}

	/**
	 * Register endpoints for the REST API.
	 */
	public function register_api_endpoints() {
		register_rest_route(
			$this->namespace,
			'/data',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => [ new SiteController(), 'get_sites' ],
					'permission_callback' => '__return_true',
				),
			)
		);
		register_rest_route(
			$this->namespace,
			'/install',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => [ $this->importController, 'install' ],
					'permission_callback' => function () {
						return current_user_can( 'install_themes' );
					},
					'args'                => array(
						'action'      => array(
							'type'     => 'string',
							'required' => 'true',
							'enum'     => array( 'install-plugins', 'import-content', 'import-content-posts', 'import-media', 'import-customizer', 'import-widgets', 'complete' ),
						),
						'demo_config' => array(
							'type'              => 'object',
							'description'       => __( 'Demo configuration to import. Theme specific keys are allowed, security relevant fields are validated.', 'themegrill-demo-importer' ),
							'validate_callback' => array( DemoConfigValidator::class, 'validate' ),
							'sanitize_callback' => array( DemoConfigValidator::class, 'sanitize' ),
						),
					),
				),
			)
		);
		register_rest_route(
			$this->namespace,
			'/cleanup',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => [ $this->importController, 'cleanup' ],
					'permission_callback' => function () {
						return current_user_can( 'install_themes' );
					},
				),
			)
		);
		register_rest_route(
			$this->namespace,
			'/activate-pro',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => [ $this->importController, 'activate_pro' ],
					'permission_callback' => function () {
						return current_user_can( 'install_themes' );
					},
				),
			)
		);
		register_rest_route(
			$this->namespace,
			'/localized-data',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => [ $this->importController, 'get_localized_data' ],
					'permission_callback' => function () {
						return current_user_can( 'install_themes' );
					},
				),
			)
		);
		register_rest_route(
			$this->namespace,
			'/tracking-consent',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => [ $this->importController, 'save_tracking_consent' ],
					'permission_callback' => function () {
						return current_user_can( 'install_themes' );
					},
					'args'                => array(
						'allow_tracking' => array(
							'type'    => 'boolean',
							'default' => false,
						),
					),
				),
			)
		);

		if ( defined( 'TDI_AI_API_BASE' ) && TDI_AI_API_BASE ) {
			$this->register_ai_endpoints();
		}
	}

	/**
	 * Register the Build with AI proxy endpoints.
	 */
	private function register_ai_endpoints() {
		$controller = new AiController();
		$routes     = array(
			'/ai/generate'           => 'generate',
			'/ai/regenerate-section' => 'regenerate_section',
			'/ai/switch-demo'        => 'switch_demo',
			'/ai/apply'              => 'apply',
			'/ai/color-map'          => 'color_map',
		);

		foreach ( $routes as $route => $method ) {
			register_rest_route(
				$this->namespace,
				$route,
				array(
					array(
						'methods'             => 'POST',
						'callback'            => array( $controller, $method ),
						'permission_callback' => function () {
							return current_user_can( 'manage_options' );
						},
					),
				)
			);
		}

		register_rest_route(
			$this->namespace,
			'/ai/prepare-theme',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $controller, 'prepare_theme' ),
					'permission_callback' => function () {
						return current_user_can( 'install_themes' ) && current_user_can( 'switch_themes' );
					},
				),
			)
		);
	}
}
