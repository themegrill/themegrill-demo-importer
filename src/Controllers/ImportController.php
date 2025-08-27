<?php

namespace ThemeGrill\Demo\Importer\Controllers;

use Exception;
use ThemeGrill\Demo\Importer\Admin;
use ThemeGrill\Demo\Importer\Logger;
use ThemeGrill\Demo\Importer\Services\ImportService;
use WP_Error;
use WP_REST_Response;

class ImportController {
	private $importService;
	private $logger;

	public function __construct() {
		$this->importService = new ImportService();
		$this->logger        = Logger::getInstance();
	}

	public function install( $request ) {
		$action = $request['action'] ?? '';
		if ( ! $action ) {
			$this->logger->error( 'Invalid action provided' );

			return new WP_Error(
				'invalid_action',
				__( 'Invalid action provided', 'themegrill-demo-importer' ),
				array( 'status' => 500 )
			);
		}
		$demo_config = $request['demo_config'] ?? array();

		if ( ! $demo_config ) {
			$this->logger->error( 'Invalid demo config provided' );

			return new WP_Error(
				'invalid_demo_config',
				__( 'Invalid demo config provided', 'themegrill-demo-importer' ),
				array( 'status' => 500 )
			);
		}
		$options = $request['opts'] ?? array();

		/** @var WP_REST_Response|WP_Error $result */
		$result = null;
		$result = $this->importService->handleImport( $action, $demo_config, $options );

		return rest_ensure_response( $result );
	}

	public function cleanup() {
		try {
			$result = $this->importService->cleanup();
			return new WP_REST_Response( $result, 200 );
		} catch ( Exception $e ) {
			return new WP_Error( 'cleanup_error', $e->getMessage(), [ 'status' => 500 ] );
		}
	}

	public function activate_pro( $request ) {
		$slug = $request['id'] ?? '';

		if ( empty( $slug ) ) {
			return new WP_Error(
				'invalid_slug',
				__( 'Invalid slug provided', 'themegrill-demo-importer' ),
				array( 'status' => 500 )
			);
		}

		if ( 'zakra-pro' === $slug ) {
			activate_plugin( 'zakra-pro/zakra-pro.php' );
		} else {
			switch_theme( $slug );
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'message' => __( 'Pro activated successfully.', 'themegrill-demo-importer' ),
			),
			200
		);
	}

	public function get_localized_data( $request ) {
		$refetch        = (bool) $request->get_param( 'refetch' ) ?? false;
		$localized_data = Admin::get_localized_data();
		$demos          = Admin::get_demo_packages( $refetch );

		if ( array_key_exists( 'message', $demos ) ) {
				$localized_data['data']      = array();
				$localized_data['error_msg'] = $demos['message'];
		} else {
			$localized_data['data'] = $demos;
		}
		return $localized_data;
	}
}
