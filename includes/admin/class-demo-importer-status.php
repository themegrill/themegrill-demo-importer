<?php
/**
 * Debug/Status Page.
 *
 * @package ThemeGrill_Demo_Importer
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class TG_Demo_Importer_Status
 */
class TG_Demo_Importer_Status {

	/**
	 * Handles the display of System Status.
	 */
	public static function system_status() {
		include_once dirname( __FILE__ ) . '/views/html-admin-page-system-status-report.php';
	}

	/**
	 * Handles the display of FAQ's.
	 */
	public static function demo_import_faqs() {
		include_once dirname( __FILE__ ) . '/views/html-admin-page-demo-import-faqs.php';
	}

	/**
	 * Check if we can add files under the `wp-content/uploads/tg-demo-pack` folder.
	 *
	 * @return string
	 */
	public static function get_write_permission() {
		$output                    = '';
		$wp_upload_dir             = wp_upload_dir( null, false );
		$error                     = $wp_upload_dir['error'];
		$tg_demo_pack_uploads_path = $wp_upload_dir['basedir'] . '/tg-demo-pack/';

		if ( ! $error && is_writable( $tg_demo_pack_uploads_path ) ) {
			$output = __( 'All Fine', 'themegrill-demo-importer' );
		} else {
			$output = __( 'There are some write permission errors on your site.', 'themegrill-demo-importer' );
		}

		return esc_html( $output );
	}

	/**
	 * Check if we can connect to GitHub server for demo import feature.
	 *
	 * @return string
	 */
	public static function get_demo_server_connection_status() {
		$output              = '';
		$package_file_server = wp_remote_get( 'https://d1sb0nhp4t2db4.cloudfront.net/README.md' );
		$http_response_code  = wp_remote_retrieve_response_code( $package_file_server );

		if ( is_wp_error( $package_file_server ) || 200 !== (int) $http_response_code ) {
			$output = __( 'There is a connection issue of your site to our demo pack services.', 'themegrill-demo-importer' );
		} else {
			$output = __( 'Connected', 'themegrill-demo-importer' );
		}

		return esc_html( $output );
	}

	/**
	 * Get lists of active plugins.
	 *
	 * @return array
	 */
	public static function get_active_plugins() {
		// Ensure get_plugins function is loaded.
		if ( ! function_exists( 'get_plugins' ) ) {
			include ABSPATH . '/wp-admin/includes/plugin.php';
		}

		$active_plugins = get_option( 'active_plugins' );
		$active_plugins = array_intersect_key( get_plugins(), array_flip( $active_plugins ) );

		return $active_plugins;
	}

	/**
	 * Get lists of inactive plugins.
	 *
	 * @return array
	 */
	public static function get_inactive_plugins() {
		return array_diff_key( get_plugins(), self::get_active_plugins() );
	}
}
