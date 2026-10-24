<?php

namespace ThemeGrill\Demo\Importer\Importers;

use ThemeGrill\Demo\Importer\Helpers\RemoteRequest;
use ThemeGrill\Demo\Importer\Logger;
use WP_Error;

class MediaImporter {

	const BATCH_SIZE = 10;

	/**
	 * Image sizes made during the import; the rest are made in the background afterwards.
	 */
	const IMPORT_SIZES = array( 'thumbnail', 'medium' );

	/**
	 * Cron hook that makes the image sizes skipped during the import.
	 */
	const SUBSIZES_HOOK = 'themegrill_demo_importer_generate_subsizes';

	/**
	 * Attachments made in the background per cron run.
	 */
	const SUBSIZES_BATCH = 5;

	private $logger;

	public function __construct() {
		$this->logger = Logger::getInstance();
	}

	/**
	 * Process the next batch of pending attachments.
	 *
	 * Returns progress information so the caller can loop until done.
	 *
	 * @param array $skip_urls Image URLs the caller replaces after import, so they are not downloaded.
	 */
	public function import_batch( array $skip_urls = array() ): array {
		$pending = get_option( 'themegrill_demo_importer_pending_attachments', array() );

		// Record total on the first call so the frontend can show overall progress.
		$total = get_option( 'themegrill_demo_importer_media_total', false );
		if ( false === $total ) {
			$pending = $this->without_skipped( $pending, $skip_urls );
			update_option( 'themegrill_demo_importer_pending_attachments', $pending );
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

		$results = $this->process_batch( $batch );
		$new_ids = array();

		foreach ( $batch as $index => $attachment ) {
			$new_post_id = $results[ $index ] ?? null;
			if ( ! $new_post_id || is_wp_error( $new_post_id ) ) {
				// Left unresolved, the demo's original (often stale/staging) URL stays
				// baked into post_content and _elementor_data - log which one and why,
				// so a dead demo-asset host shows up here instead of only as a missing
				// image/video on the front end.
				$reason = is_wp_error( $new_post_id ) ? $new_post_id->get_error_message() : 'unknown error';
				$this->logger->warning( 'Skipped attachment ' . ( $attachment['original_url'] ?? '' ) . ': ' . $reason );
				continue;
			}

			$original_id  = $attachment['original_id'];
			$original_url = $attachment['original_url'];
			$new_url      = wp_get_attachment_url( $new_post_id );

			$mapping['post'][ $original_id ] = $new_post_id;
			$url_remap[ $original_url ]      = $new_url;

			$url_remap = $this->remap_size_urls( $url_remap, $original_url, $new_post_id, $new_url );
			$new_ids[] = $new_post_id;

			// Track for cleanup on reset.
			$imported_posts   = get_option( 'themegrill_demo_importer_imported_posts', array() );
			$imported_posts[] = $new_post_id;
			update_option( 'themegrill_demo_importer_imported_posts', array_unique( $imported_posts ) );
		}

		update_option( 'themegrill_demo_importer_url_remap', $url_remap );
		update_option( 'themegrill_demo_importer_mapping', $mapping );
		self::queue_subsizes( $new_ids );

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
	 * Download a batch of attachments in parallel, then insert each one.
	 *
	 * @param array $batch Pending attachments.
	 * @return array Batch index => new post ID, or WP_Error.
	 */
	private function process_batch( array $batch ) {
		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';

		$results = array();
		$targets = array();

		foreach ( $batch as $index => $attachment ) {
			$upload = $this->prepare_upload( $attachment );
			if ( is_wp_error( $upload ) ) {
				$results[ $index ] = $upload;
				continue;
			}
			$targets[ $index ] = array(
				'url'  => $attachment['remote_url'],
				'file' => $upload,
			);
		}

		foreach ( RemoteRequest::download_many( $targets ) as $index => $downloaded ) {
			$results[ $index ] = is_wp_error( $downloaded )
				? $downloaded
				: $this->insert_attachment( $batch[ $index ], $targets[ $index ]['file'] );
		}

		return $results;
	}

	/**
	 * Reserve the upload path an attachment downloads into.
	 *
	 * @param array $attachment Pending attachment.
	 * @return string|WP_Error Local file path.
	 */
	private function prepare_upload( array $attachment ) {
		// Upload subfolder from _wp_attached_file meta (e.g. "2024/03").
		$upload_date = $attachment['postdata']['post_date'] ?? '';
		foreach ( $attachment['meta'] as $meta_item ) {
			if ( '_wp_attached_file' !== ( $meta_item['key'] ?? '' ) ) {
				continue;
			}
			if ( preg_match( '%^[0-9]{4}/[0-9]{2}%', $meta_item['value'] ?? '', $matches ) ) {
				$upload_date = $matches[0];
			}
			break;
		}

		$file_name = basename( $attachment['remote_url'] );
		$upload    = wp_upload_bits( $file_name, null, '', $upload_date );
		if ( $upload['error'] ) {
			$this->logger->warning( 'Upload dir error for ' . $file_name . ': ' . $upload['error'] );
			return new WP_Error( 'upload_dir_error', $upload['error'] );
		}

		return $upload['file'];
	}

	/**
	 * Insert a downloaded file as an attachment, making only the import image sizes.
	 *
	 * @param array  $attachment Pending attachment.
	 * @param string $file       Downloaded file path.
	 * @return int|WP_Error New post ID.
	 */
	private function insert_attachment( array $attachment, $file ) {
		$info = wp_check_filetype( $file );
		if ( empty( $info['type'] ) ) {
			wp_delete_file( $file );
			return new WP_Error( 'attachment_processing_error', 'Invalid file type' );
		}

		$postdata                   = $attachment['postdata'];
		$postdata['post_mime_type'] = $info['type'];

		$post_id = wp_insert_attachment( $postdata, $file );
		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		self::generate_import_metadata( $post_id, $file );

		return $post_id;
	}

	/**
	 * Generate attachment metadata with only the import image sizes.
	 *
	 * @since 2.2.0
	 *
	 * @param int    $attachment_id Attachment ID.
	 * @param string $file          Attachment file path.
	 */
	public static function generate_import_metadata( $attachment_id, $file ) {
		add_filter( 'intermediate_image_sizes_advanced', array( __CLASS__, 'limit_import_sizes' ) );
		try {
			$metadata = wp_generate_attachment_metadata( $attachment_id, $file );
		} finally {
			remove_filter( 'intermediate_image_sizes_advanced', array( __CLASS__, 'limit_import_sizes' ) );
		}
		wp_update_attachment_metadata( $attachment_id, $metadata );
	}

	/**
	 * Keep only the import image sizes.
	 *
	 * @since 2.2.0
	 *
	 * @param array $sizes Image sizes to generate.
	 * @return array
	 */
	public static function limit_import_sizes( $sizes ) {
		return array_intersect_key( (array) $sizes, array_flip( self::IMPORT_SIZES ) );
	}

	/**
	 * Queue attachments for the image sizes skipped during import.
	 *
	 * @since 2.2.0
	 *
	 * @param int[] $attachment_ids Attachment IDs.
	 */
	public static function queue_subsizes( array $attachment_ids ) {
		if ( empty( $attachment_ids ) ) {
			return;
		}

		$queue = (array) get_option( 'themegrill_demo_importer_pending_subsizes', array() );
		update_option( 'themegrill_demo_importer_pending_subsizes', array_values( array_unique( array_merge( $queue, array_map( 'absint', $attachment_ids ) ) ) ), false );

		if ( ! wp_next_scheduled( self::SUBSIZES_HOOK ) ) {
			wp_schedule_single_event( time() + MINUTE_IN_SECONDS, self::SUBSIZES_HOOK );
		}
	}

	/**
	 * Make the missing image sizes for the next queued attachments.
	 *
	 * @since 2.2.0
	 */
	public static function generate_pending_subsizes() {
		$queue = (array) get_option( 'themegrill_demo_importer_pending_subsizes', array() );
		if ( empty( $queue ) ) {
			return;
		}

		require_once ABSPATH . 'wp-admin/includes/image.php';

		foreach ( array_splice( $queue, 0, self::SUBSIZES_BATCH ) as $attachment_id ) {
			if ( wp_attachment_is_image( $attachment_id ) ) {
				wp_update_image_subsizes( $attachment_id );
			}
		}

		if ( empty( $queue ) ) {
			delete_option( 'themegrill_demo_importer_pending_subsizes' );
			return;
		}

		update_option( 'themegrill_demo_importer_pending_subsizes', $queue, false );
		wp_schedule_single_event( time() + 10, self::SUBSIZES_HOOK );
	}

	/**
	 * Map the demo's size-specific URLs of an attachment to the imported copy.
	 *
	 * Demo content can bake a registered size's URL into post_content (a block with a
	 * "large" sizeSlug). Sizes made later in the background map to the full image, so
	 * nothing stays hotlinked to the demo site meanwhile. Names use the ORIGINAL basename:
	 * wp_upload_bits() may rename the new file ("hero-1.jpg"), but content still says "hero-1024x768.jpg".
	 *
	 * @param array  $url_remap    Old URL => new URL map.
	 * @param string $original_url Demo attachment URL.
	 * @param int    $new_post_id  New attachment ID.
	 * @param string $new_url      New attachment URL.
	 * @return array
	 */
	private function remap_size_urls( array $url_remap, $original_url, $new_post_id, $new_url ) {
		$metadata = wp_get_attachment_metadata( $new_post_id );
		if ( empty( $metadata['width'] ) || empty( $metadata['height'] ) ) {
			return $url_remap;
		}

		$generated = array();
		foreach ( (array) ( $metadata['sizes'] ?? array() ) as $size_data ) {
			if ( ! empty( $size_data['file'] ) ) {
				$generated[ $size_data['width'] . 'x' . $size_data['height'] ] = $size_data['file'];
			}
		}

		$original_dir      = trailingslashit( dirname( $original_url ) );
		$new_dir           = trailingslashit( dirname( $new_url ) );
		$original_basename = pathinfo( wp_basename( $original_url ), PATHINFO_FILENAME );
		$original_ext      = pathinfo( wp_basename( $original_url ), PATHINFO_EXTENSION );

		foreach ( wp_get_registered_image_subsizes() as $size ) {
			$dims = image_resize_dimensions( $metadata['width'], $metadata['height'], $size['width'], $size['height'], $size['crop'] );
			if ( ! $dims ) {
				continue;
			}
			$dimensions = $dims[4] . 'x' . $dims[5];

			$url_remap[ $original_dir . $original_basename . '-' . $dimensions . '.' . $original_ext ] = isset( $generated[ $dimensions ] )
				? $new_dir . $generated[ $dimensions ]
				: $new_url;
		}

		return $url_remap;
	}

	/**
	 * Drop attachments the caller replaces after import, unless one is a featured image.
	 *
	 * @param array $pending   Pending attachments.
	 * @param array $skip_urls Image URLs to leave out.
	 * @return array
	 */
	private function without_skipped( array $pending, array $skip_urls ) {
		if ( empty( $skip_urls ) ) {
			return $pending;
		}

		$skip     = array_flip( array_map( array( $this, 'image_key' ), $skip_urls ) );
		$featured = array_map( 'absint', array_values( (array) get_option( 'themegrill_demo_importer_featured_images', array() ) ) );
		$kept     = array_filter(
			$pending,
			function ( $attachment ) use ( $skip, $featured ) {
				return in_array( absint( $attachment['original_id'] ), $featured, true ) || ! isset( $skip[ $this->image_key( $attachment['original_url'] ) ] );
			}
		);

		$this->logger->info( sprintf( 'Skipping %d replaced attachments', count( $pending ) - count( $kept ) ) );

		return array_values( $kept );
	}

	/**
	 * Year/month/filename of an upload URL without size or "-scaled" suffix, so a block's
	 * size URL matches its attachment whatever host or proxy served it.
	 *
	 * @param string $url Upload URL.
	 * @return string
	 */
	private function image_key( $url ) {
		$path = (string) wp_parse_url( $url, PHP_URL_PATH );
		$path = preg_replace( '/-(\d+x\d+|scaled)(?=\.\w+$)/', '', $path );

		return strtolower( implode( '/', array_slice( explode( '/', $path ), -3 ) ) );
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

		if ( ! empty( $url_remap ) ) {
			$this->remap_content_urls( $url_remap );
			$this->remap_panels_data_urls( $url_remap );
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
		delete_option( 'themegrill_demo_importer_featured_images' );
		delete_option( 'themegrill_demo_importer_media_total' );
		delete_option( 'themegrill_demo_importer_pending_attachments' );
	}

	/**
	 * Replace old attachment URLs in post content and Elementor data, one write per changed row.
	 *
	 * Replaces a REPLACE() query per URL per table, which scanned every row for each of the
	 * hundreds of URL and size variants. strtr() also matches longest keys first.
	 *
	 * @param array $url_remap Map of old attachment URL => new local URL.
	 */
	private function remap_content_urls( array $url_remap ): void {
		global $wpdb;

		// SiteOrigin shortcodes hold JSON with escaped slashes ("https:\/\/..."), so map that form too.
		$map = $url_remap;
		foreach ( $url_remap as $old_url => $new_url ) {
			$map[ str_replace( '/', '\/', $old_url ) ] = str_replace( '/', '\/', $new_url );
		}

		$post_ids = $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE post_content LIKE '%wp-content%'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- One-off import pass over raw content.
		foreach ( array_chunk( $post_ids, 100 ) as $chunk ) {
			$placeholders = implode( ',', array_fill( 0, count( $chunk ), '%d' ) );
			$posts        = $wpdb->get_results( $wpdb->prepare( "SELECT ID, post_content FROM {$wpdb->posts} WHERE ID IN ($placeholders)", ...$chunk ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- $placeholders is a run of %d built above.
			foreach ( $posts as $post ) {
				$content = strtr( $post->post_content, $map );
				if ( $content !== $post->post_content ) {
					$wpdb->update( $wpdb->posts, array( 'post_content' => $content ), array( 'ID' => $post->ID ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Cache cleared next line.
					clean_post_cache( (int) $post->ID );
				}
			}
		}

		$rows = $wpdb->get_results( "SELECT meta_id, post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_elementor_data'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- One-off import pass.
		foreach ( $rows as $row ) {
			$value = strtr( $row->meta_value, $map );
			if ( $value !== $row->meta_value ) {
				$wpdb->update( $wpdb->postmeta, array( 'meta_value' => $value ), array( 'meta_id' => $row->meta_id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Cache cleared next line.
				wp_cache_delete( (int) $row->post_id, 'post_meta' );
			}
		}
	}

	/**
	 * Remap attachment URLs inside SiteOrigin Page Builder's `panels_data` postmeta.
	 *
	 * Unlike Elementor's `_elementor_data` (a plain JSON string, safe for a direct
	 * substring REPLACE), `panels_data` is stored PHP-serialized. A serialized string
	 * encodes each string's byte length up front (`s:45:"https://...";`), so replacing
	 * an old URL with a new one of a different length via SQL REPLACE() desyncs that
	 * length from the actual string, corrupting the entire layout for every widget in
	 * it - not just the one holding the swapped image. Unserializing, walking the
	 * structure, and reserializing keeps it valid regardless of length changes.
	 *
	 * @param array $url_remap Map of old attachment URL => new local URL.
	 */
	private function remap_panels_data_urls( array $url_remap ): void {
		global $wpdb;

		// Scope to posts this import actually touched, and only write back rows
		// that actually changed - an unscoped, unconditional pass would deserialize,
		// reserialize and update every panels_data row on the whole site (including
		// pre-existing SiteOrigin content this import never touched) on every import.
		$imported_posts = get_option( 'themegrill_demo_importer_imported_posts', array() );
		if ( empty( $imported_posts ) ) {
			return;
		}

		$ids          = array_map( 'intval', $imported_posts );
		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
		$rows         = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
			$wpdb->prepare(
				"SELECT meta_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = 'panels_data' AND post_id IN ($placeholders)", // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
				...$ids
			)
		);

		foreach ( $rows as $row ) {
			$data = maybe_unserialize( $row->meta_value );
			if ( empty( $data ) ) {
				continue;
			}

			$remapped = $this->remap_urls_recursive( $data, $url_remap );
			if ( $remapped === $data ) {
				continue;
			}

			$wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$wpdb->postmeta,
				array( 'meta_value' => maybe_serialize( $remapped ) ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				array( 'meta_id' => $row->meta_id )
			);
		}
	}

	/**
	 * Recursively replace attachment URLs within an arbitrarily nested value.
	 *
	 * @param mixed $value     Value to search (array, string, or scalar).
	 * @param array $url_remap Map of old attachment URL => new local URL.
	 * @return mixed
	 */
	private function remap_urls_recursive( $value, array $url_remap ) {
		if ( is_array( $value ) ) {
			foreach ( $value as $key => $item ) {
				$value[ $key ] = $this->remap_urls_recursive( $item, $url_remap );
			}
			return $value;
		}

		if ( is_string( $value ) && false !== strpos( $value, '://' ) ) {
			return strtr( $value, $url_remap );
		}

		return $value;
	}
}
