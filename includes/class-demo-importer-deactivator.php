<?php

class TG_Demo_Importer_Deactivator {

	public static function deactivate() {

		if ( get_option( 'tg_pro_theme_notice_start_time' ) ) {
			delete_option( 'tg_pro_theme_notice_start_time' );
		}

	}

}
