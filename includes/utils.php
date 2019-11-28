<?php

class TG_Demo_Importer_Utils {
	public static function get_theme_supported_themes() {
		$core = array(
			'spacious',
			'colormag',
			'flash',
			'estore',
			'ample',
			'accelerate',
			'colornews',
			'foodhunt',
			'fitclub',
			'radiate',
			'freedom',
			'himalayas',
			'esteem',
			'envince',
			'suffice',
			'explore',
			'masonic',
			'cenote',
			'zakra',
		);
		// Check for official core themes pro version.
		$pro = array_diff( $core, array( 'explore', 'masonic' ) );

		if ( ! empty( $pro ) ) {
			$pro = preg_replace( '/$/', '-pro', $pro );
		}

		return array_merge( $core, $pro );
	}
}

