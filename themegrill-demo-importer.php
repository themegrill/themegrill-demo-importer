<?php
/**
 * Plugin Name: ThemeGrill Demo Importer
 * Plugin URI: http://themegrill.com/demo-importer/
 * Description: Import your demo content, widgets and theme settings with one click for ThemeGrill themes
 * Version: 1.0.0
 * Author: ThemeGrill
 * Author URI: http://themegrill.com
 * License: GPLv3 or later
 * Text Domain: themegrill-demo-importer
 * Domain Path: /languages/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'ThemeGrill_Demo_Importer' ) ) :

/**
 * ThemeGrill_Demo_Importer main class.
 */
final class ThemeGrill_Demo_Importer {

	/**
	 * Plugin version.
	 * @var string
	 */
	const VERSION = '1.0.0';

	/**
	 * Instance of this class.
	 * @var object
	 */
	protected static $instance = null;

	/**
	 * Initialize the plugin.
	 */
	private function __construct() {
		// Load plugin text domain.
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

		// Check with ThemeGrill theme is installed.
		if ( in_array( get_option( 'template' ), $this->get_core_supported_themes() ) ) {
			$this->includes();
			$this->demo_includes();

			// Hooks.
			add_filter( 'themegrill_demo_importer_assets_path', array( $this, 'plugin_assets_path' ) );
			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'plugin_action_links' ) );
		} else {
			add_action( 'admin_notices', array( $this, 'theme_support_missing_notice' ) );
		}
	}

	/**
	 * Return an instance of this class.
	 * @return object A single instance of this class.
	 */
	public static function get_instance() {
		// If the single instance hasn't been set, set it now.
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Install TG Importer.
	 */
	public static function install() {
		$upload_dir = wp_upload_dir();

		if ( ! is_blog_installed() ) {
			return;
		}

		// Install files and folders for uploading files and prevent hotlinking.
		$files = array(
			array(
				'base'    => $upload_dir['basedir'] . '/tg-demo-pack',
				'file'    => 'index.html',
				'content' => '',
			),
		);

		foreach ( $files as $file ) {
			if ( wp_mkdir_p( $file['base'] ) && ! file_exists( trailingslashit( $file['base'] ) . $file['file'] ) ) {
				if ( $file_handle = @fopen( trailingslashit( $file['base'] ) . $file['file'], 'w' ) ) {
					fwrite( $file_handle, $file['content'] );
					fclose( $file_handle );
				}
			}
		}
	}

	/**
	 * Load Localisation files.
	 *
	 * Note: the first-loaded translation file overrides any following ones if the same translation is present.
	 *
	 * Locales found in:
	 *      - WP_LANG_DIR/themegrill-demo-importer/themegrill-demo-importer-LOCALE.mo
	 *      - WP_LANG_DIR/plugins/themegrill-demo-importer-LOCALE.mo
	 */
	public function load_plugin_textdomain() {
		$locale = apply_filters( 'plugin_locale', get_locale(), 'themegrill-demo-importer' );

		load_textdomain( 'themegrill-demo-importer', WP_LANG_DIR . '/themegrill-demo-importer/themegrill-demo-importer-' . $locale . '.mo' );
		load_plugin_textdomain( 'themegrill-demo-importer', false, plugin_basename( dirname( __FILE__ ) ) . '/languages' );
	}

	/**
	 * Get core supported themes.
	 * @return array
	 */
	private function get_core_supported_themes() {
		return array( 'spacious', 'colormag', 'flash' );
	}

	/**
	 * Includes.
	 */
	private function includes() {
		include_once( dirname( __FILE__ ) . '/includes/class-demo-importer.php' );
	}

	/**
	 * Includes demo config.
	 */
	private function demo_includes() {
		$upload_dir = wp_upload_dir();

		// Check the folder contains at least 1 valid demo config.
		$files = glob( $upload_dir['basedir'] . '/tg-demo-pack/**/tg-demo-config.php' );
		if ( $files ) {
			foreach ( $files as $file ) {
				if ( $file && is_readable( $file ) ) {
					include_once( $file );
				}
			}
		}
	}

	/**
	 * Get the plugin url.
	 * @return string
	 */
	public function plugin_url() {
		return untrailingslashit( plugins_url( '/', __FILE__ ) );
	}

	/**
	 * Get the plugin assets path.
	 * @return string
	 */
	public function plugin_assets_path() {
		return trailingslashit( $this->plugin_url() . '/assets/' );
	}

	/**
	 * Display action links in the Plugins list table.
	 * @param  array $actions
	 * @return array
	 */
	public function plugin_action_links( $actions ) {
		$new_actions = array(
			'previews' => '<a href="' . admin_url( 'themes.php?page=demo-importer&tab=previews' ) . '" aria-label="' . esc_attr( __( 'View Demos', 'themegrill-demo-importer' ) ) . '">' . __( 'Demos', 'themegrill-demo-importer' ) . '</a>',
		);

		return array_merge( $new_actions, $actions );
	}

	/**
	 * Theme support fallback notice.
	 * @return string
	 */
	public function theme_support_missing_notice() {
		if ( ! class_exists( 'TG_Demo_Importer' ) ) {
			echo '<div class="error notice is-dismissible"><p><strong>' . __( 'ThemeGrill Demo Importer', 'themegrill-demo-importer' ) . '</strong> &#8211; ' . sprintf( __( 'This plugin requires %s by ThemeGrill to work.', 'themegrill-demo-importer' ), '<a href="http://www.themegrill.com/themes/" target="_blank">' . __( 'Official Theme', 'themegrill-demo-importer' ) . '</a>' ) . '</p></div>';
		}
	}
}

add_action( 'plugins_loaded', array( 'ThemeGrill_Demo_Importer', 'get_instance' ) );

register_activation_hook( __FILE__, array( 'ThemeGrill_Demo_Importer', 'install' ) );

endif;
