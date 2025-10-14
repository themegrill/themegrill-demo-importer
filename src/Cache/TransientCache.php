<?php
/**
 * Transient Cache Manager
 *
 * A utility class for managing cached data using WordPress transients.
 *
 * @package ThemeGrill/StarterTemplates/Cache
 */

namespace ThemeGrill\StarterTemplates\Cache;

defined( 'ABSPATH' ) || exit;

class TransientCache {
	private const DEFAULT_EXPIRATION = 12 * HOUR_IN_SECONDS;
	private const KEY_PREFIX         = 'themegrill_starter_templates';

	/**
	 * Get cached data or execute callback to fetch fresh data.
	 *
	 * @param string   $key        Cache key (will be prefixed automatically).
	 * @param callable $callback   Callback function to fetch fresh data.
	 * @param int      $expiration Expiration time in seconds (default: 1 hour).
	 * @return mixed   Cached or fresh data from callback.
	 */
	public static function remember( string $key, callable $callback, int $expiration = self::DEFAULT_EXPIRATION ): mixed {
		$transientKey = self::getPrefixedKey( $key );
		$cachedData   = get_transient( $transientKey );
		if ( $cachedData !== false ) {
			return $cachedData;
		}
		$freshData = $callback();

		if ( ! is_wp_error( $freshData ) && null !== $freshData ) {
			set_transient( $transientKey, $freshData, $expiration );
		}

		return $freshData;
	}

	/**
	 * Manually delete a cached item.
	 *
	 * @param string $key Cache key to delete.
	 * @return bool True if successful, false otherwise.
	 */
	public static function forget( string $key ): bool {
		$transientKey = self::getPrefixedKey( $key );
		return delete_transient( $transientKey );
	}

	/**
	 * Check if a cached item exists.
	 *
	 * @param string $key Cache key to check.
	 * @return bool True if exists, false otherwise.
	 */
	public static function has( string $key ): bool {
		$transientKey = self::getPrefixedKey( $key );
		return get_transient( $transientKey ) !== false;
	}

	/**
	 * Get cached data without callback.
	 *
	 * @param string $key     Cache key to retrieve
	 * @param mixed  $_default Default value if cache doesn't exist.
	 * @return mixed Cached data or default value.
	 */
	public static function get( string $key, mixed $_default = false ): mixed {
		$transientKey = self::getPrefixedKey( $key );
		$cachedData   = get_transient( $transientKey );

		return $cachedData !== false ? $cachedData : $_default;
	}

	/**
	 * Store data in cache.
	 *
	 * @param string $key        Cache key.
	 * @param mixed  $value      Data to cache.
	 * @param int    $expiration Expiration time in seconds (default: 1 hour).
	 * @return bool True if successful, false otherwise.
	 */
	public static function put( string $key, mixed $value, int $expiration = self::DEFAULT_EXPIRATION ): bool {
		$transientKey = self::getPrefixedKey( $key );
		return set_transient( $transientKey, $value, $expiration );
	}

	/**
	 * Get the prefixed transient key.
	 *
	 * @param string $key Original cache key.
	 * @return string Prefixed cache key.
	 */
	private static function getPrefixedKey( string $key ): string {
		return self::KEY_PREFIX . $key;
	}
}
