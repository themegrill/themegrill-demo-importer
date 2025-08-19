<?php
/**
 * Customizer importer - import customizer settings.
 *
 * Code adapted from the "Customizer Export/Import" plugin.
 *
 * @package ThemeGrill_Demo_Importer\Classes
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * TG_Customizer_Importer Class.
 */
class TG_Customizer_Importer {

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
	 * @return void|WP_Error
	 */
	public static function import( $data, $demo_id, $demo_data, $term_id_map ) {
		global $wp_customize;

		// Data checks.
		if ( ! is_array( $data ) ) {
			return new WP_Error( 'themegrill_customizer_import_data_error', __( 'The customizer data is not in a correct format.', 'themegrill-demo-importer' ) );
		}

		// Import Images.
		if ( apply_filters( 'themegrill_customizer_import_images', true ) ) {
			$data = self::import_customizer_images( $data );
		}

		// Import custom options.
		if ( isset( $data['options'] ) ) {

			// Load WordPress Customize Setting Class.
			if ( ! class_exists( 'WP_Customize_Setting' ) ) {
				require_once ABSPATH . WPINC . '/class-wp-customize-setting.php';
			}

			// Include Customizer Demo Importer Setting class.
			include_once __DIR__ . '/customize/class-oc-customize-demo-importer-setting.php';

			foreach ( $data['options'] as $option_key => $option_value ) {
				$option = new OC_Customize_Demo_Importer_Setting(
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

		if ( isset( $data['nav_menu_locations'] ) && is_array( $data['nav_menu_locations'] ) ) {
			foreach ( $data['nav_menu_locations'] as $location => $menu_id ) {
				if ( isset( $term_id_map[ $menu_id ] ) ) {
					$data['nav_menu_locations'][ $location ] = $term_id_map[ $menu_id ];
				}
			}
		}

		if ( isset( $data['colormag_footer_menu'] ) && ! empty( $data['colormag_footer_menu'] ) ) {
			$footer_menu_id               = isset( $term_id_map[ $data['colormag_footer_menu'] ] ) ? (string) $term_id_map[ $data['colormag_footer_menu'] ] : $data['colormag_footer_menu'];
			$data['colormag_footer_menu'] = $footer_menu_id;
		}

		// If wp_css is set then import it.
		if ( function_exists( 'wp_update_custom_css_post' ) && isset( $data['wp_css'] ) && '' !== $data['wp_css'] ) {
			wp_update_custom_css_post( $data['wp_css'] );
		}
		// Loop through theme mods and update them.
		foreach ( $data as $key => $value ) {
			set_theme_mod( $key, $value );
		}
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

	private static function replace_image_host( $url ) {
		$parsed_url = wp_parse_url( $url );

		if ( ! $parsed_url || ! isset( $parsed_url['path'] ) ) {
			return $url;
		}

		$site_url = wp_parse_url( home_url() );
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

	/**
	 * Taken from the core media_sideload_image function and
	 * modified to return an array of data instead of html.
	 *
	 * @param  string $file The image file path.
	 * @return array An array of image data.
	 */
	private static function media_handle_sideload( $file ) {
		$data = new stdClass();

		if ( ! function_exists( 'media_handle_sideload' ) ) {
			require_once ABSPATH . 'wp-admin/includes/media.php';
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/image.php';
		}

		if ( ! empty( $file ) ) {
			// Set variables for storage, fix file filename for query strings.
			preg_match( '/[^\?]+\.(jpe?g|jpe|gif|png)\b/i', $file, $matches );
			$file_array         = array();
			$file_array['name'] = basename( $matches[0] );

			// Download file to temp location.
			$file_array['tmp_name'] = download_url( $file );

			// If error storing temporarily, return the error.
			if ( is_wp_error( $file_array['tmp_name'] ) ) {
				return $file_array['tmp_name'];
			}

			// Do the validation and storage stuff.
			$id = media_handle_sideload( $file_array, 0 );

			// If error storing permanently, unlink.
			if ( is_wp_error( $id ) ) {
				@unlink( $file_array['tmp_name'] );
				return $id;
			}

			// Build the object to return.
			$meta                = wp_get_attachment_metadata( $id );
			$data->attachment_id = $id;
			$data->url           = wp_get_attachment_url( $id );
			$data->thumbnail_url = wp_get_attachment_thumb_url( $id );
			$data->height        = $meta['height'];
			$data->width         = $meta['width'];
		}

		return $data;
	}
}
