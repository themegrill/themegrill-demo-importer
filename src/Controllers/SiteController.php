<?php

namespace ThemeGrill\Demo\Importer\Controllers;

use Exception;
use ThemeGrill\Demo\Importer\Services\SiteService;
use WP_REST_Response;

class SiteController {
	private $siteService;

	public function __construct() {
		$this->siteService = new SiteService();
	}

	public function get_sites( $request ) {
		$slug  = $request->get_param( 'id' );
		$theme = $request->get_param( 'theme' );

		try {
			$data = $this->siteService->fetchSitesData( $slug, $theme );

			return new WP_REST_Response(
				[
					'success' => true,
					'data'    => $data,
				],
				200
			);

		} catch ( Exception $e ) {
			return new WP_REST_Response(
				[
					'success' => false,
					'message' => $e->getMessage(),
				],
				500
			);
		}
	}
}
