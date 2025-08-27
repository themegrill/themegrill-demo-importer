<?php

namespace ThemeGrill\Demo\Importer\Importers\WXRImporter;

if ( ! class_exists( 'WPImporterLoggerServerSentEvents' ) ) {
	class WPImporterLoggerServerSentEvents extends WPImporterLogger {
		/**
		 * Logs with an arbitrary level.
		 *
		 * @param mixed $level
		 * @param string $message
		 * @param array $context
		 * @return null
		 */
		public function log( $level, $message, array $context = array() ) {
			$data = compact( 'level', 'message' );

			switch ( $level ) {
				case 'emergency':
				case 'alert':
				case 'critical':
				case 'error':
				case 'warning':
				case 'notice':
				case 'info':
					echo "event: log\n";
					echo 'data: ' . wp_json_encode( $data ) . "\n\n";
					// flush();
					break;

				case 'debug':
					if ( defined( 'IMPORT_DEBUG' ) && IMPORT_DEBUG ) {
						echo "event: log\n";
						echo 'data: ' . wp_json_encode( $data ) . "\n\n";
						// flush();
						break;
					}
					break;
			}
		}
	}

}
