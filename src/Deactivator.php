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
	}
}
