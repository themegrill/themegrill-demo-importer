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

		$get_all_users           = get_users();
		$theme_notice_start_time = get_option( 'tg_pro_theme_notice_start_time' );

		// Delete the time set on `wp_options`.
		if ( $theme_notice_start_time ) {
			delete_option( 'tg_pro_theme_notice_start_time' );
		}

		// Delete user meta data for theme review notice.
		foreach ( $get_all_users as $user ) {
			$ignored_notice_permanent = get_user_meta( $user->ID, 'tg_nag_pro_theme_notice_ignore', true );
			$ignored_notice_partially = get_user_meta( $user->ID, 'tg_nag_pro_theme_notice_partial_ignore', true );

			// Delete permanent notice remove data.
			if ( $ignored_notice_permanent ) {
				delete_user_meta( $user->ID, 'tg_nag_pro_theme_notice_ignore' );
			}

			// Delete partial notice remove data.
			if ( $ignored_notice_partially ) {
				delete_user_meta( $user->ID, 'tg_nag_pro_theme_notice_partial_ignore' );
			}
		}

	}

}
