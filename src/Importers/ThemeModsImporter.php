<?php

namespace ThemeGrill\Demo\Importer\Importers;

use ThemeGrill\Demo\Importer\CustomizeDemoImporterSetting;
use ThemeGrill\Demo\Importer\Logger;
use WP_Error;
use WP_REST_Response;

class ThemeModsImporter {
	private $logger;

	public function __construct() {
		$this->logger = Logger::getInstance();
	}

	public function import( $demo, $args = array() ) {
		if ( ! $demo['themeMods'] ) {
			return true;
		}
		$mapping_data = get_option( 'themegrill_demo_importer_mapping', array() );
		$term_id_map  = array();
		$post_id_map  = array();
		if ( ! empty( $mapping_data ) ) {
			$term_id_map = $mapping_data['term_id'] ?? array();
			$post_id_map = $mapping_data['post'] ?? array();
		}
		$this->logger->info( 'Importing theme mods...', [ 'start_time' => true ] );
		$import = $this->processImport( $demo['themeMods'], $demo['slug'], $demo, $term_id_map, $args, $post_id_map );
		if ( is_wp_error( $import ) ) {
			$this->logger->error( 'Error importing customizer: ' . $import->get_error_message(), [ 'end_time' => true ] );
			return new WP_Error( 'import_customizer_failed', 'Error importing customizer.', array( 'status' => 500 ) );
		}
		$this->logger->info( 'Theme mods imported.', [ 'end_time' => true ] );
		return new WP_REST_Response(
			array(
				'success' => true,
				'message' => 'Customizer Imported.',
			),
			200
		);
	}

	/**
	 * Imports uploaded mods and calls WordPress core customize_save actions so
	 * themes that hook into them can act before mods are saved to the database.
	 *
	 * Update: WP core customize_save actions were removed, because of some errors.
	 *
	 * @param  array $data Theme mods.
	 * @param  string $demo_id     The ID of demo being imported.
	 * @param  array  $demo_data   The data of demo being imported.
	 * @param  array  $term_id_map   Processed Terms Map
	 * @param  array  $args   Additional arguments
	 * @param  array  $post_id_map   Map of the demo's original attachment/post IDs to their newly imported IDs.
	 * @return void|WP_Error
	 */
	public static function processImport( $data, $demo_id, $demo_data, $term_id_map, $args, $post_id_map = array() ) {
		global $wp_customize;

		// Data checks.
		if ( ! is_array( $data ) ) {
			return new WP_Error( 'themegrill_customizer_import_data_error', __( 'The customizer data is not in a correct format.', 'themegrill-demo-importer' ) );
		}

		// Import Images.
		if ( apply_filters( 'themegrill_customizer_import_images', true ) ) {
			$data = self::import_customizer_images( $data );
		}

		// Rewrite any remaining links (e.g. header button links) that still point at the demo site.
		$data = self::replace_demo_site_urls( $data );

		// Import custom options.
		$options = ! empty( $demo_data['wp_options'] ) ? $demo_data['wp_options'] : array();
		foreach ( $options as $key => $value ) {
			if ( 'options' === $key ) {
				if ( ! class_exists( 'WP_Customize_Setting' ) ) {
					require_once ABSPATH . WPINC . '/class-wp-customize-setting.php';
				}
				foreach ( $options['options'] as $option_key => $option_value ) {
					$option = new CustomizeDemoImporterSetting(
						$wp_customize,
						$option_key,
						array(
							'default'    => '',
							'type'       => 'option',
							'capability' => 'edit_theme_options',
						)
					);

					$option->import( $option_value );
				}
			}
			if ( function_exists( 'wp_update_custom_css_post' ) && 'wp_css' === $key && ! empty( $options['wp_css'] ) ) {
				wp_update_custom_css_post( $options['wp_css'] );
			}
		}

		if ( ! empty( $data['nav_menu_locations'] ) && is_array( $data['nav_menu_locations'] ) ) {
			foreach ( $data['nav_menu_locations'] as $location => $menu_id ) {
				if ( isset( $term_id_map[ $menu_id ] ) ) {
					$data['nav_menu_locations'][ $location ] = $term_id_map[ $menu_id ];
				}
			}
		}

		if ( ! empty( $data['colormag_footer_menu'] ) ) {
			$footer_menu_id               = isset( $term_id_map[ $data['colormag_footer_menu'] ] ) ? (string) $term_id_map[ $data['colormag_footer_menu'] ] : $data['colormag_footer_menu'];
			$data['colormag_footer_menu'] = $footer_menu_id;
		}

		$category_color_renames = array();
		foreach ( $data as $key => $value ) {
			if ( preg_match( '/^colormag_category_color_(\d+)$/', $key, $matches ) ) {
				$old_cat_id = (int) $matches[1];
				if ( isset( $term_id_map[ $old_cat_id ] ) && $term_id_map[ $old_cat_id ] != $old_cat_id ) {
					$category_color_renames[ 'colormag_category_color_' . $term_id_map[ $old_cat_id ] ] = $value;
					unset( $data[ $key ] );
				}
			}
		}
		foreach ( $category_color_renames as $new_key => $value ) {
			$data[ $new_key ] = $value;
		}

		$mods = [];
		// Loop through theme mods and update them.
		foreach ( $data as $key => $value ) {
			$mods[ $key ] = $value;
		}

		// `custom_logo` holds the demo's own attachment post ID, not a URL, so the
		// image-URL rewriting above never touches it - it still points at the
		// original demo site's ID (meaningless, or coincidentally wrong, here)
		// unless it's remapped through the newly imported attachment's ID the same
		// way `_thumbnail_id` is remapped after media import.
		if ( ! empty( $mods['custom_logo'] ) && isset( $post_id_map[ $mods['custom_logo'] ] ) ) {
			$mods['custom_logo'] = $post_id_map[ $mods['custom_logo'] ];
		}

		if ( ! empty( $args['custom_logo'] ) ) {
				$mods['custom_logo'] = $args['custom_logo'];
		}
		if ( ! empty( $args['color_palette'] ) ) {
			$color_palette_key = $demo_data['theme_slug'] . '_color_palette';
			$colors            = array();
			$id                = 'custom-' . time();
			foreach ( $args['color_palette'] as $index => $color ) {
				$palette_key            = $demo_data['theme_slug'] . '-color-' . ( $index + 1 );
				$colors[ $palette_key ] = $color;
			}

			$new_custom = array(
				'id'     => $id,
				'name'   => 'Starter Colors',
				'colors' => $colors,
			);

			$existing_custom   = isset( $data[ $color_palette_key ]['custom'] ) ? $data[ $color_palette_key ]['custom'] : [];
			$existing_custom[] = $new_custom;

			$new_value                  = [
				'id'         => $id,
				'name'       => 'Starter Colors',
				'colors'     => $colors,
				'custom'     => $existing_custom,
				'updated_at' => time(),
			];
			$mods[ $color_palette_key ] = $new_value;
		}

		if ( ! empty( $args['typography'] ) ) {
			$typography_keys = [
				'zakra'     => [
					'body'    => 'zakra_body_typography',
					'heading' => 'zakra_heading_typography',
				],
				'colormag'  => [
					'body'    => 'colormag_base_typography',
					'heading' => 'colormag_headings_typography',
				],
				'elearning' => [
					'body'    => 'elearning_base_typography_body',
					'heading' => 'elearning_base_typography_heading',
				],
			];

			if ( ! isset( $typography_keys[ $demo_data['theme_slug'] ] ) ) {
				return;
			}

			$body_typography_key    = $typography_keys[ $demo_data['theme_slug'] ]['body'];
			$heading_typography_key = $typography_keys[ $demo_data['theme_slug'] ]['heading'];

			$body_typography_value                = isset( $data[ $body_typography_key ] ) ? $data[ $body_typography_key ] : [];
			$body_typography_value['font-family'] = 'System' === $args['typography'][1] ? 'inherit' : $args['typography'][1];
			$mods[ $body_typography_key ]         = $body_typography_value;

			$heading_typography_value                = isset( $data[ $heading_typography_key ] ) ? $data[ $heading_typography_key ] : [];
			$heading_typography_value['font-family'] = 'System' === $args['typography'][0] ? 'inherit' : $args['typography'][0];
			$mods[ $heading_typography_key ]         = $heading_typography_value;

		}

		update_option( 'themegrill_starter_template_theme_mods', $mods );

		// Kept apart so the layouts survive Zakra's post-import migration, which rebuilds them.
		$layout_keys = preg_grep( '/^zakra_(?:global|blog|single_page|single_post|others_page|woocommerce_global|woocommerce_page|single_product)_(?:sidebar|container)_layout$/', array_keys( $mods ) );
		update_option( 'themegrill_starter_template_layout_mods', array_intersect_key( $mods, array_flip( $layout_keys ) ), false );
	}

	/**
	 * Imports images for settings saved as mods.
	 *
	 * @param  array $mods An array of customizer mods.
	 * @return array The mods array with any new import data.
	 */
	private static function import_customizer_images( $mods ) {
		foreach ( $mods as $key => $value ) {
			if ( self::is_image_url( $value ) ) {
				$new_url      = self::replace_image_host( $value );
				$mods[ $key ] = $new_url;
			} elseif ( is_array( $value ) ) {
				$mods[ $key ] = self::process_array_images( $value );
			}
		}

		return $mods;
	}

	/**
	 * Rewrites any theme mod value (recursively through arrays) that is an absolute URL
	 * pointing at demo site, e.g. a header button link left over.
	 *
	 * @param  mixed $data Theme mod value or array of values.
	 * @return mixed
	 */
	private static function replace_demo_site_urls( $data ) {
		foreach ( $data as $key => $value ) {
			if ( is_string( $value ) ) {
				$data[ $key ] = self::replace_demo_link( $value );
			} elseif ( is_array( $value ) ) {
				$data[ $key ] = self::replace_demo_site_urls( $value );
			}
		}

		return $data;
	}

	/**
	 * Rewrites a theme mod URL not on this site's own host, stripping the assumed demo host + subdirectory slug.
	 *
	 * @param  string $value Theme mod value.
	 * @return string
	 */
	private static function replace_demo_link( $value ) {
		if ( '' === $value ) {
			return $value;
		}

		$parsed = wp_parse_url( $value );
		if ( empty( $parsed['host'] ) ) {
			return $value;
		}

		$host      = preg_replace( '/^www\./i', '', strtolower( $parsed['host'] ) );
		$site_host = preg_replace( '/^www\./i', '', strtolower( wp_parse_url( home_url(), PHP_URL_HOST ) ) );

		if ( $host === $site_host ) {
			return $value;
		}

		$path = isset( $parsed['path'] ) ? preg_replace( '/^\/[^\/]+/', '', $parsed['path'] ) : '';

		$new_value = untrailingslashit( home_url() ) . $path;

		if ( ! empty( $parsed['query'] ) ) {
			$new_value .= '?' . $parsed['query'];
		}
		if ( ! empty( $parsed['fragment'] ) ) {
			$new_value .= '#' . $parsed['fragment'];
		}

		return $new_value;
	}

	/**
	 * Finds the attachment this import already created for a demo image URL.
	 *
	 * Imported attachments keep the demo's original URL as their guid, so a theme mod
	 * pointing at the same file (e.g. the transparent header logo) can reuse it. The
	 * complete URL, host and demo directory included, has to match; a path suffix alone
	 * could pick a same-named file from another demo. Only the scheme is allowed to differ.
	 *
	 * @param  string $url Demo image URL.
	 * @return string Local attachment URL, or an empty string if none was imported.
	 */
	private static function find_imported_attachment_url( $url ) {
		global $wpdb;

		$imported = array_map( 'intval', (array) get_option( 'themegrill_demo_importer_imported_posts', array() ) );
		if ( empty( $imported ) || ! preg_match( '#^https?://(.+)$#i', $url, $matches ) ) {
			return '';
		}

		$placeholders = implode( ',', array_fill( 0, count( $imported ), '%d' ) );
		$attachment   = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
			$wpdb->prepare(
				"SELECT ID FROM {$wpdb->posts} WHERE post_type = 'attachment' AND guid IN (%s, %s) AND ID IN ($placeholders) ORDER BY ID DESC LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
				'https://' . $matches[1],
				'http://' . $matches[1],
				...$imported
			)
		);

		return $attachment ? (string) wp_get_attachment_url( $attachment ) : '';
	}

	/**
	 * Resolves a demo image URL from a theme mod to a file that exists on this site.
	 *
	 * Reuses the attachment imported with the demo when there is one, otherwise
	 * side-loads the file into the Media Library. The old string-only rewrite is kept
	 * as the last resort, which only works if a same-named upload happens to exist.
	 *
	 * @param  string $url Demo image URL.
	 * @return string
	 */
	private static function replace_image_host( $url ) {
		$parsed_url = wp_parse_url( $url );

		if ( ! $parsed_url || ! isset( $parsed_url['path'] ) ) {
			return $url;
		}

		$site_url = wp_parse_url( home_url() );

		if ( ! empty( $parsed_url['host'] ) && $parsed_url['host'] !== $site_url['host'] ) {
			$imported_url = self::find_imported_attachment_url( $url );
			if ( '' !== $imported_url ) {
				return $imported_url;
			}

			$sideloaded_url = WidgetsImporter::sideload_widget_image( $url );
			if ( $sideloaded_url !== $url ) {
				return $sideloaded_url;
			}
		}

		$path     = $parsed_url['path'];
		$path     = preg_replace( '/\/sites\/\d+/', '', $path );
		$path     = preg_replace( '/^\/[^\/]+/', '', $path );
		$new_url  = $site_url['scheme'] . '://' . $site_url['host'];
		$new_url .= $path;
		return $new_url;
	}

	private static function process_array_images( $data ) {
		foreach ( $data as $key => $value ) {
			if ( is_string( $value ) && self::is_image_url( $value ) ) {
				$data[ $key ] = self::replace_image_host( $value );
			} elseif ( is_array( $value ) ) {
				$data[ $key ] = self::process_array_images( $value );
			}
		}
		return $data;
	}

	/**
	 * Checks to see whether a url is an image url or not.
	 *
	 * @param  string $url The url to check.
	 * @return bool Whether the url is an image url or not.
	 */
	private static function is_image_url( $url ) {
		if ( is_string( $url ) && preg_match( '/\.(jpg|jpeg|png|gif)/i', $url ) ) {
			return true;
		}

		return false;
	}
}
