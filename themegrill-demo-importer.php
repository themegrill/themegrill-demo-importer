<?php
/**
 * Plugin Name: Starter Templates & Sites Pack by ThemeGrill
 * Plugin URI: https://themegrill.com/demo-importer/
 * Description: Premium starter sites and website templates by ThemeGrill. Import demo content, widgets, and theme settings with one click.
 * Version: 2.0.0.5
 * Requires at least: 5.7
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

const TGDM_VERSION     = '2.0.0.5';
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
