<?php

namespace ThemeGrill\Demo\Importer;

use ThemeGrill\Demo\Importer\Traits\Singleton;

class Stats {
	use Singleton;

	const FORMBRICKS_ENV_ID = 'TODO'; // Replace with real env ID before deploying.

	protected function init() {
		// Consent sync, cron bridge, and debug hooks work without SDK — register unconditionally.
		add_filter( 'pre_option_themegrill_demo_importer_logger_flag', array( $this, 'get_logger_status' ) );
		add_action( 'update_option_tdi_allow_contribution', array( $this, 'sync_logger_flag' ), 10, 2 );
		add_action( 'tdi_weekly_contribution', array( $this, 'fire_sdk_log' ) );
		add_action( 'themegrill_demo_importer_import_complete', array( $this, 'fire_post_import_ping' ) );
		add_action( 'themegrill_demo_importer_log_activity', array( $this, 'debug_log_cron_fired' ), 1 );
		add_filter( 'pre_http_request', array( $this, 'debug_log_payload' ), 10, 3 );

		if ( ! file_exists( dirname( TGDM_PLUGIN_FILE ) . '/vendor/themegrill/themegrill-sdk/load.php' ) ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( 'Demo Importer SDK vendor not found at: ' . dirname( TGDM_PLUGIN_FILE ) . '/vendor/themegrill/themegrill-sdk/load.php' );
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
		$data['theme_mods']      = wp_json_encode( get_theme_mods() );

		return $data;
	}

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
	 * @param mixed $_old_value Previous option value.
	 * @param mixed $new_value  New option value.
	 */
	public function sync_logger_flag( $_old_value, $new_value ) {
		update_option( 'themegrill_demo_importer_logger_flag', $new_value );

		if ( 'yes' === $new_value ) {
			if ( ! wp_next_scheduled( 'tdi_weekly_contribution' ) ) {
				wp_schedule_event( time() + WEEK_IN_SECONDS, 'weekly', 'tdi_weekly_contribution' );
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( 'TDI tracking: consent granted → scheduled tdi_weekly_contribution (weekly)' );
			} else {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( 'TDI tracking: consent granted → tdi_weekly_contribution already scheduled' );
			}
		} else {
			wp_clear_scheduled_hook( 'tdi_weekly_contribution' );
			wp_clear_scheduled_hook( 'themegrill_demo_importer_log_activity' );
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( 'TDI tracking: consent revoked → cleared tdi_weekly_contribution + themegrill_demo_importer_log_activity' );
		}
	}

	/**
	 * Fire immediate ping after import completes so imported_demos data is accurate.
	 */
	public function fire_post_import_ping() {
		$consent = get_option( 'tdi_allow_contribution', 'no' );
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( 'TDI tracking: import complete hook fired (tdi_allow_contribution=' . $consent . ', imported_demos=' . implode( ',', get_option( '_tgdm_imported_demos', array() ) ) . ')' );

		if ( 'yes' !== $consent ) {
			return;
		}

		$this->fire_tracking_immediately();
	}

	/**
	 * Re-run Logger::setup_actions() with flag now 'yes', then fire immediately.
	 * Needed because setup_actions() ran at wp_loaded before consent was given.
	 */
	private function fire_tracking_immediately() {
		global $wp_filter;
		if ( ! isset( $wp_filter['wp_loaded'] ) || ! class_exists( 'ThemeGrillSDK\Modules\Logger' ) ) {
			return;
		}
		foreach ( $wp_filter['wp_loaded']->callbacks as $callbacks ) {
			foreach ( $callbacks as $cb ) {
				if ( is_array( $cb['function'] )
					&& $cb['function'][0] instanceof \ThemeGrillSDK\Modules\Logger
					&& 'setup_actions' === $cb['function'][1] ) {
					$cb['function'][0]->setup_actions();
				}
			}
		}
		do_action( 'themegrill_demo_importer_log_activity' );
	}

	/**
	 * Cron callback: fire SDK log action so send_log() runs.
	 */
	public function fire_sdk_log() {
		do_action( 'themegrill_demo_importer_log_activity' );
	}

	/**
	 * Log when themegrill_demo_importer_log_activity fires.
	 */
	public function debug_log_cron_fired() {
		global $wp_filter;

		$source = wp_doing_cron() ? 'wp-cron' : 'manual/ajax';
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( 'Demo Importer cron fired: themegrill_demo_importer_log_activity (source: ' . $source . ', allow_contribution: ' . get_option( 'tdi_allow_contribution', 'not set' ) . ', sdk_loaded: ' . ( class_exists( 'ThemeGrillSDK\Loader' ) ? 'yes' : 'no' ) . ')' );

		if ( isset( $wp_filter['themegrill_demo_importer_log_activity'] ) ) {
			foreach ( $wp_filter['themegrill_demo_importer_log_activity']->callbacks as $priority => $callbacks ) {
				foreach ( $callbacks as $cb ) {
					if ( is_array( $cb['function'] ) ) {
						$name = get_class( $cb['function'][0] ) . '->' . $cb['function'][1];
					} elseif ( $cb['function'] instanceof \Closure ) {
						$name = 'Closure';
					} else {
						$name = (string) $cb['function'];
					}
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
					error_log( '  themegrill_demo_importer_log_activity priority ' . $priority . ': ' . $name );
				}
			}
		}
	}

	/**
	 * Log SDK tracking payload when sent to api.themegrill.com.
	 *
	 * @param bool|array $pre  Whether to preempt the request.
	 * @param array      $args Request arguments.
	 * @param string     $url  Request URL.
	 * @return bool|array
	 */
	public function debug_log_payload( $pre, $args, $url ) {
		if ( false !== strpos( $url, 'api.themegrill.com/tracking/log' ) ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( 'Demo Importer SDK payload sent to: ' . $url );
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( 'Demo Importer SDK body: ' . print_r( json_decode( $args['body'], true ), true ) );
		}
		return $pre;
	}

	private function get_install_days() {
		$install_time = get_option( 'themegrill_demo_importer_install', time() );
		return (int) floor( ( time() - (int) $install_time ) / DAY_IN_SECONDS );
	}
}
