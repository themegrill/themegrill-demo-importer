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

function allow_iframe_in_import( $allowedposttags ) {
	$allowedposttags['iframe'] = array(
		'src'             => array(),
		'width'           => array(),
		'height'          => array(),
		'frameborder'     => array(),
		'allowfullscreen' => array(),
		'allow'           => array(),
		'loading'         => array(),
	);

	return $allowedposttags;
}
add_filter( 'wp_kses_allowed_html', 'allow_iframe_in_import', 10, 1 );

function allow_iframe_after_import( $postdata, $post ) { //phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
	$postdata['post_content'] = wp_kses( $postdata['post_content'], wp_kses_allowed_html( 'post' ) );
	return $postdata;
}
add_filter( 'wp_import_post_data_processed', 'allow_iframe_after_import', 10, 2 );
