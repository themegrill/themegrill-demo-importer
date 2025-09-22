<?php

namespace ThemeGrill\Demo\Importer;

use ThemeGrill\Demo\Importer\Controllers\ImportController;
use ThemeGrill\Demo\Importer\Controllers\SiteController;
use ThemeGrill\Demo\Importer\Traits\Singleton;
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
						'action' => array(
							'type'     => 'string',
							'required' => 'true',
							'enum'     => array( 'install-plugins', 'import-content', 'import-customizer', 'import-widgets', 'complete' ),
						),
						// 'complete' => array(
						//  'type'     => 'boolean',
						//  'required' => true,
						//  'default'  => false,
						// ),
						// 'demo-data' => [

						// ],
						// 'opts'   => array(
						//  'type'       => 'object',
						//  'required'   => false,
						//  'properties' => array(
						//      'logo'                => array(
						//          'type' => 'number',
						//      ),
						//  ),
						// ),
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
					'permission_callback' => '__return_true',
				),
			)
		);
	}
}
