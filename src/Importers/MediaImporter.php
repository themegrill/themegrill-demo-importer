<?php

namespace ThemeGrill\Demo\Importer\Importers;

use ThemeGrill\Demo\Importer\Logger;
use WP_Error;

class MediaImporter {

	const BATCH_SIZE = 5;

	private $logger;

	public function __construct() {
		$this->logger = Logger::getInstance();
	}

	/**
	 * Process the next batch of pending attachments.
	 *
	 * Returns progress information so the caller can loop until done.
	 */
	public function import_batch(): array {
		$pending = get_option( 'themegrill_demo_importer_pending_attachments', array() );

		// Record total on the first call so the frontend can show overall progress.
		$total = get_option( 'themegrill_demo_importer_media_total', false );
		if ( false === $total ) {
			$total = count( $pending );
			update_option( 'themegrill_demo_importer_media_total', $total );
		}
		$total = (int) $total;

		if ( empty( $pending ) ) {
			$this->finalize();
			return array(
				'success'   => true,
				'done'      => true,
				'remaining' => 0,
				'total'     => $total,
			);
		}

		$batch = array_splice( $pending, 0, self::BATCH_SIZE );
		update_option( 'themegrill_demo_importer_pending_attachments', $pending );

		$url_remap = get_option( 'themegrill_demo_importer_url_remap', array() );
		$mapping   = get_option( 'themegrill_demo_importer_mapping', array() );

		foreach ( $batch as $attachment ) {
			$new_post_id = $this->process_single( $attachment );
			if ( ! $new_post_id || is_wp_error( $new_post_id ) ) {
				continue;
			}

			$original_id = $attachment['original_id'];
			$remote_url  = $attachment['remote_url'];

			$mapping['post'][ $original_id ] = $new_post_id;
			$url_remap[ $remote_url ]        = wp_get_attachment_url( $new_post_id );

			// Track for cleanup on reset.
			$imported_posts   = get_option( 'themegrill_demo_importer_imported_posts', array() );
			$imported_posts[] = $new_post_id;
			update_option( 'themegrill_demo_importer_imported_posts', array_unique( $imported_posts ) );
		}

		update_option( 'themegrill_demo_importer_url_remap', $url_remap );
		update_option( 'themegrill_demo_importer_mapping', $mapping );

		$remaining = count( $pending );

		if ( 0 === $remaining ) {
			$this->finalize();
			return array(
				'success'   => true,
				'done'      => true,
				'remaining' => 0,
				'total'     => $total,
			);
		}

		return array(
			'success'   => true,
			'done'      => false,
			'remaining' => $remaining,
			'total'     => $total,
		);
	}

	/**
	 * Download and insert a single attachment.
	 *
	 * @return int|WP_Error New post ID on success.
	 */
	private function process_single( array $attachment ) {
		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';

		$postdata   = $attachment['postdata'];
		$meta       = $attachment['meta'];
		$remote_url = $attachment['remote_url'];

		// Determine upload subfolder from _wp_attached_file meta (e.g. "2024/03").
		$postdata['upload_date'] = $postdata['post_date'] ?? '';
		foreach ( $meta as $meta_item ) {
			if ( '_wp_attached_file' !== ( $meta_item['key'] ?? '' ) ) {
				continue;
			}
			if ( preg_match( '%^[0-9]{4}/[0-9]{2}%', $meta_item['value'] ?? '', $matches ) ) {
				$postdata['upload_date'] = $matches[0];
			}
			break;
		}

		$file_name = basename( $remote_url );
		$upload    = wp_upload_bits( $file_name, 0, '', $postdata['upload_date'] );
		if ( $upload['error'] ) {
			$this->logger->warning( 'Upload dir error for ' . $file_name . ': ' . $upload['error'] );
			return new WP_Error( 'upload_dir_error', $upload['error'] );
		}

		$response = wp_remote_get(
			$remote_url,
			array(
				'stream'    => true,
				'filename'  => $upload['file'],
				'headers'   => array( 'User-Agent' => 'ThemeGrill Starter Template/1.0' ),
				'sslverify' => true,
				'timeout'   => 30,
			)
		);

		if ( is_wp_error( $response ) ) {
			@unlink( $upload['file'] ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
			$this->logger->warning( 'Failed to fetch ' . $remote_url . ': ' . $response->get_error_message() );
			return $response;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		if ( 200 !== $code ) {
			@unlink( $upload['file'] ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
			return new WP_Error( 'import_file_error', 'Server returned ' . $code . ' for ' . $remote_url );
		}

		if ( 0 === filesize( $upload['file'] ) ) {
			@unlink( $upload['file'] ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
			return new WP_Error( 'import_file_error', 'Zero size file downloaded' );
		}

		$info = wp_check_filetype( $upload['file'] );
		if ( ! $info ) {
			@unlink( $upload['file'] ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
			return new WP_Error( 'attachment_processing_error', 'Invalid file type' );
		}

		$postdata['post_mime_type'] = $info['type'];

		$post_id = wp_insert_attachment( $postdata, $upload['file'] );
		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		$metadata = wp_generate_attachment_metadata( $post_id, $upload['file'] );
		wp_update_attachment_metadata( $post_id, $metadata );

		return $post_id;
	}

	/**
	 * Run after all batches are complete: replace attachment URLs in post content
	 * and remap featured image (_thumbnail_id) meta to the newly imported IDs.
	 */
	private function finalize(): void {
		global $wpdb;

		$url_remap       = get_option( 'themegrill_demo_importer_url_remap', array() );
		$mapping         = get_option( 'themegrill_demo_importer_mapping', array() );
		$featured_images = get_option( 'themegrill_demo_importer_featured_images', array() );

		// Replace old attachment URLs in post_content and _elementor_data postmeta
		// (longest first to avoid partial matches).
		if ( ! empty( $url_remap ) ) {
			uksort( $url_remap, fn( $a, $b ) => strlen( $b ) - strlen( $a ) );
			foreach ( $url_remap as $old_url => $new_url ) {
				$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
					$wpdb->prepare(
						"UPDATE {$wpdb->posts} SET post_content = REPLACE(post_content, %s, %s)",
						$old_url,
						$new_url
					)
				);
				$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
					$wpdb->prepare(
						"UPDATE {$wpdb->postmeta} SET meta_value = REPLACE(meta_value, %s, %s) WHERE meta_key = '_elementor_data'",
						$old_url,
						$new_url
					)
				);
			}
		}

		// Update _thumbnail_id to point to newly imported attachment IDs.
		foreach ( $featured_images as $post_id => $old_attachment_id ) {
			if ( isset( $mapping['post'][ $old_attachment_id ] ) ) {
				$new_id = (int) $mapping['post'][ $old_attachment_id ];
				if ( $new_id !== (int) $old_attachment_id ) {
					update_post_meta( $post_id, '_thumbnail_id', $new_id );
				}
			}
		}

		// Delete imported Elementor compiled CSS so it regenerates with local paths.
		// The demo CSS files reference the original multisite server and will 404 on the new site.
		$imported_posts = get_option( 'themegrill_demo_importer_imported_posts', array() );
		if ( ! empty( $imported_posts ) ) {
			$ids          = array_map( 'intval', $imported_posts );
			$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
			$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$wpdb->prepare(
					"DELETE FROM {$wpdb->postmeta} WHERE post_id IN ($placeholders) AND meta_key IN ('_elementor_css', '_elementor_inline_css')", // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
					...$ids
				)
			);
		}

		// Clear Elementor's global file cache so pages regenerate CSS on next load.
		if ( class_exists( '\Elementor\Plugin' ) && isset( \Elementor\Plugin::$instance->files_manager ) ) {
			\Elementor\Plugin::$instance->files_manager->clear_cache();
		}

		// Clean up temporary options.
		delete_option( 'themegrill_demo_importer_url_remap' );
		delete_option( 'themegrill_demo_importer_featured_images' );
		delete_option( 'themegrill_demo_importer_media_total' );
		delete_option( 'themegrill_demo_importer_pending_attachments' );
	}
}
