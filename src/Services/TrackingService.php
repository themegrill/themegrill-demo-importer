<?php

namespace ThemeGrill\Demo\Importer\Services;

class TrackingService {

	const WEEKLY_CRON_HOOK   = 'tdi_weekly_tracking';
	const FIRST_RUN_CRON_HOOK = 'tdi_first_tracking';
	const TRACKING_ENDPOINT  = 'https://api.themegrill.com/tracking/log';
	const SDK_LOGGER_FLAG    = 'themegrill_demo_importer_logger_flag';

	/**
	 * Called after a successful import when tracking is opted in.
	 * Schedules an immediate (async) first ping and a recurring weekly ping.
	 */
	public function track_on_import_success() {
		if ( get_option( 'tdi_allow_tracking' ) !== 'yes' ) {
			return;
		}

		// Sync SDK logger flag so the SDK's own Logger also activates.
		update_option( self::SDK_LOGGER_FLAG, 'yes' );

		// Fire first ping asynchronously (non-blocking) immediately.
		$this->send_tracking_data( false );

		// Schedule weekly recurring pings if not already scheduled.
		if ( ! wp_next_scheduled( self::WEEKLY_CRON_HOOK ) ) {
			wp_schedule_event( time() + WEEK_IN_SECONDS, 'weekly', self::WEEKLY_CRON_HOOK );
		}
	}

	/**
	 * Cron callback — runs weekly.
	 */
	public function fire_weekly_tracking() {
		if ( get_option( 'tdi_allow_tracking' ) !== 'yes' ) {
			return;
		}
		$this->send_tracking_data( true );
	}

	/**
	 * Remove the weekly cron event (called on plugin deactivation).
	 */
	public function unschedule() {
		$timestamp = wp_next_scheduled( self::WEEKLY_CRON_HOOK );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, self::WEEKLY_CRON_HOOK );
		}
	}

	/**
	 * Build and send the tracking payload.
	 *
	 * @param bool $blocking Whether the HTTP request should block.
	 */
	private function send_tracking_data( bool $blocking = false ) {
		global $wp_version;

		$theme       = wp_get_theme();
		$environment = array(
			'theme'   => array(
				'name'   => $theme->get( 'Name' ),
				'author' => $theme->get( 'Author' ),
				'parent' => $theme->parent() ? $theme->parent()->get( 'Name' ) : $theme->get( 'Name' ),
			),
			'plugins' => get_option( 'active_plugins', array() ),
		);

		$custom_data = apply_filters( 'themegrill_demo_importer_logger_data', array() );

		wp_remote_post(
			self::TRACKING_ENDPOINT,
			array(
				'method'      => 'POST',
				'timeout'     => 3,
				'redirection' => 5,
				'blocking'    => $blocking,
				'body'        => wp_json_encode(
					array(
						'site'         => get_site_url(),
						'slug'         => 'themegrill-demo-importer',
						'version'      => TGDM_VERSION,
						'wp_version'   => $wp_version,
						'install_time' => get_option( 'themegrill_demo_importer_install', time() ),
						'locale'       => get_locale(),
						'data'         => $custom_data,
						'environment'  => $environment,
						'license'      => apply_filters( 'themegrill_demo_importer_license_status', '' ),
					)
				),
				'headers'     => array(
					'Content-Type' => 'application/json',
					'User-Agent'   => 'ThemeGrillSDK',
				),
			)
		);
	}
}
