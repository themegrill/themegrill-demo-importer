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

			// Demo content can bake a specific registered image size's URL directly
			// into post_content (e.g. a Gutenberg image block with a "large"
			// sizeSlug) instead of resolving it dynamically at render time. As long
			// as this site registers the same image sizes the demo was built with,
			// WordPress names each generated size file identically
			// (`{basename}-{width}x{height}.{ext}`), so the demo's original size URL
			// can be reconstructed from the old directory plus the same naming
			// convention, and remapped the same way as the full-size image.
			//
			// The reconstruction must use the ORIGINAL basename (from $original_url),
			// not the new file's own basename: when the upload directory already has
			// a same-named file (e.g. a prior import left one behind), wp_upload_bits()
			// renames it - "hero.jpg" becomes "hero-1.jpg" - and every generated size
			// filename is then built from that renamed basename ("hero-1-1024x768.jpg").
			// The demo's content still references the un-renamed "hero-1024x768.jpg",
			// so using the new file's basename here would produce a remap key that
			// never matches anything, leaving those sizes hotlinked.
			$new_metadata = wp_get_attachment_metadata( $new_post_id );
			if ( ! empty( $new_metadata['sizes'] ) ) {
				$new_dir = trailingslashit( dirname( $new_url ) );

				foreach ( $source_urls as $source_url ) {
					$original_dir      = trailingslashit( dirname( $source_url ) );
					$original_basename = pathinfo( wp_basename( $source_url ), PATHINFO_FILENAME );

					foreach ( $new_metadata['sizes'] as $size_data ) {
						if ( empty( $size_data['file'] ) || empty( $size_data['width'] ) || empty( $size_data['height'] ) ) {
							continue;
						}

						$size_ext           = pathinfo( $size_data['file'], PATHINFO_EXTENSION );
						$original_size_file = $original_basename . '-' . $size_data['width'] . 'x' . $size_data['height'] . '.' . $size_ext;

						$url_remap[ $original_dir . $original_size_file ] = $new_dir . $size_data['file'];
					}
				}
			}

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

		// Safe remote fetch: blocks private/loopback SSRF targets.
		$response = \ThemeGrill\Demo\Importer\Helpers\RemoteRequest::get(
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
			$sorted_url_remap = $url_remap;
			uksort( $sorted_url_remap, fn( $a, $b ) => strlen( $b ) - strlen( $a ) );
			foreach ( $sorted_url_remap as $old_url => $new_url ) {
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

				// SiteOrigin widgets can be embedded directly in post_content via a
				// `[siteorigin_widget]` shortcode carrying a JSON-encoded "instance"
				// attribute. json_encode() escapes forward slashes by default, so the
				// same URL appears there as "https:\/\/..." and the plain-URL REPLACE
				// above never matches it - replace that escaped form too.
				$old_url_escaped = str_replace( '/', '\/', $old_url );
				$new_url_escaped = str_replace( '/', '\/', $new_url );

				$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
					$wpdb->prepare(
						"UPDATE {$wpdb->posts} SET post_content = REPLACE(post_content, %s, %s)",
						$old_url_escaped,
						$new_url_escaped
					)
				);
				$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
					$wpdb->prepare(
						"UPDATE {$wpdb->postmeta} SET meta_value = REPLACE(meta_value, %s, %s) WHERE meta_key = '_elementor_data'",
						$old_url_escaped,
						$new_url_escaped
					)
				);
			}

			$this->remap_panels_data_urls( $url_remap );
			$this->remap_foreign_elementor_media( $url_remap );
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
