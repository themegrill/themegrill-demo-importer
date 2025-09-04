<?php

namespace ThemeGrill\Demo\Importer\Importers;

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
			return;
		}
		$mapping_data = get_option( 'themegrill_demo_importer_mapping', array() );
		$term_id_map  = array();
		if ( ! empty( $mapping_data ) ) {
			$term_id_map = $mapping_data['term_id'] ?? array();
		}
		$import = $this->processImport( $demo['themeMods'], $demo['slug'], $demo, $term_id_map, $args );
		if ( is_wp_error( $import ) ) {
			$this->logger->error( 'Error importing customizer: ' . $import->get_error_message() );
			return new WP_Error( 'import_customizer_failed', 'Error importing customizer.', array( 'status' => 500 ) );
		}

		if ( ! empty( $args ) ) {
			if ( $args['blogname'] ) {
				update_option( 'blogname', $args['blogname'] );
			}

			if ( $args['blogdescription'] ) {
				update_option( 'blogdescription', $args['blogdescription'] );
			}

			if ( $args['custom_logo'] ) {
				$theme_mods = get_theme_mods();
				$post_id    = $theme_mods['custom_logo'] ?? null;

				if ( $post_id ) {
					set_theme_mod( 'custom_logo', $args['custom_logo'] );
				}
			}
		}

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
	 * @return void|WP_Error
	 */
	public static function processImport( $data, $demo_id, $demo_data, $term_id_map, $args ) {
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
		// if ( isset( $data['options'] ) ) {

		//  // Load WordPress Customize Setting Class.
		//  if ( ! class_exists( 'WP_Customize_Setting' ) ) {
		//      require_once ABSPATH . WPINC . '/class-wp-customize-setting.php';
		//  }

			// Include Customizer Demo Importer Setting class.
			// include_once __DIR__ . '/customize/class-oc-customize-demo-importer-setting.php';

			// foreach ( $data['options'] as $option_key => $option_value ) {
			//  $option = new OC_Customize_Demo_Importer_Setting(
			//      $wp_customize,
			//      $option_key,
			//      array(
			//          'default'    => '',
			//          'type'       => 'option',
			//          'capability' => 'edit_theme_options',
			//      )
			//  );

			//  $option->import( $option_value );
			// }
		// }

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
			if ( $demo_data['theme_slug'] . '_color_palette' === $key && ! empty( $args['color_palette'] ) ) {
				$colors = array();
				$id     = 'custom-' . time();
				foreach ( $args['color_palette'] as $index => $color ) {
					$palette_key            = $demo_data['theme_slug'] . '-color-' . ( $index + 1 );
					$colors[ $palette_key ] = $color;
				}

				$new_custom = array(
					'colors' => $colors,
					'id'     => $id,
				);

				$value['custom'][] = $new_custom;

				$new_value = [
					'id'     => $id,
					'name'   => 'Importer Color Palette',
					'colors' => $colors,
					'custom' => $value['custom'],
				];
				set_theme_mod( $key, $new_value );
			} elseif ( ! empty( $args['typography'] ) ) {
				$typography_keys = [
					'body'    => [ 'zakra_body_typography', 'colormag_base_typography', 'elearning_base_typography_body' ],
					'heading' => [ 'zakra_heading_typography', 'colormag_headings_typography', 'elearning_base_typography_heading' ],
				];

				if ( in_array( $key, $typography_keys['body'], true ) ) {
					$value['font-family'] = $args['typography'][0];
				}
				if ( in_array( $key, $typography_keys['heading'], true ) ) {
					$value['font-family'] = $args['typography'][1];
				}
				set_theme_mod( $key, $value );
			} else {
				set_theme_mod( $key, $value );
			}
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
}
