<?php

namespace ThemeGrill\Demo\Importer;

class Activator {

	public static function init() {
		register_activation_hook( TGDM_PLUGIN_FILE, array( __CLASS__, 'activate' ) );
	}

	/**
	 * Install TG Demo Importer.
	 */
	public static function activate() {
	}
}
