<?php
/**
 * Plugin Name: Starter Templates & Sites Pack by ThemeGrill
 * Plugin URI: https://themegrill.com/demo-importer/
 * Description: Premium starter sites and website templates by ThemeGrill. Import demo content, widgets, and theme settings with one click.
 * Version: 2.0.0.3
 * Requires at least: 5.5
 * Requires PHP: 8.1.0
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

const TGDM_VERSION     = '2.0.0.3';
const TGDM_PLUGIN_FILE = __FILE__;
define( 'TGDM_ABSPATH', plugin_dir_path( __FILE__ ) );
define( 'TGDM_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
// const THEMEGRILL_BASE_URL = 'http://themegrill-demos-api.test';
const THEMEGRILL_BASE_URL = 'https://themegrilldemos.com';
const ZAKRA_BASE_URL      = 'https://zakrademos.com';
const TGDM_NAMESPACE      = '/wp-json/themegrill-demos/v1';

if ( version_compare( PHP_VERSION, '8.1.0', '<' ) ) {
	add_action(
		'admin_notices',
		function () {
			echo '<div class="notice notice-error is-dismissible">';
			echo '<p><strong>Starter Templates & Sites Pack by ThemeGrill Activation Error:</strong> This plugin requires PHP 8.1.0 or higher. Your current version is ' . PHP_VERSION . '.</p>';
			echo '<p>Please contact your hosting provider to upgrade PHP.</p>';
			echo '</div>';
		}
	);

	add_action(
		'admin_init',
		function () {
			deactivate_plugins( plugin_basename( __FILE__ ) );
			if ( isset( $_GET['activate'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended
				unset( $_GET['activate'] );
			}
		}
	);

	return;
}

require_once __DIR__ . '/vendor/autoload.php';

\ThemeGrill\Demo\Importer\App::instance();

add_filter(
	'wp_import_post_data_processed',
	function ( $post_data, $post, $term_id_map = null ) {
		if ( isset( $post_data['post_content'] ) && has_blocks( $post_data['post_content'] ) && $term_id_map ) {
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

add_action(
	'admin_menu',
	function () {
		if ( isset( $_GET['page'] ) && in_array( sanitize_key( wp_unslash( $_GET['page'] ) ), array( 'colormag-starter-templates', 'zakra-starter-templates', 'demo-importer' ), true ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$redirect_url = admin_url( 'admin.php?page=tg-starter-templates' );
			wp_safe_redirect( $redirect_url );
			exit;
		}
	},
	PHP_INT_MIN
);
