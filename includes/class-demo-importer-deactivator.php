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

		// Delete the `Plugin Deactivate` data sets.
		self::plugin_deactivate_notice();

	}

	/**
	 * Delete the options set for `Plugin Deactivate` admin notice.
	 */
	public static function plugin_deactivate_notice() {

		$ignore_deactivate_notice = get_option( 'tg_demo_importer_plugin_deactivate_notice' );

		// Delete the options table row.
		if ( $ignore_deactivate_notice ) {
			delete_option( 'tg_demo_importer_plugin_deactivate_notice' );
		}

	}

}
