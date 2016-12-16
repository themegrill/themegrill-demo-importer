<?php
/**
 * Demo Importer Functions.
 *
 * @author   ThemeGrill
 * @category Admin
 * @package  Importer/Functions
 * @version  1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'tg_get_demo_file_url' ) ) {

	/**
	 * Get a demo file URL.
	 *
	 * @param  string $demo_dir demo dir.
	 * @return string the demo data file URL.
	 */
	function tg_get_demo_file_url( $demo_dir ) {
		return apply_filters( 'themegrill_demo_file_url', get_template_directory_uri() . '/inc/demo-data/' . $demo_dir, $demo_dir );
	}
}

if ( ! function_exists( 'tg_get_demo_file_path' ) ) {

	/**
	 * Get a demo file path.
	 *
	 * @param  string $demo_dir demo dir.
	 * @return string the demo data file path.
	 */
	function tg_get_demo_file_path( $demo_dir ) {
		return apply_filters( 'themegrill_demo_file_path', get_template_directory() . '/inc/demo-data/' . $demo_dir . '/dummy-data', $demo_dir );
	}
}

if ( ! function_exists( 'tg_get_demo_importer_assets_path' ) ) {

	/**
	 * Get a demo importer assets path.
	 *
	 * @return string the demo data assets path.
	 */
	function tg_get_demo_importer_assets_path() {
		return apply_filters( 'themegrill_demo_importer_assets_path', get_template_directory_uri() . '/inc/demo-importer/assets/' );
	}
}

/**
 * Get an attachment ID from the filename.
 *
 * @param  string $filename
 * @return int Attachment ID on success, 0 on failure
 */
function tg_get_attachment_id( $filename ) {
	$attachment_id = 0;

	$file = basename( $filename );

	$query_args = array(
		'post_type'   => 'attachment',
		'post_status' => 'inherit',
		'fields'      => 'ids',
		'meta_query'  => array(
			array(
				'value'   => $file,
				'compare' => 'LIKE',
				'key'     => '_wp_attachment_metadata',
			),
		)
	);

	$query = new WP_Query( $query_args );

	if ( $query->have_posts() ) {

		foreach ( $query->posts as $post_id ) {

			$meta = wp_get_attachment_metadata( $post_id );

			$original_file       = basename( $meta['file'] );
			$cropped_image_files = wp_list_pluck( $meta['sizes'], 'file' );

			if ( $original_file === $file || in_array( $file, $cropped_image_files ) ) {
				$attachment_id = $post_id;
				break;
			}

		}

	}

	return $attachment_id;
}

/**
 * Retrieve the links for each plugins list.
 *
 * @param  array $plugins_list
 * @return mixed
 */
function tg_get_plugins_links( $plugins_list ) {
	$plugins_link = array();

	foreach ( $plugins_list as $plugin_slug => $plugin_data ) {
		if ( isset( $plugin_data['link'] ) ) {
			$plugin_url = $plugin_data['link'];
		} else {
			$plugin_url = admin_url( 'plugin-install.php?tab=search&type=term&s=' . $plugin_slug );
		}

		$plugins_link[ $plugin_slug ] = '<a href="' . esc_url( $plugin_url ) . '" target="_blank">' . esc_html( $plugin_data['name'] ) . '</a>';
	}

	return implode( ', ', $plugins_link );
}

/**
 * Checks whether the required plugins are active.
 *
 * @param  array $raw_plugins_list Plugins list to check.
 * @return bool
 */
function tg_is_plugins_active( $raw_plugins_list ) {
	$plugins_data = array();
	$plugins_list = wp_list_pluck( $raw_plugins_list, 'slug' );

	foreach ( $plugins_list as $plugin_name => $plugin_slug ) {
		if ( is_plugin_active( $plugin_slug ) ) {
			$plugins_data[ $plugin_name ] = $plugin_slug;
		}
	}

	return array_diff( $plugins_list, $plugins_data ) ? true : false;
}

/**
 * Clear data before demo import AJAX action.
 *
 * @see tg_reset_widgets()
 * @see tg_delete_nav_menus()
 * @see tg_remove_theme_mods()
 */
if ( apply_filters( 'themegrill_clear_data_before_demo_import', true ) ) {
	add_action( 'themegrill_ajax_before_demo_import', 'tg_reset_widgets', 10 );
	add_action( 'themegrill_ajax_before_demo_import', 'tg_delete_nav_menus', 20 );
	add_action( 'themegrill_ajax_before_demo_import', 'tg_remove_theme_mods', 30 );
}

/**
 * Reset existing active widgets.
 */
function tg_reset_widgets() {
	$sidebars_widgets = wp_get_sidebars_widgets();

	// Reset active widgets.
	foreach ( $sidebars_widgets as $key => $widgets ) {
		$sidebars_widgets[ $key ] = array();
	}

	wp_set_sidebars_widgets( $sidebars_widgets );
}

/**
 * Delete existing navigation menus.
 */
function tg_delete_nav_menus() {
	$nav_menus = wp_get_nav_menus();

	// Delete navigation menus.
	if ( ! empty( $nav_menus ) ) {
		foreach ( $nav_menus as $nav_menu ) {
			wp_delete_nav_menu( $nav_menu->slug );
		}
	}
}

/**
 * Remove theme modifications option.
 */
function tg_remove_theme_mods() {
	remove_theme_mods();
}

/**
 * After demo imported AJAX action.
 *
 * @see tg_set_wc_pages()
 */
if ( class_exists( 'WooCommerce' ) ) {
	add_action( 'themegrill_ajax_demo_imported', 'tg_set_wc_pages' );
}

/**
 * Set WC pages properly and disable setup wizard redirect.
 *
 * After importing demo data filter out duplicate WC pages and set them properly.
 * Happens when the user run default woocommerce setup wizard during installation.
 *
 * Note: WC pages ID are stored in an option and slug are modified to remove any numbers.
 *
 * @param string $demo_id
 */
function tg_set_wc_pages( $demo_id ) {
	global $wpdb;

	$wc_pages = apply_filters( 'themegrill_wc_' . $demo_id . '_pages', array(
		'shop' => array(
			'name'  => 'shop',
			'title' => 'Shop',
		),
		'cart' => array(
			'name'  => 'cart',
			'title' => 'Cart',
		),
		'checkout' => array(
			'name'  => 'checkout',
			'title' => 'Checkout',
		),
		'myaccount' => array(
			'name'  => 'my-account',
			'title' => 'My Account',
		),
	) );

	// Set WC pages properly.
	foreach ( $wc_pages as $key => $wc_page ) {

		// Get the ID of every page with matching name or title.
		$page_ids = $wpdb->get_results( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE (post_name = %s OR post_title = %s) AND post_type = 'page' AND post_status = 'publish'", $wc_page['name'], $wc_page['title'] ) );

		if ( ! is_null( $page_ids ) ) {
			$page_id    = 0;
			$delete_ids	= array();

			// Retrieve page with greater id and delete others.
			if ( sizeof( $page_ids ) > 1 ) {
				foreach ( $page_ids as $page ) {
					if ( $page->ID > $page_id ) {
						if ( $page_id ) {
							$delete_ids[] = $page_id;
						}

						$page_id = $page->ID;
					} else {
						$delete_ids[] = $page->ID;
					}
				}
			} else {
				$page_id = $page_ids[0]->ID;
			}

			// Delete posts.
			foreach ( $delete_ids as $delete_id ) {
				wp_delete_post( $delete_id, true );
			}

			// Update WC page.
			if ( $page_id > 0 ) {
				update_option( 'woocommerce_' . $key . '_page_id', $page_id );
				wp_update_post( array( 'ID' => $page_id, 'post_name' => sanitize_title( $wc_page['name'] ) ) );
			}
		}
	}

	// We no longer need WC setup wizard redirect.
	delete_transient( '_wc_activation_redirect' );
}
