<?php
/**
 * ThemeGrill Demo Importer setup.
 *
 * @package ThemeGrill_Demo_Importer
 * @since   1.5.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Main ThemeGrill Demo Importer Class.
 *
 * @class ThemeGrill_Demo_Importer
 */
final class ThemeGrill_Demo_Importer {

	/**
	 * ThemeGrill Demo Importer version.
	 *
	 * @var string
	 */
	public $version = '2.0.0';

	/**
	 * The single instance of the class.
	 *
	 * @var object
	 */
	protected static $_instance = null;

	/**
	 * Main ThemeGrill Demo Importer Instance.
	 *
	 * Ensures only one instance of ThemeGrill Demo Importer is loaded or can be loaded.
	 *
	 * @return ThemeGrill_Demo_Importer Main instance.
	 */
	public static function instance() {

		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;

	}

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.4
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Cheatin&#8217; huh?', 'themegrill-demo-importer' ), '1.4' );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.4
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Cheatin&#8217; huh?', 'themegrill-demo-importer' ), '1.4' );
	}

	/**
	 * ThemeGrill Demo Importer Constructor.
	 */
	private function __construct() {

		$this->define_constants();
		$this->init_hooks();

		do_action( 'themegrill_demo_importer_loaded' );

	}

	/**
	 * Define ThemeGrill Demo Importer Constants.
	 */
	private function define_constants() {

		$upload_dir = wp_upload_dir( null, false );

		$this->define( 'TGDM_ABSPATH', dirname( TGDM_PLUGIN_FILE ) . '/' );
		$this->define( 'TGDM_PLUGIN_BASENAME', plugin_basename( TGDM_PLUGIN_FILE ) );
		$this->define( 'TGDM_VERSION', $this->version );
		$this->define( 'TGDM_DEMO_DIR', $upload_dir['basedir'] . '/tg-demo-pack/' );

	}

	/**
	 * Define constant if not already set.
	 *
	 * @param string      $name  Constant name.
	 * @param string|bool $value Constant value.
	 */
	private function define( $name, $value ) {

		if ( ! defined( $name ) ) {
			define( $name, $value );
		}

	}

	/**
	 * Hook into actions and filters.
	 */
	private function init_hooks() {

		// Load plugin text domain.
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

		// Register activation hook.
		register_activation_hook( TGDM_PLUGIN_FILE, array( $this, 'install' ) );

		// Register deactivation hook.
		register_deactivation_hook( TGDM_PLUGIN_FILE, array( $this, 'deactivate' ) );

		// Check with Official ThemeGrill theme is installed.
		if ( in_array( get_option( 'template' ), $this->get_core_supported_themes(), true ) ) {
			$this->includes();

			add_filter( 'plugin_action_links_' . TGDM_PLUGIN_BASENAME, array( $this, 'plugin_action_links' ) );
			add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 2 );
		} else {
			add_action( 'admin_notices', array( $this, 'theme_support_missing_notice' ) );
		}

	}

	/**
	 * Get core supported themes.
	 *
	 * @return array
	 */
	private function get_core_supported_themes() {

		// Main official core themes.
		$core_themes = array(
			'spacious',
			'colormag',
			'flash',
			'estore',
			'ample',
			'accelerate',
			'colornews',
			'foodhunt',
			'fitclub',
			'radiate',
			'freedom',
			'himalayas',
			'esteem',
			'envince',
			'suffice',
			'explore',
			'masonic',
			'cenote',
			'zakra',
		);

		// Check for official core themes pro version.
		$pro_themes = array_diff(
			$core_themes,
			array(
				'explore',
				'masonic',
				'zakra',
			)
		);

		if ( ! empty( $pro_themes ) ) {
			$pro_themes = preg_replace( '/$/', '-pro', $pro_themes );
		}

		return array_merge( $core_themes, $pro_themes );

	}

	/**
	 * Include required core files.
	 */
	private function includes() {

		include_once TGDM_ABSPATH . 'includes/class-demo-importer.php';
		include_once TGDM_ABSPATH . 'includes/functions-demo-importer.php';
		include_once TGDM_ABSPATH . 'includes/admin/class-plugin-review-notice.php';
		include_once TGDM_ABSPATH . 'includes/admin/class-plugin-deactivate-notice.php';

	}

	/**
	 * Install/Create ThemeGrill Demo Importer main uploads folder.
	 */
	public function install() {

		$files = array(
			array(
				'base'    => TGDM_DEMO_DIR,
				'file'    => 'index.html',
				'content' => '',
			),
		);

		// Bypass if filesystem is read-only and/or non-standard upload system is used.
		if ( ! is_blog_installed() || apply_filters( 'themegrill_demo_importer_install_skip_create_files', false ) ) {
			return;
		}

		// Install files and folders.
		foreach ( $files as $file ) {

			if ( wp_mkdir_p( $file['base'] ) && ! file_exists( trailingslashit( $file['base'] ) . $file['file'] ) ) {

				$file_handle = @fopen( trailingslashit( $file['base'] ) . $file['file'], 'w' ); // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.file_system_read_fopen, WordPress.PHP.NoSilencedErrors.Discouraged

				if ( $file_handle ) {
					fwrite( $file_handle, $file['content'] ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fwrite
					fclose( $file_handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fclose
				}
			}
		}

		// Redirect to demo importer page.
		set_transient( '_tg_demo_importer_activation_redirect', 1, 30 );

	}

	/**
	 * Deactivation hook.
	 */
	public function deactivate() {

		include_once dirname( __FILE__ ) . '/class-demo-importer-deactivator.php';

		TG_Demo_Importer_Deactivator::deactivate();

	}

	/**
	 * Load Localisation files.
	 *
	 * Note: the first-loaded translation file overrides any following ones if the same translation is present.
	 *
	 * Locales found in:
	 *  - WP_LANG_DIR/themegrill-demo-importer/themegrill-demo-importer-LOCALE.mo
	 *  - WP_LANG_DIR/plugins/themegrill-demo-importer-LOCALE.mo
	 */
	public function load_plugin_textdomain() {

		$locale = is_admin() && function_exists( 'get_user_locale' ) ? get_user_locale() : get_locale();
		$locale = apply_filters( 'plugin_locale', $locale, 'themegrill-demo-importer' );

		unload_textdomain( 'themegrill-demo-importer' );
		load_textdomain( 'themegrill-demo-importer', WP_LANG_DIR . '/themegrill-demo-importer/themegrill-demo-importer-' . $locale . '.mo' );
		load_plugin_textdomain( 'themegrill-demo-importer', false, plugin_basename( dirname( TGDM_PLUGIN_FILE ) ) . '/languages' );

	}

	/**
	 * Get the plugin url.
	 *
	 * @return string
	 */
	public function plugin_url() {
		return untrailingslashit( plugins_url( '/', TGDM_PLUGIN_FILE ) );
	}

	/**
	 * Get the plugin path.
	 *
	 * @return string
	 */
	public function plugin_path() {
		return untrailingslashit( plugin_dir_path( TGDM_PLUGIN_FILE ) );
	}

	/**
	 * Display action links in the Plugins list table.
	 *
	 * @param  array $actions Plugin Action links.
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
	 *
	 * @param  array  $plugin_meta Plugin Row Meta.
	 * @param  string $plugin_file Plugin Row Meta.
	 * @return array
	 */
	public function plugin_row_meta( $plugin_meta, $plugin_file ) {

		if ( TGDM_PLUGIN_BASENAME === $plugin_file ) {

			$new_plugin_meta = array(
				'docs'    => '<a href="' . esc_url( apply_filters( 'themegrill_demo_importer_docs_url', 'https://themegrill.com/docs/themegrill-demo-importer/' ) ) . '" title="' . esc_attr( __( 'View Demo Importer Documentation', 'themegrill-demo-importer' ) ) . '">' . __( 'Docs', 'themegrill-demo-importer' ) . '</a>',
				'support' => '<a href="' . esc_url( apply_filters( 'themegrill_demo_importer_support_url', 'https://themegrill.com/support-forum/' ) ) . '" title="' . esc_attr( __( 'Visit Free Customer Support Forum', 'themegrill-demo-importer' ) ) . '">' . __( 'Free Support', 'themegrill-demo-importer' ) . '</a>',
			);

			return array_merge( $plugin_meta, $new_plugin_meta );
		}

		return (array) $plugin_meta;

	}

	/**
	 * Theme support fallback notice.
	 */
	public function theme_support_missing_notice() {

		$output     = '';
		$themes_url = array_intersect( array_keys( wp_get_themes() ), $this->get_core_supported_themes() ) ? admin_url( 'themes.php?search=themegrill' ) : admin_url( 'theme-install.php?search=themegrill' );

		$output .= '<div class="error notice is-dismissible"><p><strong>';
		$output .= esc_html__( 'ThemeGrill Demo Importer', 'themegrill-demo-importer' );
		$output .= '</strong> &#8211; ';
		$output .= sprintf(
			/* translators: %s: official ThemeGrill themes URL */
			esc_html__( 'This plugin requires %s to be activated to work.', 'themegrill-demo-importer' ),
			'<a href="' . esc_url( $themes_url ) . '">' . esc_html__( 'Official ThemeGrill Theme', 'themegrill-demo-importer' ) . '</a>'
		);
		$output .= '</p></div>';

		echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

	}
}
