<?php

namespace ThemeGrill\Demo\Importer;

class Deactivator {

	public static function init() {
		register_deactivation_hook( TGDM_PLUGIN_FILE, array( __CLASS__, 'deactivate' ) );
	}

	/**
	 * Deactivate TG Demo Importer.
	 */
	public static function deactivate() {
		wp_clear_scheduled_hook( 'tdi_weekly_contribution' );
	}
}
