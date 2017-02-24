<?php
/**
 * Plugin Name: ThemeGrill Demo Importer
 * Plugin URI: http://themegrill.com/demo-importer/
 * Description: Description: Import your demo content, widgets and theme settings with one click for ThemeGrill official themes.
 * Version: 1.3.0
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
	const VERSION = '1.3.0';

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

			// Hooks.
			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'plugin_action_links' ) );
			add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 2 );
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

		// Redirect to demo importer page.
		set_transient( '_tg_demo_importer_activation_redirect', 1, 30 );
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
		$pro_themes  = array();
		$core_themes = array( 'spacious', 'colormag', 'flash', 'estore' );

		// Check for core themes pro version :)
		foreach ( $core_themes as $core_theme ) {
			$pro_themes[] = $core_theme . '-pro';
		}

		return array_merge( $core_themes, $pro_themes );
	}

	/**
	 * Includes.
	 */
	private function includes() {
		include_once( dirname( __FILE__ ) . '/includes/class-demo-importer.php' );
		include_once( dirname( __FILE__ ) . '/includes/functions-demo-update.php' );

		// Includes demo packages config.
		if ( false === strpos( get_option( 'template' ), '-pro' ) ) {
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
	}

	/**
	 * Get the plugin url.
	 * @return string
	 */
	public static function plugin_url() {
		return untrailingslashit( plugins_url( '/', __FILE__ ) );
	}

	/**
	 * Display action links in the Plugins list table.
	 * @param  array $actions
	 * @return array
	 */
	public function plugin_action_links( $actions ) {
		$new_actions = array(
			'importer' => '<a href="' . admin_url( 'themes.php?page=demo-importer' ) . '" aria-label="' . esc_attr( __( 'View Demo Importer', 'themegrill-demo-importer' ) ) . '">' . __( 'Demo Importer', 'themegrill-demo-importer' ) . '</a>',
		);

		return array_merge( $new_actions, $actions );
	}

	/**
	 * Display row meta in the Plugins list table.
	 * @param  array  $plugin_meta
	 * @param  string $plugin_file
	 * @return array
	 */
	public function plugin_row_meta( $plugin_meta, $plugin_file ) {
		if ( plugin_basename( __FILE__ ) == $plugin_file ) {
			$new_plugin_meta = array(
				'docs'    => '<a href="' . esc_url( apply_filters( 'themegrill_demo_importer_docs_url', 'http://themegrill.com/docs/themegrill-demo-importer/' ) ) . '" title="' . esc_attr( __( 'View Demo Importer Documentation', 'themegrill-demo-importer' ) ) . '">' . __( 'Docs', 'themegrill-demo-importer' ) . '</a>',
				'support' => '<a href="' . esc_url( apply_filters( 'themegrill_demo_importer_support_url', 'http://themegrill.com/support-forum/' ) ) . '" title="' . esc_attr( __( 'Visit Free Customer Support Forum', 'themegrill-demo-importer' ) ) . '">' . __( 'Free Support', 'themegrill-demo-importer' ) . '</a>',
			);

			return array_merge( $plugin_meta, $new_plugin_meta );
		}

		return (array) $plugin_meta;
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
