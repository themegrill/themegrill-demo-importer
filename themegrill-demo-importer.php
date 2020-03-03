<?php
/**
 * Plugin Name: ThemeGrill Demo Importer
 * Plugin URI: https://themegrill.com/demo-importer/
 * Description: Import ThemeGrill official themes demo content, widgets and theme settings with just one click.
 * Version: 1.6.3
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
	include_once dirname( __FILE__ ) . '/includes/class-themegrill-demo-importer.php';
}

/**
 * Main instance of ThemeGrill Demo importer.
 *
 * Returns the main instance of ThemeGrill_Demo_Importer to prevent the need to use globals.
 *
 * @return object A single instance of class ThemeGrill_Demo_Importer
 * @since  1.3.4
 */
function tgdm() {
	return ThemeGrill_Demo_Importer::instance();
}

// Global for backwards compatibility.
$GLOBALS['themegrill-demo-importer'] = tgdm();
