<?php

namespace ThemeGrill\Demo\Importer\Importers;

use ThemeGrill\Demo\Importer\Importers\WXRImporter\WXRImporter;
use ThemeGrill\Demo\Importer\Logger;
use WP_Error;
use WP_Query;
use WP_REST_Response;

class ContentImporter {

	private $logger;

	public function __construct() {
		$this->logger = Logger::getInstance();
	}

	public function import( $demo, $pages ) {
		do_action( 'themegrill_ajax_before_demo_import' );
		wp_raise_memory_limit( 'memory_limit', '350M' );

		if ( strpos( ini_get( 'disable_functions' ), 'set_time_limit' ) === false ) {
			set_time_limit( 300 );
		}
		if ( $pages ) {
			foreach ( $pages as $page ) {
				$page_title = $page['title'];
				$this->logger->info( "Importing $page_title page...", [ 'import_content_start_time' => true ] );
				$response = $this->import_xml( $page['content'] );
				if ( is_wp_error( $response ) ) {
					$this->logger->error( "Error importing $page_title: " . $response->get_error_message(), [ 'import_content_end_time' => true ] );
				} else {
					$this->logger->info( "$page_title imported successfully.", [ 'import_content_end_time' => true ] );
				}
			}
		} else {
			$content = $demo['content'];
			if ( ! $content ) {
				$this->logger->error( 'No XML content file provided for import.' );
				return new WP_Error( 'no_content_file', 'No content file.', array( 'status' => 500 ) );
			}
			$this->logger->info(
				'Importing content...',
				[
					'import_content_start_time' => true,
				]
			);

			$response = $this->import_xml( $content );
			if ( is_wp_error( $response ) ) {
				$this->logger->error( 'Error importing content: ' . $response->get_error_message(), [ 'import_content_end_time' => true ] );
				return $response;
			}

			$this->logger->info( 'Content imported.', [ 'import_content_end_time' => true ] );

		}

		$this->logger->info( 'Importing core options...', [ 'start_time' => true ] );
		$this->import_core_options( $demo );
		$this->logger->info( 'Core options imported.', [ 'end_time' => true ] );

		return new WP_REST_Response(
			array(
				'success' => true,
				'message' => 'Content Imported.',
			),
			200
		);
	}

	public function import_xml( $content ) {
		// Load Importer API.
		require_once ABSPATH . 'wp-admin/includes/import.php';

		if ( ! class_exists( 'WP_Importer' ) ) {
			$class_wp_importer = ABSPATH . 'wp-admin/includes/class-wp-importer.php';

			if ( file_exists( $class_wp_importer ) ) {
				require $class_wp_importer;
			}
		}

		ob_start();
		$importer = new WXRImporter();
		$logger   = Logger::getInstance();
		$importer->set_logger( $logger );
		$data = $importer->import( $content );

		ob_end_clean();

		update_option( 'themegrill_demo_importer_mapping', $importer->get_mapping_data() );

		if ( is_wp_error( $data ) ) {
			return new WP_Error( 'import_content_failed', 'Error importing content:' . $data->get_error_message(), array( 'status' => 500 ) );
		}

		return true;
	}

	public function import_core_options( $demo ) {
		$show_on_front  = $demo['show_on_front'] ?? '';
		$page_on_front  = $demo['page_on_front'] ?? '';
		$page_for_posts = $demo['page_for_posts'] ?? '';
		if ( $show_on_front ) {
			if ( in_array( $show_on_front, array( 'posts', 'page' ), true ) ) {
				update_option( 'show_on_front', $show_on_front );
			}
		}

		$mapping_data = get_option( 'themegrill_demo_importer_mapping', array() );
		if ( $page_on_front ) {
			$page_on_front_remapped_id = ! empty( $mapping_data['post'][ $page_on_front ] ) ? $mapping_data['post'][ $page_on_front ] : $page_on_front;
			if ( get_post_status( $page_on_front_remapped_id ) === 'publish' ) {
				update_option( 'page_on_front', $page_on_front_remapped_id );
			}
		}
		if ( $page_for_posts ) {
			$page_for_posts_remapped_id = ! empty( $mapping_data['post'][ $page_for_posts ] ) ? $mapping_data['post'][ $page_for_posts ] : $page_for_posts;
			if ( get_post_status( $page_for_posts_remapped_id ) === 'publish' ) {
				update_option( 'page_for_posts', $page_for_posts_remapped_id );
				update_option( 'show_on_front', 'page' );
			}
		}

		return true;
	}

	public function get_page_by_title( $title ) {
		if ( ! $title ) {
			return null;
		}

		$query = new WP_Query(
			array(
				'post_type'              => 'page',
				'title'                  => $title,
				'post_status'            => 'all',
				'posts_per_page'         => 1,
				'no_found_rows'          => true,
				'ignore_sticky_posts'    => true,
				'update_post_term_cache' => false,
				'update_post_meta_cache' => false,
			)
		);

		if ( ! $query->have_posts() ) {
			return null;
		}

		return current( $query->posts );
	}
}
