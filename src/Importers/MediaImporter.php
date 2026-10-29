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

			// The demo may reference this one file by either of the URLs its export carried
			// (guid and attachment_url can name the plain, `-scaled` or edited copy, or sit
			// under a different uploads path), so register every spelling we were given.
			$source_urls = array_values( array_unique( array_filter( array( $original_url, $attachment['alias_url'] ?? '' ) ) ) );

			foreach ( $source_urls as $source_url ) {
				$url_remap[ $source_url ] = $new_url;
			}

			foreach ( $source_urls as $source_url ) {
				$url_remap = $this->remap_size_urls( $url_remap, $source_url, $new_post_id, $new_url );
			}
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
		$sources = array();
		foreach ( $batch as $index => $attachment ) {
			$sources[ $index ] = array_values( array_filter( array( $attachment['remote_url'], $attachment['remote_fallback_url'] ?? '' ) ) );
			$results[ $index ] = new WP_Error( 'import_file_error', 'No source URL for attachment' );
		}

		// guid and attachment_url can name different copies of the same image, and either can be
		// the dead one: download every first choice together, then retry the failures from the other.
		for ( $round = 0; $round < 2; $round++ ) {
			$targets = array();
			foreach ( $sources as $index => $urls ) {
				if ( empty( $urls[ $round ] ) ) {
					continue;
				}
				$upload = $this->prepare_upload( $batch[ $index ], $urls[ $round ] );
				if ( is_wp_error( $upload ) ) {
					$results[ $index ] = $upload;
					continue;
				}
				$targets[ $index ] = array(
					'url'  => $urls[ $round ],
					'file' => $upload,
				);
			}

			foreach ( RemoteRequest::download_many( $targets ) as $index => $downloaded ) {
				$results[ $index ] = is_wp_error( $downloaded )
					? $downloaded
					: $this->insert_attachment( $batch[ $index ], $targets[ $index ]['file'] );

				if ( ! is_wp_error( $results[ $index ] ) ) {
					unset( $sources[ $index ] );
				} elseif ( isset( $sources[ $index ][ $round + 1 ] ) ) {
					$this->logger->notice( 'Retrying ' . $targets[ $index ]['url'] . ' from its other exported URL: ' . $results[ $index ]->get_error_message() );
				}
			}
		}

		return $results;
	}

	/**
	 * Reserve the upload path an attachment downloads into.
	 *
	 * @param array  $attachment Pending attachment.
	 * @param string $url        Source URL being downloaded.
	 * @return string|WP_Error Local file path.
	 */
	private function prepare_upload( array $attachment, $url ) {
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

		$file_name = basename( $url );
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
			$this->remap_foreign_elementor_media( $url_remap );
		}

		// Term and attachment IDs inside panels_data are numeric, so the URL pass above
		// cannot see them. Runs outside that guard: a demo with no media still has
		// widgets pointing at the demo's categories.
		$this->remap_panels_data_ids( $mapping );

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
		delete_option( 'themegrill_demo_importer_previous_imported_posts' );
	}

	/**
	 * Path of an uploads URL relative to the uploads directory, with any multisite
	 * `sites/{id}/` segment removed, e.g. "2020/06/logo.png".
	 *
	 * @param  string $url Media URL.
	 * @return string Empty when the URL isn't under an uploads directory.
	 */
	private function uploads_relative_path( string $url ): string {
		$path = (string) wp_parse_url( $url, PHP_URL_PATH );
		if ( ! preg_match( '#/uploads/(?:sites/\d+/)?(.+)$#', $path, $matches ) ) {
			return '';
		}

		return $matches[1];
	}

	/**
	 * Point images that still reference another host at the attachments this import created.
	 *
	 * Elementor data exported from the demo can reference an image through a different
	 * host than the one the WXR attachment came from (a staging/sandbox site), and with
	 * the old site's attachment ID as a string. Neither matches the URL remap or the ID
	 * mapping, so the image stays hotlinked to a host that is often gone, or resolves to
	 * whatever unrelated attachment happens to own that ID here. The file itself was
	 * imported, so match it by its uploads-relative path and set both url and id.
	 *
	 * @param array $url_remap Map of old attachment URL => new local URL.
	 */
	private function remap_foreign_elementor_media( array $url_remap ): void {
		global $wpdb;

		// Only posts imported by this run: the list also holds earlier imports (kept for
		// cleanup), and their content must not be rewritten to this demo's attachments.
		$imported_posts = array_diff(
			array_map( 'intval', (array) get_option( 'themegrill_demo_importer_imported_posts', array() ) ),
			array_map( 'intval', (array) get_option( 'themegrill_demo_importer_previous_imported_posts', array() ) )
		);
		if ( empty( $imported_posts ) ) {
			return;
		}

		$by_path = array();
		foreach ( $url_remap as $old_url => $new_url ) {
			$relative = $this->uploads_relative_path( $old_url );
			if ( '' !== $relative && ! isset( $by_path[ $relative ] ) ) {
				$by_path[ $relative ] = $new_url;
			}
		}

		if ( empty( $by_path ) ) {
			return;
		}

		$ids          = array_map( 'intval', $imported_posts );
		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
		$rows         = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
			$wpdb->prepare(
				"SELECT meta_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_elementor_data' AND post_id IN ($placeholders)", // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
				...$ids
			)
		);

		$local_host = wp_parse_url( home_url(), PHP_URL_HOST );

		foreach ( $rows as $row ) {
			$data = json_decode( $row->meta_value, true );
			if ( ! is_array( $data ) ) {
				continue;
			}

			$remapped = $this->remap_foreign_media_recursive( $data, $by_path, $local_host );
			if ( $remapped === $data ) {
				continue;
			}

			$wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$wpdb->postmeta,
				array( 'meta_value' => wp_json_encode( $remapped ) ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				array( 'meta_id' => $row->meta_id )
			);
		}
	}

	/**
	 * Recursively rewrite Elementor media controls ({ url, id }) that point at another host.
	 *
	 * @param array  $data       Decoded Elementor data (or a nested part of it).
	 * @param array  $by_path    Map of uploads-relative path => new local URL.
	 * @param string $local_host This site's host.
	 * @return array
	 */
	private function remap_foreign_media_recursive( array $data, array $by_path, string $local_host ): array {
		foreach ( $data as $key => $value ) {
			if ( is_array( $value ) ) {
				$data[ $key ] = $this->remap_foreign_media_recursive( $value, $by_path, $local_host );
			}
		}

		if ( empty( $data['url'] ) || ! is_string( $data['url'] ) ) {
			return $data;
		}

		$host = wp_parse_url( $data['url'], PHP_URL_HOST );
		if ( ! $host || $host === $local_host ) {
			return $data;
		}

		$relative = $this->uploads_relative_path( $data['url'] );
		if ( '' === $relative || empty( $by_path[ $relative ] ) ) {
			return $data;
		}

		$data['url'] = $by_path[ $relative ];

		$attachment_id = attachment_url_to_postid( $data['url'] );
		if ( $attachment_id ) {
			$data['id'] = isset( $data['id'] ) && is_string( $data['id'] ) ? (string) $attachment_id : $attachment_id;
		}

		return $data;
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
	 * Remap term and attachment IDs inside SiteOrigin Page Builder's `panels_data`.
	 *
	 * The layout is imported verbatim, so widget taxonomy fields and row/cell background
	 * attachment IDs still carry the demo site's IDs. Those usually do not exist here, and
	 * on a site with earlier content they can resolve to something unrelated - an empty
	 * portfolio section, or a row showing someone else's image.
	 *
	 * @param array $mapping Import mapping, with `post` and `term_id` keys.
	 */
	private function remap_panels_data_ids( array $mapping ): void {
		global $wpdb;

		$posts = array_map( 'intval', $mapping['post'] ?? array() );
		$terms = array_map( 'intval', $mapping['term_id'] ?? array() );

		if ( empty( $posts ) && empty( $terms ) ) {
			return;
		}

		// Only posts from this run: the imported-posts list also holds earlier imports,
		// whose layouts must not be rewritten with this demo's IDs.
		$imported_posts = array_diff(
			array_map( 'intval', (array) get_option( 'themegrill_demo_importer_imported_posts', array() ) ),
			array_map( 'intval', (array) get_option( 'themegrill_demo_importer_previous_imported_posts', array() ) )
		);

		if ( empty( $imported_posts ) ) {
			return;
		}

		$ids          = array_values( $imported_posts );
		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
		$rows         = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
			$wpdb->prepare(
				"SELECT meta_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = 'panels_data' AND post_id IN ($placeholders)", // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
				...$ids
			)
		);

		foreach ( $rows as $row ) {
			$data = maybe_unserialize( $row->meta_value );
			if ( empty( $data ) || ! is_array( $data ) ) {
				continue;
			}

			$remapped = $this->remap_panels_ids_recursive( $data, $posts, $terms );
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
	 * Walk panels_data, swapping the demo's term and attachment IDs for the imported ones.
	 *
	 * Matched on field name rather than path because the same fields recur at every depth:
	 * row styles, cell styles, widget styles, and the nested panels_data a layout widget
	 * carries. `id` is deliberately not touched - that is SiteOrigin's own cell index.
	 *
	 * @param array $data  Unserialized panels_data, or a nested part of it.
	 * @param array $posts Old attachment ID => new attachment ID.
	 * @param array $terms Old term ID => new term ID.
	 * @return array
	 */
	private function remap_panels_ids_recursive( array $data, array $posts, array $terms ): array {
		foreach ( $data as $key => $value ) {
			if ( 'background_image_attachment' === $key ) {
				$data[ $key ] = $this->swap_panels_id( $value, $posts );
				continue;
			}

			if ( 'category' === $key || 'categories' === $key ) {
				if ( is_array( $value ) ) {
					foreach ( $value as $index => $term ) {
						$value[ $index ] = $this->swap_panels_id( $term, $terms );
					}
					$data[ $key ] = $value;
				} else {
					$data[ $key ] = $this->swap_panels_id( $value, $terms );
				}
				continue;
			}

			if ( is_array( $value ) ) {
				$data[ $key ] = $this->remap_panels_ids_recursive( $value, $posts, $terms );
			}
		}

		return $data;
	}

	/**
	 * Swap a single ID through a mapping, preserving the stored value's type.
	 *
	 * `0` means "all" or "none" in these fields, and an ID this import did not create is
	 * left alone rather than guessed at. A non-numeric value (SiteOrigin also accepts a
	 * URL in the background field) is returned untouched.
	 *
	 * @param mixed $value Stored ID, as an int or a numeric string.
	 * @param array $map   Old ID => new ID.
	 * @return mixed
	 */
	private function swap_panels_id( $value, array $map ) {
		if ( ! is_scalar( $value ) || ! is_numeric( $value ) ) {
			return $value;
		}

		$old = (int) $value;

		if ( $old <= 0 || ! isset( $map[ $old ] ) ) {
			return $value;
		}

		return is_string( $value ) ? (string) $map[ $old ] : (int) $map[ $old ];
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
