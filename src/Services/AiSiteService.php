<?php
/**
 * Build with AI site service.
 *
 * @package ThemeGrill\Demo\Importer
 */

namespace ThemeGrill\Demo\Importer\Services;

use ThemeGrill\Demo\Importer\Helpers\RemoteRequest;
use ThemeGrill\Demo\Importer\Importers\MediaImporter;
use ThemeGrill\Demo\Importer\Importers\PluginImporter;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Prepares the theme for an AI-built site and applies the generated copy,
 * images, colors and fonts to the demo pages the importer created.
 */
class AiSiteService {

	/**
	 * Theme every AI demo is built on.
	 */
	const THEME = 'zakra';

	/**
	 * Stock photo hosts whose images are copied into the Media Library.
	 */
	const STOCK_HOSTS = array( 'pixabay.com', 'cdn.pixabay.com' );

	/**
	 * BlockArt attribute holding a block's background photo.
	 */
	const BACKGROUND_ATTR = 'background.image.image.url';

	/**
	 * Stock image URL => local URL, filled before slots are applied.
	 *
	 * @var array
	 */
	private $stock_images = array();

	/**
	 * Install the theme from WordPress.org if missing, then activate it.
	 *
	 * @since 2.2.0
	 *
	 * @return array|WP_Error Theme slug and whether it was installed.
	 */
	public function prepare_theme() {
		$installed = false;

		if ( ! wp_get_theme( self::THEME )->exists() ) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
			require_once ABSPATH . 'wp-admin/includes/theme.php';
			require_once ABSPATH . 'wp-admin/includes/file.php';

			$api = themes_api(
				'theme_information',
				array(
					'slug'   => self::THEME,
					'fields' => array( 'sections' => false ),
				)
			);
			if ( is_wp_error( $api ) ) {
				return $api;
			}

			add_filter( 'filesystem_method', array( PluginImporter::class, 'force_direct_filesystem_method' ) );
			try {
				$result = ( new \Theme_Upgrader( new \WP_Ajax_Upgrader_Skin() ) )->install( $api->download_link );
			} finally {
				remove_filter( 'filesystem_method', array( PluginImporter::class, 'force_direct_filesystem_method' ) );
			}

			if ( is_wp_error( $result ) ) {
				return $result;
			}
			if ( ! $result ) {
				return new WP_Error( 'theme_install_failed', __( 'The Zakra theme could not be installed. Install it from Appearance > Themes and try again.', 'themegrill-demo-importer' ) );
			}
			$installed = true;
		}

		// A Zakra child theme counts as active.
		if ( self::THEME !== get_template() ) {
			switch_theme( self::THEME );
		}

		return array(
			'theme'     => self::THEME,
			'installed' => $installed,
		);
	}

	/**
	 * Apply the generated site to the imported demo pages.
	 *
	 * Must run before the importer's `complete` step, which deletes the demo ID
	 * and media URL maps this relies on.
	 *
	 * @since 2.2.0
	 *
	 * @param array $payload Sanitized payload: siteTitle, tagline, colorMap, fontMap, pages (slots, removeBlocks).
	 * @return array Number of pages updated.
	 */
	public function apply( array $payload ) {
		$mapping   = get_option( 'themegrill_demo_importer_mapping', array() );
		$post_map  = isset( $mapping['post'] ) ? (array) $mapping['post'] : array();
		$url_remap = (array) get_option( 'themegrill_demo_importer_url_remap', array() );

		$slots_by_post  = array();
		$remove_by_post = array();
		foreach ( $payload['pages'] as $page ) {
			$post_id = $this->find_page( $page, $post_map );
			if ( $post_id ) {
				$slots_by_post[ $post_id ]  = $page['slots'];
				$remove_by_post[ $post_id ] = $page['removeBlocks'];
			}
		}

		$this->import_stock_images( wp_list_pluck( array_merge( array(), ...array_values( $slots_by_post ) ), 'image' ) );

		// Copy goes on the chosen pages; colors and fonts on every imported page.
		$post_ids = array_unique( array_merge( array_keys( $slots_by_post ), $this->imported_pages( $post_map ) ) );
		$updated  = 0;

		foreach ( $post_ids as $post_id ) {
			$post = get_post( $post_id );
			if ( ! $post ) {
				continue;
			}

			$blocks = parse_blocks( $post->post_content );
			foreach ( isset( $slots_by_post[ $post_id ] ) ? $slots_by_post[ $post_id ] : array() as $slot ) {
				$blocks = $this->apply_slot( $blocks, $slot, $url_remap );
			}
			// Slot paths index the original page, so sections are removed only after every slot is applied.
			if ( ! empty( $remove_by_post[ $post_id ] ) ) {
				$blocks = $this->remove_top_blocks( $blocks, $remove_by_post[ $post_id ] );
			}
			$blocks  = $this->rebrand_blocks( $blocks, $payload['colorMap'], $payload['fontMap'] );
			$content = serialize_blocks( $blocks );

			if ( $content !== $post->post_content ) {
				// Saving also clears BlockArt's cached CSS, so styles rebuild from the new attributes.
				wp_update_post(
					wp_slash(
						array(
							'ID'           => $post_id,
							'post_content' => $content,
						)
					)
				);
				++$updated;
			}
		}

		if ( '' !== $payload['siteTitle'] ) {
			update_option( 'blogname', $payload['siteTitle'] );
		}
		update_option( 'blogdescription', $payload['tagline'] );

		return array( 'updated' => $updated );
	}

	/**
	 * Imported post ID for a demo page.
	 *
	 * @param array $page     Page payload with demoPageId and demoPageSlug.
	 * @param array $post_map Demo post ID => imported post ID.
	 * @return int Post ID, 0 when not found.
	 */
	private function find_page( array $page, array $post_map ) {
		if ( $page['demoPageId'] && isset( $post_map[ $page['demoPageId'] ] ) ) {
			return absint( $post_map[ $page['demoPageId'] ] );
		}

		$found = $page['demoPageSlug'] ? get_page_by_path( $page['demoPageSlug'] ) : null;

		return $found ? $found->ID : 0;
	}

	/**
	 * Imported pages and reusable blocks from the importer's ID map.
	 *
	 * @param array $post_map Demo post ID => imported post ID.
	 * @return int[]
	 */
	private function imported_pages( array $post_map ) {
		return array_values(
			array_filter(
				array_map( 'absint', $post_map ),
				function ( $post_id ) {
					return in_array( get_post_type( $post_id ), array( 'page', 'wp_block' ), true );
				}
			)
		);
	}

	/**
	 * Apply one slot value to the block at its path.
	 *
	 * @param array $blocks    Parsed blocks.
	 * @param array $slot      Slot payload: path, attr, inHtml, and text or image.
	 * @param array $url_remap Demo media URL => imported media URL.
	 * @return array
	 */
	private function apply_slot( array $blocks, array $slot, array $url_remap ) {
		$indexes = array_map( 'intval', explode( '.', $slot['path'] ) );

		return $this->update_at(
			$blocks,
			$indexes,
			function ( $block ) use ( $slot, $url_remap ) {
				if ( null === $slot['image'] ) {
					return $this->set_text( $block, $slot['attr'], $slot['text'], $slot['inHtml'] );
				}
				$block = $this->set_image( $block, $slot['attr'], $slot['image'], $url_remap );

				return '' !== $slot['overlay'] ? $this->add_overlay( $block, $slot['attr'], $slot['overlay'] ) : $block;
			}
		);
	}

	/**
	 * Array keys of the named blocks in a list, skipping freeform whitespace.
	 *
	 * @param array $blocks Parsed blocks.
	 * @return array
	 */
	private function named_keys( array $blocks ) {
		$keys = array();
		foreach ( $blocks as $key => $block ) {
			if ( ! empty( $block['blockName'] ) ) {
				$keys[] = $key;
			}
		}

		return $keys;
	}

	/**
	 * Remove top-level blocks by their index among named blocks.
	 *
	 * @param array $blocks  Parsed top-level blocks.
	 * @param int[] $indexes Named-block indexes to remove.
	 * @return array
	 */
	private function remove_top_blocks( array $blocks, array $indexes ) {
		$named = $this->named_keys( $blocks );

		foreach ( $indexes as $index ) {
			if ( isset( $named[ $index ] ) ) {
				unset( $blocks[ $named[ $index ] ] );
			}
		}

		return array_values( $blocks );
	}

	/**
	 * Update the block at a path of named-block indexes.
	 *
	 * @param array    $blocks  Blocks at this level.
	 * @param int[]    $indexes Remaining path, counting named blocks only.
	 * @param callable $update  Receives and returns the target block.
	 * @return array
	 */
	private function update_at( array $blocks, array $indexes, callable $update ) {
		$index = array_shift( $indexes );
		$named = $this->named_keys( $blocks );

		if ( ! isset( $named[ $index ] ) ) {
			return $blocks;
		}

		$key = $named[ $index ];
		if ( $indexes ) {
			$blocks[ $key ]['innerBlocks'] = $this->update_at( $blocks[ $key ]['innerBlocks'], $indexes, $update );
		} else {
			$blocks[ $key ] = $update( $blocks[ $key ] );
		}

		return $blocks;
	}

	/**
	 * Set a block's text in its attribute and, where duplicated, its saved HTML.
	 *
	 * @param array  $block   Block.
	 * @param string $attr    Attribute path, or "@html:<element>" for core blocks.
	 * @param string $text    New plain text.
	 * @param bool   $in_html Whether the text is also in the saved HTML.
	 * @return array
	 */
	private function set_text( array $block, $attr, $text, $in_html ) {
		$escaped = esc_html( $text );

		if ( 0 === strpos( $attr, '@html:' ) ) {
			$element = substr( $attr, 6 );
			$tag     = 'h' === $element ? 'h[1-6]' : preg_quote( $element, '/' );

			return $this->replace_in_html(
				$block,
				'/(<(' . $tag . ')\b[^>]*>)(.*?)(<\/\2>)/s',
				function ( $matches ) use ( $escaped ) {
					return $matches[1] . $escaped . $matches[4];
				}
			);
		}

		$old            = $this->get_path( $block['attrs'], $attr );
		$block['attrs'] = $this->set_path( $block['attrs'], $attr, $escaped );

		if ( $in_html && is_string( $old ) && '' !== $old ) {
			$block = $this->replace_in_html(
				$block,
				'/' . preg_quote( $old, '/' ) . '/',
				function () use ( $escaped ) {
					return $escaped;
				}
			);
		}

		return $block;
	}

	/**
	 * Layer a gradient over a section's new background photo, above any overlay the demo
	 * already has, so text designed for the demo photo stays readable.
	 *
	 * @param array  $block    Block.
	 * @param string $attr     Image attribute path.
	 * @param string $gradient Validated CSS linear-gradient().
	 * @return array
	 */
	private function add_overlay( array $block, $attr, $gradient ) {
		if ( 'blockart/section' !== $block['blockName'] || self::BACKGROUND_ATTR !== $attr ) {
			return $block;
		}

		$has_overlay = ! empty( $block['attrs']['overlay'] );
		$existing    = $has_overlay ? (array) ( $block['attrs']['overlayBackground'] ?? array() ) : array();
		$type        = $existing['type'] ?? 'color';
		if ( 'image' === $type ) {
			return $block;
		}

		$layers = array( $gradient );
		if ( 'gradient' === $type && ! empty( $existing['gradient'] ) ) {
			$layers[] = $existing['gradient'];
		} elseif ( ! empty( $existing['color'] ) ) {
			$layers[] = 'linear-gradient(' . $existing['color'] . ', ' . $existing['color'] . ')';
		}

		$block['attrs']['overlay']           = true;
		$block['attrs']['overlayBackground'] = array_merge(
			$existing,
			array(
				'background' => 1,
				'type'       => 'gradient',
				'gradient'   => implode( ', ', $layers ),
			)
		);

		// The saved markup carries an overlay element only when the demo had an overlay.
		if ( ! $has_overlay ) {
			$block = $this->replace_in_html(
				$block,
				'/^(\s*<div\b[^>]*>)/',
				function ( $matches ) {
					return $matches[1] . '<div class="blockart-overlay"></div>';
				}
			);
		}

		return $block;
	}

	/**
	 * Point an image slot at a new URL, preferring the imported local copy.
	 *
	 * @param array  $block     Block.
	 * @param string $attr      Attribute path, or "@html:img" for core images.
	 * @param string $url       Requested image URL.
	 * @param array  $url_remap Demo media URL => imported media URL.
	 * @return array
	 */
	private function set_image( array $block, $attr, $url, array $url_remap ) {
		if ( isset( $url_remap[ $url ] ) ) {
			$url = $url_remap[ $url ];
		} elseif ( isset( $this->stock_images[ $url ] ) ) {
			$url = $this->stock_images[ $url ];
		}

		if ( '@html:img' === $attr ) {
			return $this->replace_in_html(
				$block,
				'/(<img\b[^>]*\bsrc=")([^"]*)(")/',
				function ( $matches ) use ( $url ) {
					return $matches[1] . esc_url( $url ) . $matches[3];
				}
			);
		}

		$old_url = $this->get_path( $block['attrs'], $attr );
		if ( $old_url === $url ) {
			return $block;
		}

		$block['attrs'] = $this->set_path( $block['attrs'], $attr, esc_url_raw( $url ) );
		$attachment_id  = attachment_url_to_postid( $url );

		// Keep BlockArt's attachment ID in step with the URL.
		$id_attr = preg_replace( '/\.url$/', '.id', $attr );
		if ( $id_attr !== $attr && null !== $this->get_path( $block['attrs'], $id_attr ) ) {
			$block['attrs'] = $this->set_path( $block['attrs'], $id_attr, $attachment_id );
		}

		// Static blocks (blockart/image) also bake the URL into their saved <img>, which is what the front end renders.
		if ( is_string( $old_url ) && '' !== $old_url && false !== strpos( $block['innerHTML'], $old_url ) ) {
			$block = $this->replace_in_html(
				$block,
				'/<img\b[^>]*\bsrc="' . preg_quote( $old_url, '/' ) . '"[^>]*>/',
				function ( $matches ) use ( $old_url, $url, $attachment_id ) {
					$img = str_replace( $old_url, esc_url( $url ), $matches[0] );
					return $attachment_id ? preg_replace( '/\bwp-image-\d+\b/', 'wp-image-' . $attachment_id, $img ) : $img;
				}
			);
		}

		return $block;
	}

	/**
	 * Copy stock photos into the Media Library in parallel, since Pixabay doesn't
	 * allow hotlinking. Photos that fail to download keep their remote URL.
	 *
	 * @param array $urls Slot image URLs; non-stock URLs are ignored.
	 */
	private function import_stock_images( array $urls ) {
		$targets = array();

		foreach ( array_unique( array_filter( $urls ) ) as $url ) {
			if ( isset( $this->stock_images[ $url ] ) || ! in_array( wp_parse_url( $url, PHP_URL_HOST ), self::STOCK_HOSTS, true ) ) {
				continue;
			}

			// Reuse a copy from an earlier import of the same photo.
			$existing = get_posts(
				array(
					'post_type'      => 'attachment',
					'post_status'    => 'inherit',
					'posts_per_page' => 1,
					'fields'         => 'ids',
					'meta_key'       => '_tdi_source_url', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
					'meta_value'     => $url, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				)
			);
			if ( $existing ) {
				$this->stock_images[ $url ] = wp_get_attachment_url( $existing[0] );
				continue;
			}

			$targets[ $url ] = array(
				'url'  => $url,
				'file' => wp_tempnam( wp_basename( (string) wp_parse_url( $url, PHP_URL_PATH ) ) ),
			);
		}

		if ( empty( $targets ) ) {
			return;
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$attachment_ids = array();
		add_filter( 'intermediate_image_sizes_advanced', array( MediaImporter::class, 'limit_import_sizes' ) );
		try {
			foreach ( RemoteRequest::download_many( $targets ) as $url => $downloaded ) {
				if ( is_wp_error( $downloaded ) ) {
					continue;
				}

				$file          = array(
					'name'     => wp_basename( (string) wp_parse_url( $url, PHP_URL_PATH ) ),
					'tmp_name' => $targets[ $url ]['file'],
				);
				$attachment_id = media_handle_sideload( $file, 0 );
				if ( is_wp_error( $attachment_id ) ) {
					wp_delete_file( $file['tmp_name'] );
					continue;
				}

				update_post_meta( $attachment_id, '_tdi_source_url', $url );
				$this->stock_images[ $url ] = wp_get_attachment_url( $attachment_id );
				$attachment_ids[]           = $attachment_id;
			}
		} finally {
			remove_filter( 'intermediate_image_sizes_advanced', array( MediaImporter::class, 'limit_import_sizes' ) );
		}

		MediaImporter::queue_subsizes( $attachment_ids );
	}

	/**
	 * Replace the first regex match in a block's saved HTML and content chunks.
	 *
	 * @param array    $block    Block.
	 * @param string   $pattern  Regex.
	 * @param callable $callback preg_replace_callback callback.
	 * @return array
	 */
	private function replace_in_html( array $block, $pattern, callable $callback ) {
		$block['innerHTML'] = preg_replace_callback( $pattern, $callback, $block['innerHTML'], 1 );

		foreach ( $block['innerContent'] as $i => $chunk ) {
			if ( is_string( $chunk ) && preg_match( $pattern, $chunk ) ) {
				$block['innerContent'][ $i ] = preg_replace_callback( $pattern, $callback, $chunk, 1 );
				break;
			}
		}

		return $block;
	}

	/**
	 * Swap demo colors and fonts for the brand's throughout a block tree.
	 *
	 * @param array $blocks    Blocks.
	 * @param array $color_map Demo hex => brand hex.
	 * @param array $font_map  Demo font family => brand font family.
	 * @return array
	 */
	private function rebrand_blocks( array $blocks, array $color_map, array $font_map ) {
		foreach ( $blocks as $i => $block ) {
			if ( empty( $block['blockName'] ) ) {
				continue;
			}
			$blocks[ $i ]['attrs']       = $this->rebrand_value( $block['attrs'], '', $color_map, $font_map );
			$blocks[ $i ]['innerBlocks'] = $this->rebrand_blocks( $block['innerBlocks'], $color_map, $font_map );
		}

		return $blocks;
	}

	/**
	 * Map one attribute value (recursively) to the brand colors and fonts.
	 *
	 * @param mixed  $value     Attribute value.
	 * @param string $key       Attribute key.
	 * @param array  $color_map Demo hex => brand hex.
	 * @param array  $font_map  Demo font family => brand font family.
	 * @return mixed
	 */
	private function rebrand_value( $value, $key, array $color_map, array $font_map ) {
		if ( is_array( $value ) ) {
			foreach ( $value as $k => $v ) {
				$value[ $k ] = $this->rebrand_value( $v, (string) $k, $color_map, $font_map );
			}
			return $value;
		}

		if ( ! is_string( $value ) ) {
			return $value;
		}

		if ( 'family' === $key && isset( $font_map[ $value ] ) ) {
			return $font_map[ $value ];
		}

		// Keep any alpha suffix (#rrggbbaa) on mapped colors.
		if ( preg_match( '/^#([0-9a-f]{6})([0-9a-f]{2})?$/i', $value, $match ) ) {
			$hex = '#' . strtolower( $match[1] );
			if ( isset( $color_map[ $hex ] ) ) {
				return $color_map[ $hex ] . ( isset( $match[2] ) ? $match[2] : '' );
			}
		}

		return $value;
	}

	/**
	 * Read a dot-path from an array.
	 *
	 * @param array  $data Data.
	 * @param string $path Dot path.
	 * @return mixed|null
	 */
	private function get_path( array $data, $path ) {
		foreach ( explode( '.', $path ) as $key ) {
			if ( ! is_array( $data ) || ! array_key_exists( $key, $data ) ) {
				return null;
			}
			$data = $data[ $key ];
		}

		return $data;
	}

	/**
	 * Write a dot-path into an array.
	 *
	 * @param array  $data  Data.
	 * @param string $path  Dot path.
	 * @param mixed  $value Value.
	 * @return array
	 */
	private function set_path( array $data, $path, $value ) {
		$keys = explode( '.', $path );
		$key  = array_shift( $keys );

		$data[ $key ] = $keys
			? $this->set_path( isset( $data[ $key ] ) && is_array( $data[ $key ] ) ? $data[ $key ] : array(), implode( '.', $keys ), $value )
			: $value;

		return $data;
	}
}
