<?php

namespace ThemeGrill\Demo\Importer;

use ThemeGrill\Demo\Importer\Services\TrackingService;

class Deactivator {

	public static function init() {
		register_deactivation_hook( TGDM_PLUGIN_FILE, array( __CLASS__, 'deactivate' ) );
	}

	/**
	 * Deactivate TG Demo Importer.
	 */
	public static function deactivate() {
		( new TrackingService() )->unschedule();
	}
}
