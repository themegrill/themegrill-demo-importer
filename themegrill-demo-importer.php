<?php
/**
 * Plugin Name: Starter Templates & Sites Pack by ThemeGrill
 * Plugin URI: https://themegrill.com/demo-importer/
 * Description: Import ThemeGrill official themes demo content, widgets and theme settings with just one click.
 * Version: 2.0.0
 * Author: ThemeGrill
 * Author URI: https://themegrill.com
 * License: GPLv3 or later
 * Text Domain: themegrill-demo-importer
 * Domain Path: /languages/
 *
 * @package ThemeGrill/StarterTemplates
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const THEMEGRILL_STARTER_TEMPLATES_VERSION     = '2.0.0';
const THEMEGRILL_STARTER_TEMPLATES_PLUGIN_FILE = __FILE__;
const THEMEGRILL_STARTER_TEMPLATES_PLUGIN_DIR  = __DIR__;

define( 'THEMEGRILL_STARTER_TEMPLATES_PLUGIN_DIR_URL', plugin_dir_url( THEMEGRILL_STARTER_TEMPLATES_PLUGIN_FILE ) );
define( 'THEMEGRILL_STARTER_TEMPLATES_PLUGIN_DIR_PATH', plugin_dir_path( THEMEGRILL_STARTER_TEMPLATES_PLUGIN_FILE ) );
define( 'THEMEGRILL_STARTER_TEMPLATES_PLUGIN_BASENAME', plugin_basename( THEMEGRILL_STARTER_TEMPLATES_PLUGIN_FILE ) );

require_once __DIR__ . '/vendor/autoload.php';

\ThemeGrill\StarterTemplates\App::getInstance()->boot();
