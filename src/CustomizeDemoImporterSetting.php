<?php

namespace ThemeGrill\Demo\Importer;

use WP_Customize_Setting;

class CustomizeDemoImporterSetting extends WP_Customize_Setting {
	public function import( $value ) {
		$this->update( $value );
	}
}
