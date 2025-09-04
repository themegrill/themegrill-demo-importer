<?php

namespace ThemeGrill\Demo\Importer\Services;

use Exception;
use ThemeGrill\Demo\Importer\Importers\ContentImporter;
use ThemeGrill\Demo\Importer\Importers\PluginImporter;
use ThemeGrill\Demo\Importer\Importers\ThemeModsImporter;
use ThemeGrill\Demo\Importer\Importers\WidgetsImporter;
use WP_REST_Response;

class ImportService {

	private $contentImporter;
	private $widgetImporter;
	private $customizerImporter;
	private $pluginImporter;

	public function __construct() {
		$this->contentImporter    = new ContentImporter();
		$this->widgetImporter     = new WidgetsImporter();
		$this->customizerImporter = new ThemeModsImporter();
		$this->pluginImporter     = new PluginImporter();
	}

	public function handleImport( $action, $demo_config, $options ) {
		switch ( $action ) {
			case 'install-plugins':
				return $this->installPlugins( $options );

			case 'import-content':
				return $this->importContent( $demo_config, $options );

			case 'import-customizer':
				return $this->importCustomizer( $demo_config, $options );

			case 'import-widgets':
				return $this->importWidgets( $demo_config );

			case 'complete':
				return $this->completeImport( $demo_config );

			default:
				throw new Exception( 'Unknown action: ' . esc_html( $action ) );
		}
	}

	private function installPlugins( $options ) {
		$plugins = $options['plugins'] ?? array();
		return $this->pluginImporter->installPlugins( $plugins );
	}

	private function importContent( $demo_config, $options ) {
		$this->cleanup(); // Delete previous data
		$pages = $options['pages'] ?? array();
		return $this->contentImporter->import( $demo_config, $pages );
	}

	private function importCustomizer( $demo_config, $options ) {
		$args = array(
			'blogname'        => $options['blogname'] ?? '',
			'blogdescription' => $options['blogdescription'] ?? '',
			'custom_logo'     => $options['customLogo'] ?? 0,
			'color_palette'   => $options['colorPalette'],
			'typography'      => $options['typography'],
		);

		return $this->customizerImporter->import( $demo_config, $args );
	}

	private function importWidgets( $demo_config ) {
		return $this->widgetImporter->import( $demo_config );
	}

	private function completeImport( $demo_config ) {
		update_option( 'themegrill_demo_importer_activated_id', $demo_config['slug'] );

		do_action( 'themegrill_ajax_demo_imported', $demo_config['slug'], $demo_config );
		flush_rewrite_rules();
		wp_cache_flush();

		return array(
			'success' => true,
			'message' => 'Demo Imported successfully.',
		);
	}

	public function cleanup() {
		$imported_posts = get_option( 'themegrill_demo_importer_imported_posts', array() );
		$imported_terms = get_option( 'themegrill_demo_importer_imported_terms', array() );

		// Prevents elementor from breaking the cleaning process.
		$_GET['force_delete_kit'] = true;

		foreach ( $imported_posts as $post_id ) {
			// Delete post attachments
			$attachments = get_attached_media( '', $post_id );
			if ( ! empty( $attachments ) ) {
				foreach ( $attachments as $attachment ) {
					wp_delete_attachment( $attachment->ID, true );
				}
			}

			wp_delete_post( $post_id, true );
		}

		foreach ( $imported_terms as $term_id ) {
			$term = get_term( $term_id );
			if ( $term && ! is_wp_error( $term ) ) {
				// Delete term meta
				$term_meta = get_term_meta( $term_id );
				if ( ! empty( $term_meta ) ) {
					foreach ( $term_meta as $meta_key => $meta_value ) {
						delete_term_meta( $term_id, $meta_key );
					}
				}

				// Delete the term
				wp_delete_term( $term_id, $term->taxonomy );
			}
		}

		delete_option( 'themegrill_demo_importer_imported_posts' );
		delete_option( 'themegrill_demo_importer_imported_terms' );

		return new WP_REST_Response(
			array(
				'success' => true,
				'message' => __( 'Cleaned up successfully.', 'themegrill-demo-importer' ),
			),
			200
		);
	}
}
