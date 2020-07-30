<?php
/**
 * Demo Importer Updates.
 *
 * Backward compatibility for demo importer configs and options.
 *
 * @package ThemeGrill_Demo_Importer\Functions
 * @version 1.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Update demo importer options.
 *
 * @since 1.3.4
 */
function tg_update_demo_importer_options() {
	$migrate_options = array(
		'themegrill_demo_imported_id' => 'themegrill_demo_importer_activated_id',
	);

	foreach ( $migrate_options as $old_option => $new_option ) {
		$value = get_option( $old_option );

		if ( $value ) {
			update_option( $new_option, $value );
			delete_option( $old_option );
		}
	}
}
add_action( 'admin_init', 'tg_update_demo_importer_options' );
