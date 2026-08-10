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
			$data = $this->sanitize_plugin_descriptions( $data );

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

	/**
	 * Keep only safe tags in plugin feature descriptions (YITH ships HTML).
	 *
	 * @param mixed $data Demo payload from the remote API.
	 * @return mixed
	 */
	private function sanitize_plugin_descriptions( $data ) {
		if ( ! is_object( $data ) || empty( $data->plugins ) || ! is_object( $data->plugins ) ) {
			return $data;
		}

		$allowed = array(
			'a'      => array(
				'href'   => true,
				'title'  => true,
				'target' => true,
				'rel'    => true,
			),
			'strong' => array(),
			'b'      => array(),
			'em'     => array(),
			'i'      => array(),
			'code'   => array(),
			'br'     => array(),
			'p'      => array(),
			'span'   => array(),
		);

		foreach ( $data->plugins as $plugin ) {
			if ( ! is_object( $plugin ) || empty( $plugin->description ) || ! is_string( $plugin->description ) ) {
				continue;
			}

			// Demo API descriptions (notably YITH) include HTML. Decode entities, then
			// keep only safe markup so the Features UI can render bold/links without
			// showing raw tags when the frontend paints the string as text.
			$decoded              = html_entity_decode( $plugin->description, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
			$plugin->description = wp_kses( $decoded, $allowed );
		}

		return $data;
	}
}
