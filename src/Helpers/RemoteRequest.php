<?php

namespace ThemeGrill\Demo\Importer\Helpers;

use WP_Error;

/**
 * Hardened HTTP helpers for demo import remote fetches.
 *
 * Uses wp_safe_remote_get() so WordPress rejects loopback/private IPs, and
 * optionally enforces a ThemeGrill host allowlist for attacker-controlled URLs
 * (e.g. demo_config.content).
 */
class RemoteRequest {

	/**
	 * Hosts permitted for allowlisted remote fetches (content XML, etc.).
	 *
	 * @return string[]
	 */
	public static function get_allowed_hosts() {
		/**
		 * Filter the allowlisted hosts for demo importer remote downloads.
		 *
		 * @param string[] $hosts Allowed hostnames (lowercase recommended).
		 */
		return apply_filters(
			'themegrill_demo_importer_allowed_remote_hosts',
			array(
				'api.themegrill.com',
				'demo.themegrill.com',
				'themegrilldemos.com',
				'www.themegrilldemos.com',
				'zakrademos.com',
				'www.zakrademos.com',
			)
		);
	}

	/**
	 * Whether a URL is http(s) and its host is on the allowlist.
	 *
	 * @param string $url Remote URL.
	 * @return bool
	 */
	public static function is_allowed_url( $url ) {
		if ( ! is_string( $url ) || '' === $url ) {
			return false;
		}

		$parsed = wp_parse_url( $url );
		if ( empty( $parsed['scheme'] ) || empty( $parsed['host'] ) ) {
			return false;
		}

		$scheme = strtolower( (string) $parsed['scheme'] );
		if ( ! in_array( $scheme, array( 'http', 'https' ), true ) ) {
			return false;
		}

		$host    = strtolower( (string) $parsed['host'] );
		$allowed = array_map( 'strtolower', self::get_allowed_hosts() );

		return in_array( $host, $allowed, true );
	}

	/**
	 * Perform a safe remote GET.
	 *
	 * @param string $url               URL to fetch.
	 * @param array  $args              Arguments for wp_safe_remote_get().
	 * @param bool   $require_allowlist When true, reject URLs whose host is not allowlisted.
	 * @return array|WP_Error
	 */
	public static function get( $url, $args = array(), $require_allowlist = false ) {
		if ( $require_allowlist && ! self::is_allowed_url( $url ) ) {
			return new WP_Error(
				'tgdm_disallowed_url',
				__( 'The remote URL is not from an allowed ThemeGrill demo host.', 'themegrill-demo-importer' )
			);
		}

		return wp_safe_remote_get( $url, $args );
	}
}
