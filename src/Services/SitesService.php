<?php
/**
 * Sites service class for ThemeGrill Starter Templates.
 *
 * @package ThemeGrill\StarterTemplates\Services
 * @since   2.0.0
 */
namespace ThemeGrill\StarterTemplates\Services;

use ThemeGrill\StarterTemplates\Cache\TransientCache;
use ThemeGrill\StarterTemplates\Traits\Hooks;

defined( 'ABSPATH' ) || exit;

class SitesService {

	use Hooks;

	const SOURCES                 = [ 'themegrilldemos', 'zakrademos' ];
	const SITES_LIST_API_ENDPOINT = 'https://%s.com/wp-json/themegrill-demos/v1/sites';
	const SITE_DATA_API_ENDPOINT  = 'https://%s.com/%s/wp-json/themegrill-demos/v1/sites/data';
	const CACHE_PREFIX            = 'sites_%s';
	const CACHE_EXPIRATION        = DAY_IN_SECONDS * 3;
	const SITES_LIST_CACHE_KEY    = self::CACHE_PREFIX . '_list';
	const REQUEST_TIMEOUT         = 30;

	/**
	 * @param string $source Must be 'themegrill' or 'zakra'.
	 * @param bool $forceRefresh Whether to bypass cache and fetch fresh data.
	 * @return array<int, array> List of sites.
	 * @throws \InvalidArgumentException When invalid source is provided.
	 * @throws ApiException When API request fails.
	 */
	/**
	 * Get all sites from a specific source.
	 *
	 * @since 2.0.0
	 * @param string $source Must be 'themegrill' or 'zakra'.
	 * @param bool $forceRefresh Whether to bypass cache and fetch fresh data.
	 * @return array<int, array> List of sites.
	 * @throws \InvalidArgumentException When invalid source is provided.
	 * @throws \Exception When API request fails.
	 */
	public static function getAllSites( string $source, bool $forceRefresh = false ) {
		self::validateSource( $source );

		if ( $forceRefresh ) {
			TransientCache::forget( sprintf( self::SITES_LIST_CACHE_KEY, $source ) );
		}

		$apiUrl = sprintf( self::SITES_LIST_API_ENDPOINT, $source );
		$sites  = TransientCache::remember(
			sprintf( self::SITES_LIST_CACHE_KEY, $source ),
			fn() => self::fetchFromApi( $apiUrl ),
			DAY_IN_SECONDS * 3
		);

		return $sites;
	}

	/**
	 * @param string $source Must be 'themegrill' or 'zakra'.
	 * @param string $siteSlug The site identifier.
	 * @return array Site data.
	 * @throws \InvalidArgumentException When invalid source is provided.
	 * @throws ApiException When API request fails.
	 */
	/**
	 * Get data for a specific site.
	 *
	 * @since 2.0.0
	 * @param string $source Must be 'themegrill' or 'zakra'.
	 * @param string $siteSlug The site identifier.
	 * @return array Site data.
	 * @throws \InvalidArgumentException When invalid source is provided.
	 * @throws \Exception When API request fails.
	 */
	public static function getSiteData( string $source, string $siteSlug ) {
		self::validateSource( $source );

		if ( empty( $siteSlug ) ) {
			throw new \InvalidArgumentException( 'Site slug cannot be empty.' );
		}

		$apiUrl = sprintf( self::SITE_DATA_API_ENDPOINT, $source, $siteSlug );

		return TransientCache::remember(
			sprintf( self::CACHE_PREFIX, $siteSlug ),
			fn() => self::fetchFromApi( $apiUrl ),
			HOUR_IN_SECONDS
		);
	}

	/**
	 * @param string $source
	 * @throws \InvalidArgumentException
	 */
	/**
	 * Validate the source parameter.
	 *
	 * @since 2.0.0
	 * @param string $source The source to validate.
	 * @return void
	 * @throws \InvalidArgumentException When source is invalid.
	 */
	private static function validateSource( string $source ) {
		if ( ! in_array( $source, self::SOURCES, true ) ) {
			throw new \InvalidArgumentException(
				sprintf(
					'Invalid source "%s". Must be one of: %s',
					esc_html( $source ),
					esc_html( implode( ', ', self::SOURCES ) )
				)
			);
		}
	}

	/**
	 * @param string $url
	 * @return array
	 * @throws ApiException
	 */
	/**
	 * Fetch data from the API.
	 *
	 * @since 2.0.0
	 * @param string $url The API URL to fetch from.
	 * @return array The API response data.
	 * @throws \Exception When API request fails.
	 */
	private static function fetchFromApi( string $url ) {
		$homeUrl  = home_url();
		$response = wp_remote_get(
			$url,
			[
				'timeout'   => self::REQUEST_TIMEOUT,
				'headers'   => [
					'Accept'     => 'application/json',
					'User-Agent' => 'ThemeGrill-Starter-Templates/' . THEMEGRILL_STARTER_TEMPLATES_VERSION . " (+$homeUrl)" ,
					'Origin'     => $homeUrl,
					'Referer'    => $homeUrl,
				],
				'sslverify' => true,
			]
		);

		if ( is_wp_error( $response ) ) {
			throw new \Exception(
				'Failed to connect to demo server: ' . esc_html( $response->get_error_message() ),
			);
		}

		$responseCode = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $responseCode ) {
			throw new \Exception(
				sprintf( 'Server returned HTTP %d', esc_html( $responseCode ) ),
			);
		}

		$body = wp_remote_retrieve_body( $response );
		if ( empty( $body ) ) {
			throw new \Exception(
				'Server returned empty response',
			);
		}

		$data = json_decode( $body, true );
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			throw new \Exception(
				'Invalid JSON response: ' . esc_html( json_last_error_msg() ),
			);
		}

		if ( ! is_array( $data ) ) {
			throw new \Exception(
				'Unexpected response format - expected array',
			);
		}

		return $data;
	}

	/**
	 * Get available sources based on current theme.
	 *
	 * @since 2.0.0
	 * @return array Array of available sources.
	 */
	public static function getSource() {
		if ( ThemeService::isCoreTheme() ) {
			return get_template() === 'zakra' ? [ 'zakrademos' ] : [ 'themegrilldemos' ];
		}
		return self::SOURCES;
	}
}
