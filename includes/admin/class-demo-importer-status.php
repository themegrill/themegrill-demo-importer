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
}
