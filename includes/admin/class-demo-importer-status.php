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
}
