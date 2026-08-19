<?php

namespace ThemeGrill\Demo\Importer\Helpers;

/**
 * Shared checks for whether a demo config depends on a given plugin/feature.
 *
 * Used both while the import is still running (ImportService, to react before
 * content is parsed) and afterwards (ImportHooks, to run post-import setup).
 */
class DemoRequirements {

	/**
	 * Whether the demo payload lists a plugin matching any of the given needles.
	 *
	 * Plugin keys from the API look like `elementor/elementor.php`.
	 *
	 * @param array    $demo_data Demo config.
	 * @param string[] $needles   Substrings or filenames to match against plugin keys.
	 * @return bool
	 */
	public static function has_plugin( $demo_data, $needles ) {
		$plugins = $demo_data['plugins'] ?? array();
		if ( empty( $plugins ) || ! is_array( $plugins ) ) {
			return false;
		}

		$keys = array_map( 'strval', array_keys( $plugins ) );
		foreach ( (array) $needles as $needle ) {
			$needle = (string) $needle;
			if ( '' === $needle ) {
				continue;
			}
			foreach ( $keys as $key ) {
				if ( $key === $needle || false !== strpos( $key, $needle ) ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Whether the imported demo uses URM and user has selected the plugin to import.
	 *
	 * @param array $demo_data Demo config.
	 * @return bool
	 */
	public static function requires_urm( $demo_data ) {
		if ( ! self::has_plugin( $demo_data, array( 'user-registration', 'user-registration.php' ) ) ) {
			return false;
		}

		$selected_plugins = get_option( 'themegrill_demo_importer_selected_plugins', null );

		if ( is_array( $selected_plugins ) ) {
			foreach ( $selected_plugins as $plugin ) {
				if ( false !== strpos( (string) $plugin, 'user-registration' ) ) {
					return true;
				}
			}

			return false;
		}

		// No selection recorded for this run — fall back to the demo's declared dependency.
		return true;
	}

	/**
	 * Turn on the `user-registration-membership` feature flag when the demo needs it.
	 *
	 * @param array $demo_data Demo config.
	 * @return void
	 */
	public static function maybe_enable_urm_membership_feature( $demo_data ) {
		if ( ! self::requires_urm( $demo_data ) ) {
			return;
		}

		$enabled_features = get_option( 'user_registration_enabled_features', array() );
		if ( ! is_array( $enabled_features ) ) {
			$enabled_features = array();
		}

		if ( ! in_array( 'user-registration-membership', $enabled_features, true ) ) {
			$enabled_features[] = 'user-registration-membership';
			update_option( 'user_registration_enabled_features', $enabled_features );
		}
	}

	/**
	 * Make sure the URM membership DB tables exist, right after the
	 * `user-registration-membership` feature flag is turned on.
	 *
	 * URM only registers its membership module classes (and their Composer
	 * autoloader) at plugin bootstrap, gated on `ur_check_module_activation('membership')`
	 * reading the `user_registration_enabled_features` option. When we flip that
	 * option on mid-request, the module bootstrap for *this* request already ran
	 * with the old value, so `WPEverest\URMembership\Admin\Database\Database` isn't
	 * autoloadable yet and a plain `class_exists()` check silently (and wrongly)
	 * reports it missing. Force-including the module bootstrap first fixes that.
	 *
	 * @return void
	 */
	public static function ensure_urm_membership_tables() {
		if ( ! function_exists( 'UR' ) || ! defined( 'UR_ABSPATH' ) ) {
			return;
		}

		if ( ! class_exists( 'WPEverest\URMembership\Admin\Database\Database' ) ) {
			$urm_bootstrap = UR_ABSPATH . 'modules/membership/user-registration-membership.php';

			if ( file_exists( $urm_bootstrap ) ) {
				include_once $urm_bootstrap;
			}
		}

		if ( class_exists( 'WPEverest\URMembership\Admin\Database\Database' ) ) {
			\WPEverest\URMembership\Admin\Database\Database::create_tables();
		}
	}
}
