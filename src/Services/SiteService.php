<?php

namespace ThemeGrill\Demo\Importer\Services;

use Exception;

class SiteService {
	public function fetchSitesData( $slug, $theme ) {
		$base_url = ( 'zakra' === $theme ) ? ZAKRA_BASE_URL : THEMEGRILL_BASE_URL;
		$api_url  = $base_url . '/' . $slug . TGDM_NAMESPACE . '/sites/data';

		// Make HTTP request
		$response = wp_remote_get(
			$api_url,
			[
				'headers'   => [
					'User-Agent'   => 'ThemeGrill/1.0',
					'Content-Type' => 'application/json',
				],
				'sslverify' => true,
				'timeout'   => 30,
			]
		);

		// Handle errors
		if ( is_wp_error( $response ) ) {
			throw new Exception( $response->get_error_message() );
		}

		// Parse JSON
		$data = json_decode( wp_remote_retrieve_body( $response ) );

		if ( json_last_error() !== JSON_ERROR_NONE ) {
			throw new Exception( 'Invalid JSON in API response.' );
		}

		if ( empty( $data ) ) {
			throw new Exception( 'No data found.' );
		}

		return $data;
	}
}
