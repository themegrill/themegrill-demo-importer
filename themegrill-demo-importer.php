<?php
/**
 * Plugin Name: ThemeGrill Demo Importer
 * Plugin URI: https://themegrill.com/demo-importer/
 * Description: Import ThemeGrill official themes demo content, widgets and theme settings with just one click.
 * Version: 1.9.9
 * Author: ThemeGrill
 * Author URI: https://themegrill.com
 * License: GPLv3 or later
 * Text Domain: themegrill-demo-importer
 * Domain Path: /languages/
 *
 * @package ThemeGrill_Demo_Importer
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Define TGDM_PLUGIN_FILE.
if ( ! defined( 'TGDM_PLUGIN_FILE' ) ) {
	define( 'TGDM_PLUGIN_FILE', __FILE__ );
}

// Include the main ThemeGrill Demo Importer class.
if ( ! class_exists( 'ThemeGrill_Demo_Importer' ) ) {
	include_once __DIR__ . '/includes/class-themegrill-demo-importer.php';
}

/**
 * Main instance of ThemeGrill Demo importer.
 *
 * Returns the main instance of TGDM to prevent the need to use globals.
 *
 * @since  1.9.9
 * @return ThemeGrill_Demo_Importer
 */
function tgdm() {
	return ThemeGrill_Demo_Importer::instance();
}

// Global for backwards compatibility.
$GLOBALS['themegrill-demo-importer'] = tgdm();

add_filter(
	'wp_import_post_data_processed',
	function ( $post_data, $post, $term_id_map ) {
		if ( isset( $post_data['post_content'] ) && has_blocks( $post_data['post_content'] ) ) {
			$blocks = parse_blocks( $post_data['post_content'] );
			update_block_term_ids( $blocks, $term_id_map );
			$post_data['post_content'] = serialize_blocks( $blocks );
		}
		return $post_data;
	},
	10,
	3
);

function update_block_term_ids( array &$blocks, array $term_id_map ) {
	foreach ( $blocks as &$block ) {
		if ( isset( $block['blockName'] ) ) {
			if ( str_starts_with( $block['blockName'], 'magazine-blocks/' ) ) {
				if ( isset( $block['attrs'] ) ) {
					$key1 = array( 'category', 'category2', 'tag', 'tag2', 'authorName' );

					foreach ( $key1 as $key ) {
						if ( 'authorName' === $key && isset( $block['attrs'][ $key ] ) ) {
							$block['attrs'][ $key ] = (string) get_current_user_id();
							break;
						}
						if ( isset( $block['attrs'][ $key ] ) && isset( $term_id_map[ $block['attrs'][ $key ] ] ) ) {
							$block['attrs'][ $key ] = (string) $term_id_map[ $block['attrs'][ $key ] ];
						}
					}

					$key2 = array( 'excludedCategory', 'excludedCategory2' );

					foreach ( $key2 as $key ) {
						if ( isset( $block['attrs'][ $key ] ) && is_array( $block['attrs'][ $key ] ) ) {
							$block['attrs'][ $key ] = array_map(
								function ( $cat_id ) use ( $term_id_map ) {
									return isset( $term_id_map[ $cat_id ] ) ? (string) $term_id_map[ $cat_id ] : false;
								},
								$block['attrs'][ $key ]
							);
						}
					}
				}

				// Recursively update inner blocks
				if ( ! empty( $block['innerBlocks'] ) ) {
					update_block_term_ids( $block['innerBlocks'], $term_id_map );
				}
			}
			if ( 'core/group' === $block['blockName'] ) {
				if ( ! empty( $block['innerBlocks'] ) ) {
					foreach ( $block['innerBlocks'] as &$inner_block ) {
						if ( 'core/legacy-widget' === $inner_block['blockName'] ) {
							if ( isset( $inner_block['attrs']['idBase'] ) && 'nav_menu' === $inner_block['attrs']['idBase'] ) {
								if ( isset( $inner_block['attrs']['instance']['raw']['nav_menu'] ) ) {
									$current_menu_id = $inner_block['attrs']['instance']['raw']['nav_menu'];
									if ( isset( $term_id_map[ $current_menu_id ] ) ) {
										$new_menu_id = $term_id_map[ $current_menu_id ];
										$inner_block['attrs']['instance']['raw']['nav_menu'] = $new_menu_id;

										// Preserve existing raw data and update nav_menu
										$new_data             = $inner_block['attrs']['instance']['raw'];
										$new_data['nav_menu'] = $new_menu_id;

										// Update encoded and hash with complete data
										$inner_block['attrs']['instance']['encoded'] = base64_encode( serialize( $new_data ) );
										$inner_block['attrs']['instance']['hash']    = wp_hash( serialize( $new_data ) );

									}
								}
							}
						}
					}
				}
			}
		}
	}
}

add_action(
	'themegrill_widget_importer_after_widgets_import',
	function ( $term_id_map ) {
		$widget_blocks = get_option( 'widget_block', array() );
		if ( ! empty( $widget_blocks ) ) {
			foreach ( $widget_blocks as $index => $widget ) {
				if ( isset( $widget['content'] ) ) {
					$blocks = parse_blocks( $widget['content'] );
					update_block_term_ids( $blocks, $term_id_map );
					$widget_blocks[ $index ]['content'] = serialize_blocks( $blocks );
				}
			}
			update_option( 'widget_block', $widget_blocks );
		}
	},
	10
);
