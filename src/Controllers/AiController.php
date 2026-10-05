<?php
/**
 * Build with AI proxy controller.
 *
 * @package ThemeGrill\Demo\Importer
 */

namespace ThemeGrill\Demo\Importer\Controllers;

use ThemeGrill\Demo\Importer\Services\AiSiteService;
use WP_REST_Request;
use WP_REST_Response;

defined( 'ABSPATH' ) || exit;

/**
 * Forwards Build with AI requests to the AI backend so the browser only talks
 * to this site and the site token never reaches the page.
 */
class AiController {

	/**
	 * Generate a site, streaming progress events from the backend.
	 *
	 * @since 2.2.0
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|void Response when streaming is unavailable.
	 */
	public function generate( $request ) {
		$params = $request->get_json_params();
		$pages  = isset( $params['pages'] ) && is_array( $params['pages'] ) ? $params['pages'] : array( 'home' );
		$body   = array(
			'brandName'   => sanitize_text_field( $params['brandName'] ?? '' ),
			'description' => sanitize_textarea_field( $params['description'] ?? '' ),
			'niche'       => sanitize_key( $params['niche'] ?? 'auto' ),
			'tone'        => sanitize_key( $params['tone'] ?? 'friendly' ),
			'language'    => sanitize_key( $params['language'] ?? 'en' ),
			'pages'       => array_values( array_map( 'sanitize_key', $pages ) ),
		);

		if ( ! function_exists( 'curl_init' ) ) {
			return $this->forward( '/api/generate', $body );
		}

		$this->stream( '/api/generate', $body );
	}

	/**
	 * Rewrite the copy of one section.
	 *
	 * @since 2.2.0
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function regenerate_section( $request ) {
		$params = $request->get_json_params();

		return $this->forward(
			'/api/regenerate-section',
			array(
				'generationId' => sanitize_text_field( $params['generationId'] ?? '' ),
				'page'         => sanitize_key( $params['page'] ?? '' ),
				'sectionId'    => sanitize_text_field( $params['sectionId'] ?? '' ),
				'instruction'  => sanitize_text_field( $params['instruction'] ?? '' ),
			)
		);
	}

	/**
	 * Rebuild the generated site on another demo.
	 *
	 * @since 2.2.0
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function switch_demo( $request ) {
		$params = $request->get_json_params();

		return $this->forward(
			'/api/switch-demo',
			array(
				'generationId' => sanitize_text_field( $params['generationId'] ?? '' ),
				'demoSlug'     => sanitize_key( $params['demoSlug'] ?? '' ),
			)
		);
	}

	/**
	 * Install and activate the theme the AI demos are built on.
	 *
	 * @since 2.2.0
	 *
	 * @return WP_REST_Response
	 */
	public function prepare_theme() {
		$result = ( new AiSiteService() )->prepare_theme();

		if ( is_wp_error( $result ) ) {
			return new WP_REST_Response(
				array(
					'code'    => $result->get_error_code(),
					'message' => $result->get_error_message(),
				),
				500
			);
		}

		return new WP_REST_Response( $result, 200 );
	}

	/**
	 * Apply the generated copy, images and branding to the imported pages.
	 *
	 * @since 2.2.0
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function apply( $request ) {
		$params = $request->get_json_params();

		$color_map = array();
		foreach ( (array) ( $params['colorMap'] ?? array() ) as $from => $to ) {
			$from = sanitize_hex_color( strtolower( (string) $from ) );
			$to   = sanitize_hex_color( (string) $to );
			if ( $from && $to ) {
				$color_map[ $from ] = $to;
			}
		}

		$font_map = array();
		foreach ( (array) ( $params['fontMap'] ?? array() ) as $from => $to ) {
			$font_map[ sanitize_text_field( (string) $from ) ] = sanitize_text_field( (string) $to );
		}

		$pages = array();
		foreach ( (array) ( $params['pages'] ?? array() ) as $page ) {
			$slots = array();
			foreach ( (array) ( $page['slots'] ?? array() ) as $slot ) {
				$path = (string) ( $slot['path'] ?? '' );
				$attr = (string) ( $slot['attr'] ?? '' );
				if ( ! preg_match( '/^\d+(\.\d+)*$/', $path ) || ! preg_match( '/^[A-Za-z0-9_.@:-]+$/', $attr ) ) {
					continue;
				}
				$slots[] = array(
					'path'   => $path,
					'attr'   => $attr,
					'inHtml' => ! empty( $slot['inHtml'] ),
					'text'   => sanitize_textarea_field( (string) ( $slot['text'] ?? '' ) ),
					'image'  => isset( $slot['image'] ) ? esc_url_raw( (string) $slot['image'] ) : null,
				);
			}
			$pages[] = array(
				'demoPageId'   => absint( $page['demoPageId'] ?? 0 ),
				'demoPageSlug' => sanitize_title( (string) ( $page['demoPageSlug'] ?? '' ) ),
				'slots'        => $slots,
			);
		}

		$result = ( new AiSiteService() )->apply(
			array(
				'siteTitle' => sanitize_text_field( (string) ( $params['siteTitle'] ?? '' ) ),
				'tagline'   => sanitize_text_field( (string) ( $params['tagline'] ?? '' ) ),
				'colorMap'  => $color_map,
				'fontMap'   => $font_map,
				'pages'     => $pages,
			)
		);

		return new WP_REST_Response( $result, 200 );
	}

	/**
	 * Backend URL for an API path.
	 *
	 * @param string $path API path, e.g. /api/generate.
	 * @return string
	 */
	private function url( $path ) {
		return untrailingslashit( TDI_AI_API_BASE ) . $path;
	}

	/**
	 * Headers sent to the backend.
	 *
	 * @param string $accept Accept header value.
	 * @return array
	 */
	private function headers( $accept = 'application/json' ) {
		return array(
			'Content-Type' => 'application/json',
			'Accept'       => $accept,
			'X-Site-Token' => defined( 'TDI_AI_SITE_TOKEN' ) ? TDI_AI_SITE_TOKEN : '',
		);
	}

	/**
	 * Error body in the backend's own shape, so the UI handles both alike.
	 *
	 * @return array
	 */
	private function unreachable_error() {
		return array(
			'error' => array(
				'code'    => 'NETWORK_ERROR',
				'message' => __( 'This site could not reach the AI service. Please try again shortly.', 'themegrill-demo-importer' ),
			),
		);
	}

	/**
	 * Forward a JSON request and relay the backend's status and body.
	 *
	 * @param string $path API path.
	 * @param array  $body Request body.
	 * @return WP_REST_Response
	 */
	private function forward( $path, $body ) {
		$response = wp_remote_post(
			$this->url( $path ),
			array(
				'headers' => $this->headers(),
				'body'    => wp_json_encode( $body ),
				'timeout' => 90,
			)
		);

		if ( is_wp_error( $response ) ) {
			return new WP_REST_Response( $this->unreachable_error(), 502 );
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		return new WP_REST_Response(
			is_array( $data ) ? $data : $this->unreachable_error(),
			is_array( $data ) ? wp_remote_retrieve_response_code( $response ) : 502
		);
	}

	/**
	 * Relay the backend's NDJSON progress stream to the browser as it arrives.
	 *
	 * The WordPress HTTP API buffers whole responses, so this uses cURL directly.
	 *
	 * @param string $path API path.
	 * @param array  $body Request body.
	 */
	private function stream( $path, $body ) {
		$headers = array();
		foreach ( $this->headers( 'application/x-ndjson' ) as $name => $value ) {
			$headers[] = $name . ': ' . $value;
		}

		while ( ob_get_level() ) {
			ob_end_clean();
		}

		// Copy the backend's status and content type before the first body byte.
		$started = false;
		$start   = function ( $handle ) use ( &$started ) {
			if ( $started ) {
				return;
			}
			$started = true;
			status_header( (int) curl_getinfo( $handle, CURLINFO_RESPONSE_CODE ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_getinfo -- Streaming relay, see method doc.
			header( 'Content-Type: ' . ( curl_getinfo( $handle, CURLINFO_CONTENT_TYPE ) ? curl_getinfo( $handle, CURLINFO_CONTENT_TYPE ) : 'application/json' ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_getinfo -- Streaming relay, see method doc.
			header( 'Cache-Control: no-cache, no-transform' );
			header( 'X-Accel-Buffering: no' );
		};

		$handle = curl_init( $this->url( $path ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_init -- Streaming relay, see method doc.
		curl_setopt_array( // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt_array -- Streaming relay, see method doc.
			$handle,
			array(
				CURLOPT_POST          => true,
				CURLOPT_POSTFIELDS    => wp_json_encode( $body ),
				CURLOPT_HTTPHEADER    => $headers,
				CURLOPT_TIMEOUT       => 120,
				CURLOPT_WRITEFUNCTION => function ( $handle, $chunk ) use ( $start ) {
					$start( $handle );
					echo $chunk; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Raw NDJSON relayed from the AI backend.
					flush();
					return strlen( $chunk );
				},
			)
		);

		if ( false === curl_exec( $handle ) && ! $started ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_exec -- Streaming relay, see method doc.
			status_header( 502 );
			header( 'Content-Type: application/json' );
			echo wp_json_encode( $this->unreachable_error() );
		}
		exit;
	}
}
