<?php

namespace ThemeGrill\Demo\Importer;

class Activator {

	/**
	 * Install TG Demo Importer.
	 */
	public static function activate() {
		$files = array(
			array(
				'base'    => TGDM_DEMO_DIR,
				'file'    => 'index.html',
				'content' => '',
			),
		);

		// Bypass if filesystem is read-only and/or non-standard upload system is used.
		if ( ! is_blog_installed() || apply_filters( 'themegrill_demo_importer_install_skip_create_files', false ) ) {
			return;
		}

		// Install files and folders.
		foreach ( $files as $file ) {
			if ( wp_mkdir_p( $file['base'] ) && ! file_exists( trailingslashit( $file['base'] ) . $file['file'] ) ) {
				$file_handle = @fopen( trailingslashit( $file['base'] ) . $file['file'], 'w' ); // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.file_system_read_fopen
				if ( $file_handle ) {
					fwrite( $file_handle, $file['content'] ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fwrite
					fclose( $file_handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fclose
				}
			}
		}

		// Redirect to demo importer page.
		set_transient( '_tg_demo_importer_activation_redirect', 1, 30 );
	}
}
