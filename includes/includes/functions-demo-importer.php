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
		),
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
 * Ajax handler for deleting a demo pack.
 * @see tg_delete_demo_pack()
 */
function tg_ajax_delete_demo_pack() {
	check_ajax_referer( 'updates' );

	if ( empty( $_POST['slug'] ) ) {
		wp_send_json_error( array(
			'slug'         => '',
			'errorCode'    => 'no_demo_specified',
			'errorMessage' => __( 'No demo specified.', 'themegrill-demo-importer' ),
		) );
	}

	$demo_pack  = preg_replace( '/[^A-z0-9_\-]/', '', wp_unslash( $_POST['slug'] ) );
	$status     = array(
		'delete' => 'demo_pack',
		'slug'   => $demo_pack,
	);

	if ( ! current_user_can( 'upload_files' ) ) {
		$status['errorMessage'] = __( 'Sorry, you are not allowed to delete demo on this site.', 'themegrill-demo-importer' );
		wp_send_json_error( $status );
	}

	// Check filesystem credentials. `tg_delete_demo_pack()` will bail otherwise.
	$url = wp_nonce_url( 'themes.php?page=demo-importer&browse=uploads&action=delete&demo_pack=' . urlencode( $demo_pack ), 'delete-demo_' . $demo_pack );
	ob_start();
	$credentials = request_filesystem_credentials( $url );
	ob_end_clean();
	if ( false === $credentials || ! WP_Filesystem( $credentials ) ) {
		global $wp_filesystem;

		$status['errorCode']    = 'unable_to_connect_to_filesystem';
		$status['errorMessage'] = __( 'Unable to connect to the filesystem. Please confirm your credentials.', 'themegrill-demo-importer' );

		// Pass through the error from WP_Filesystem if one was raised.
		if ( $wp_filesystem instanceof WP_Filesystem_Base && is_wp_error( $wp_filesystem->errors ) && $wp_filesystem->errors->get_error_code() ) {
			$status['errorMessage'] = esc_html( $wp_filesystem->errors->get_error_message() );
		}

		wp_send_json_error( $status );
	}

	$result = tg_delete_demo_pack( $demo_pack );

	if ( is_wp_error( $result ) ) {
		$status['errorMessage'] = $result->get_error_message();
		wp_send_json_error( $status );
	} elseif ( false === $result ) {
		$status['errorMessage'] = __( 'Demo could not be deleted.', 'themegrill-demo-importer' );
		wp_send_json_error( $status );
	}

	wp_send_json_success( $status );
}
add_action( 'wp_ajax_delete-demo', 'tg_ajax_delete_demo_pack', 1 );

/**
 * Remove a demo.
 *
 *
 * @global WP_Filesystem_Base $wp_filesystem Subclass
 *
 * @param  string $demo_pack  Demo pack to delete completely.
 * @param  string $redirect   Redirect to page when complete.
 * @return void|bool|WP_Error When void, echoes content.
 */
function tg_delete_demo_pack( $demo_pack, $redirect = '' ) {
	global $wp_filesystem;

	$upload_dir       = wp_upload_dir();
	$demo_packs       = $upload_dir['basedir'] . '/tg-demo-pack/';
	$demo_imported_id = get_option( 'themegrill_demo_imported_id' );

	if ( empty( $demo_pack ) ) {
		return false;
	}

	if ( empty( $redirect ) ) {
		$redirect = wp_nonce_url( 'themes.php?page=demo-importer&browse=uploads&action=delete&demo_pack=' . urlencode( $demo_pack ), 'delete-demo_' . $demo_pack );
	}

	ob_start();
	$credentials = request_filesystem_credentials( $redirect );
	$data = ob_get_clean();

	if ( false === $credentials ) {
		if ( ! empty( $data ) ) {
			include_once( ABSPATH . 'wp-admin/admin-header.php' );
			echo $data;
			include( ABSPATH . 'wp-admin/admin-footer.php' );
			exit;
		}
		return;
	}

	if ( ! WP_Filesystem( $credentials ) ) {
		ob_start();
		request_filesystem_credentials( $redirect, '', true ); // Failed to connect, Error and request again.
		$data = ob_get_clean();

		if ( ! empty( $data ) ) {
			include_once( ABSPATH . 'wp-admin/admin-header.php' );
			echo $data;
			include( ABSPATH . 'wp-admin/admin-footer.php' );
			exit;
		}
		return;
	}

	if ( ! is_object( $wp_filesystem ) ) {
		return new WP_Error( 'fs_unavailable', __( 'Could not access filesystem.', 'themegrill-demo-importer' ) );
	}

	if ( is_wp_error( $wp_filesystem->errors ) && $wp_filesystem->errors->get_error_code() ) {
		return new WP_Error( 'fs_error', __( 'Filesystem error.', 'themegrill-demo-importer' ), $wp_filesystem->errors );
	}

	// Get the base demo folder.
	$demos_dir = $wp_filesystem->find_folder( $demo_packs );
	if ( empty( $demos_dir ) ) {
		return new WP_Error( 'fs_no_themes_dir', __( 'Unable to locate ThemeGrill demo pack directory.', 'themegrill-demo-importer' ) );
	}

	$demos_dir = trailingslashit( $demos_dir );
	$demo_dir  = trailingslashit( $demos_dir . $demo_pack );
	$deleted   = $wp_filesystem->delete( $demo_dir, true );

	if ( ! $deleted ) {
		return new WP_Error( 'could_not_remove_demo', sprintf( __( 'Could not fully remove the demo %s.', 'themegrill-demo-importer' ), $demo ) );
	}

	return true;
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
