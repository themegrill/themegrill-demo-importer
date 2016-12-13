<?php
/**
 * ThemeGrill Demo Importer Uninstall
 *
 * Uninstalls the plugin and associated data.
 *
 * @author   ThemeGrill
 * @category Core
 * @version  1.4.0
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Delete options.
delete_option( 'themegrill_demo_imported_id' );
delete_option( 'themegrill_demo_imported_notice_dismiss' );
