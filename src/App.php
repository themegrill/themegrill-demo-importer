<?php

namespace ThemeGrill\Demo\Importer;

use ThemeGrill\Demo\Importer\Traits\Singleton;

class App {
	use Singleton;

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	public $version = '2.0.0.5';

	/**
	 * Initialize the application
	 */
	protected function init() {
		$this->init_hooks();

		do_action( 'themegrill_demo_importer_loaded' );
	}

	/**
	 * Hook into actions and filters.
	 */
	private function init_hooks() {
		// Load plugin text domain.
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

		add_filter( 'plugin_action_links_' . TGDM_PLUGIN_BASENAME, array( $this, 'plugin_action_links' ) );
		add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 2 );

		Activator::init();
		Deactivator::init();
		Admin::instance();
		RestApi::instance();
		ImportHooks::instance();
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
		$locale = is_admin() && function_exists( 'get_user_locale' ) ? get_user_locale() : get_locale();
		$locale = apply_filters( 'plugin_locale', $locale, 'themegrill-demo-importer' );

		unload_textdomain( 'themegrill-demo-importer' );
		load_textdomain( 'themegrill-demo-importer', WP_LANG_DIR . '/themegrill-demo-importer/themegrill-demo-importer-' . $locale . '.mo' );
		load_plugin_textdomain( 'themegrill-demo-importer', false, plugin_basename( dirname( TGDM_PLUGIN_FILE ) ) . '/languages' );
	}

	/**
	 * Display action links in the Plugins list table.
	 *
	 * @param  array $actions Plugin Action links.
	 * @return array
	 */
	public function plugin_action_links( $actions ) {
		$url         = admin_url( Admin::$starter_templates_link );
		$new_actions = array(
			'importer' => '<a href="' . esc_url( $url ) . '" aria-label="' . esc_attr( __( 'View Starter Templates', 'themegrill-demo-importer' ) ) . '">' . __( 'Starter Templates', 'themegrill-demo-importer' ) . '</a>',
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
				'docs'    => '<a href="' . esc_url( apply_filters( 'themegrill_demo_importer_docs_url', 'https://docs.themegrill.com/themegrill-demo-importer/' ) ) . '" title="' . esc_attr( __( 'View Starter Templates Documentation', 'themegrill-demo-importer' ) ) . '">' . __( 'Docs', 'themegrill-demo-importer' ) . '</a>',
				'support' => '<a href="' . esc_url( apply_filters( 'themegrill_demo_importer_support_url', 'https://themegrill.com/support-forum/' ) ) . '" title="' . esc_attr( __( 'Visit Free Customer Support Forum', 'themegrill-demo-importer' ) ) . '">' . __( 'Free Support', 'themegrill-demo-importer' ) . '</a>',
			);

			return array_merge( $plugin_meta, $new_plugin_meta );
		}

		return (array) $plugin_meta;
	}

	/**
	 * Get the plugin url.
	 *
	 * @return string
	 */
	public static function plugin_url() {
		return untrailingslashit( plugins_url( '/', TGDM_PLUGIN_FILE ) );
	}
}
