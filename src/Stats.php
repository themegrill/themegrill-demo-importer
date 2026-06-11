<?php

namespace ThemeGrill\Demo\Importer;

use ThemeGrill\Demo\Importer\Traits\Singleton;

class Stats {
	use Singleton;

	const FORMBRICKS_ENV_ID = 'TODO'; // Replace with real env ID before deploying.

	protected function init() {
		// Consent sync and cron bridge work without SDK — register unconditionally.
		add_filter( 'pre_option_themegrill_demo_importer_logger_flag', array( $this, 'get_logger_status' ) );
		add_action( 'update_option_tdi_allow_contribution', array( $this, 'sync_logger_flag' ), 10, 2 );
		add_action( 'tdi_weekly_contribution', array( $this, 'fire_sdk_log' ) );
		add_action( 'themegrill_demo_importer_import_complete', array( $this, 'fire_post_import_ping' ) );

		if ( ! file_exists( dirname( TGDM_PLUGIN_FILE ) . '/vendor/themegrill/themegrill-sdk/load.php' ) ) {
			return;
		}

		add_filter( 'themegrill_sdk_products', array( $this, 'register_product' ) );
		add_action( 'init', array( $this, 'customize_deactivation_labels' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'declare_internal_page' ) );
		add_filter( 'themegrill-sdk/survey/themegrill-demo-importer', array( $this, 'configure_formbricks' ), 10, 2 );
		add_filter( 'themegrill_demo_importer_logger_data', array( $this, 'logger_data' ) );
		add_action( 'admin_init', array( $this, 'bridge_notification_hooks' ), 20 );

		// Suppress SDK opt-in notice — REST endpoint handles consent.
		add_filter( 'themegrill_demo_importer_logger_flag_should_show', '__return_false' );
	}

	/**
	 * Register this plugin as a ThemeGrill SDK product.
	 *
	 * @param array $products Existing registered product base files.
	 * @return array
	 */
	public function register_product( $products ) {
		$products[] = TGDM_PLUGIN_FILE;
		return $products;
	}

	/**
	 * Override the uninstall survey heading and option labels for this plugin.
	 * Runs on 'init' after the SDK has loaded its defaults into Loader::$labels.
	 */
	public function customize_deactivation_labels() {
		if ( ! class_exists( 'ThemeGrillSDK\Loader' ) ) {
			return;
		}

		\ThemeGrillSDK\Loader::$labels['uninstall']['heading_plugin'] = __(
			'Why are you deactivating Starter Templates?',
			'themegrill-demo-importer'
		);

		\ThemeGrillSDK\Loader::$labels['uninstall']['options'] = array_merge(
			\ThemeGrillSDK\Loader::$labels['uninstall']['options'],
			array(
				'id3' => array(
					'title'       => __( "I couldn't find the starter template I needed", 'themegrill-demo-importer' ),
					'placeholder' => __( 'What type of template were you looking for?', 'themegrill-demo-importer' ),
				),
				'id4' => array(
					'title'       => __( 'The import failed or did not work correctly', 'themegrill-demo-importer' ),
					'placeholder' => __( 'What problem did you experience?', 'themegrill-demo-importer' ),
				),
				'id5' => array(
					'title'       => __( 'I no longer need it', 'themegrill-demo-importer' ),
					'placeholder' => __( 'If you could improve one thing, what would it be?', 'themegrill-demo-importer' ),
				),
				'id6' => array(
					'title'       => __( 'It has a compatibility issue with my theme or plugin', 'themegrill-demo-importer' ),
					'placeholder' => __( 'What theme or plugin caused the issue?', 'themegrill-demo-importer' ),
				),
			)
		);
	}

	/**
	 * Fire the SDK internal-page action on the plugin's own admin screen.
	 * SDK ScriptLoader listens to this action to decide whether to enqueue the survey.
	 */
	public function declare_internal_page() {
		if ( ! is_admin() ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';

		if ( 'tg-starter-templates' !== $page ) {
			return;
		}

		do_action( 'themegrill_internal_page', 'themegrill-demo-importer', $page );
	}

	/**
	 * Return Formbricks survey configuration for this product.
	 * Called by SDK ScriptLoader via 'themegrill-sdk/survey/themegrill-demo-importer' filter.
	 *
	 * @param array  $data      Existing survey data (empty on first call).
	 * @param string $page_slug Current admin page slug.
	 * @return array
	 */
	public function configure_formbricks( $data, $page_slug ) {
		if ( empty( $page_slug ) ) {
			return $data;
		}

		return array(
			'environmentId' => self::FORMBRICKS_ENV_ID,
			'attributes'    => array(
				'install_days_number' => (int) $this->get_install_days(),
				'is_premium'          => false,
				'total_imports'       => count( get_option( '_tgdm_imported_demos', array() ) ),
				'imported_demos'      => implode( ',', get_option( '_tgdm_imported_demos', array() ) ),
			),
		);
	}

	/**
	 * Append demo-importer-specific data to the SDK Logger payload.
	 * Called by SDK Logger via 'themegrill_demo_importer_logger_data' filter (opt-in only).
	 *
	 * @param array $data Existing custom data array.
	 * @return array
	 */
	public function logger_data( $data ) {
		global $wpdb;

		$theme          = wp_get_theme();
		$imported_demos = get_option( '_tgdm_imported_demos', array() );

		$data['active_theme']    = $theme->get_stylesheet();
		$data['total_imports']   = count( $imported_demos );
		$data['imported_demos']  = $imported_demos;
		$data['php_version']     = PHP_VERSION;
		$data['mysql_version']   = $wpdb->db_version();
		$data['server_software'] = isset( $_SERVER['SERVER_SOFTWARE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) ) : '';
		$data['theme_mods']      = get_theme_mods();

		return $data;
	}

	/**
	 * Calculate days since the SDK first registered this plugin.
	 * SDK sets 'themegrill_demo_importer_install' on first product registration.
	 *
	 * @return int
	 */
	/**
	 * Bridge SDK notification hook case mismatch.
	 * Logger registers on 'themegrill_sdk_registered_notifications' (lowercase),
	 * but Notification module reads 'themeGrill_sdk_registered_notifications' (capital G).
	 * Runs at admin_init priority 20 — after Logger adds its filter at priority 10.
	 */
	public function bridge_notification_hooks() {
		add_filter(
			'themeGrill_sdk_registered_notifications',
			function ( $notifications ) {
				return apply_filters( 'themegrill_sdk_registered_notifications', $notifications );
			},
			20
		);
	}

	/**
	 * Return tdi_allow_contribution so SDK Logger reads consent from our option.
	 *
	 * @return string 'yes'|'no'
	 */
	public function get_logger_status() {
		return get_option( 'tdi_allow_contribution', 'no' );
	}

	/**
	 * Sync SDK logger flag and schedule/unschedule crons when consent changes.
	 *
	 * @param mixed $old_value Previous option value.
	 * @param mixed $new_value New option value.
	 */
	public function sync_logger_flag( $_old_value, $new_value ) {
		update_option( 'themegrill_demo_importer_logger_flag', $new_value );

		if ( 'yes' !== $new_value ) {
			wp_clear_scheduled_hook( 'tdi_weekly_contribution' );
		}
	}

	/**
	 * Fire first SDK ping after import completes (so imported_demos is accurate).
	 * Called by ImportService::completeImport() via action hook.
	 */
	public function fire_post_import_ping() {
		if ( 'yes' !== get_option( 'tdi_allow_contribution', 'no' ) ) {
			return;
		}

		wp_schedule_single_event( time() - 1, 'themegrill_demo_importer_log_activity' );

		if ( ! wp_next_scheduled( 'tdi_weekly_contribution' ) ) {
			wp_schedule_event( time() + WEEK_IN_SECONDS, 'weekly', 'tdi_weekly_contribution' );
		}

		spawn_cron();
	}

	/**
	 * Cron callback: fire SDK log action so send_log() runs.
	 */
	public function fire_sdk_log() {
		do_action( 'themegrill_demo_importer_log_activity' );
	}

	private function get_install_days() {
		$install_time = get_option( 'themegrill_demo_importer_install', time() );
		return (int) floor( ( time() - (int) $install_time ) / DAY_IN_SECONDS );
	}
}
