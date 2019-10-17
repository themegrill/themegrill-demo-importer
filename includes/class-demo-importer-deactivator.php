<?php
/**
 * Class to include the plugin deactivation functionality.
 *
 * Class TG_Demo_Importer_Deactivator
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class to include the plugin deactivation functionality.
 *
 * Class TG_Demo_Importer_Deactivator
 */
class TG_Demo_Importer_Deactivator {

	/**
	 * Deactivation main hook.
	 */
	public static function deactivate() {

		// Delete the `Upgrade To Pro` data sets.
		self::pro_upgrade_notice();

	}

	/**
	 * Delete the options set for `Upgrade To Pro` admin notice.
	 */
	public static function pro_upgrade_notice() {

		// Delete the time set on `wp_options`.
		if ( get_option( 'tg_pro_theme_notice_start_time' ) ) {
			delete_option( 'tg_pro_theme_notice_start_time' );
		}

	}

}
