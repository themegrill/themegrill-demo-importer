<?php

namespace ThemeGrill\Demo\Importer\Importers;

use Exception;
use ThemeGrill\Demo\Importer\Logger;

class PluginImporter {

	private $logger;

	public function __construct() {
		$this->logger = Logger::getInstance();
		$this->includes();
	}

	public function includes() {
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
	}

	public function installPlugins( $plugins ) {
		$results = array();

		$results = array_map(
			function ( $plugin ) {
				return $this->installActivatePlugin( $plugin );
			},
			$plugins
		);
		return $results;
	}

	/**
	 * Retry a WordPress.org network call once on failure.
	 *
	 * `plugins_api()` and `Plugin_Upgrader::install()` run synchronously, one
	 * plugin after another, inside a single request with no timeout handling
	 * of their own — a transient network hiccup on any one of them previously
	 * just failed outright. Since these failures were also silently swallowed
	 * client-side until now (see StartImport.tsx), a plugin could look like it
	 * "just didn't install" with no visible cause. This doesn't fix that
	 * client-side gap, but it does reduce how often a single flaky request
	 * causes a failure at all.
	 *
	 * @param callable $attempt             Zero-arg callable returning WP_Error|false on failure.
	 * @param string   $plugin_slug         Plugin slug, for logging only.
	 * @param string   $action_description  Human-readable action, for logging only.
	 * @param int      $max_attempts        Total attempts including the first.
	 * @return mixed The last attempt's result (success or final failure).
	 */
	private function withRetry( callable $attempt, $plugin_slug, $action_description, $max_attempts = 2 ) {
		$result = null;

		for ( $i = 1; $i <= $max_attempts; $i++ ) {
			$result = $attempt();
			$failed = is_wp_error( $result ) || false === $result;

			if ( ! $failed ) {
				return $result;
			}

			if ( $i < $max_attempts ) {
				$this->logger->warning( "Attempt {$i} to {$action_description} for {$plugin_slug} failed, retrying..." );

				// Some hosts (e.g. sandboxed free tiers) disable sleep() entirely —
				// a disabled function is a fatal error, not a WP_Error, so this must
				// not be called unconditionally.
				if ( function_exists( 'sleep' ) ) {
					sleep( 1 );
				}
			}
		}

		return $result;
	}

	private function installActivatePlugin( $plugin ) {
		$pg          = explode( '/', $plugin );
		$plugin_file = WP_PLUGIN_DIR . '/' . $plugin;
		$results     = array();
		$this->logger->info( 'Starting installation for plugin: ' . $pg[0], [ 'start_time' => true ] );

		// Already on disk (any install method) — just make sure it's active and stop
		// here, regardless of which branch below would otherwise install it. Without
		// this running first, the `companion-elementor` special case below never got
		// a chance to notice the plugin was already installed and active, and instead
		// unconditionally tried (and failed) to reinstall it via `tgda_install_companion_elementor`.
		if ( file_exists( $plugin_file ) ) {
			$plugin_data = get_plugin_data( $plugin_file );

			$this->logger->info( $plugin_data['Name'] . ' already installed, checking activation status.' );

			if ( is_plugin_active( $plugin ) ) {
				$this->logger->info( $plugin_data['Name'] . ' already active, skipping activation.', [ 'end_time' => true ] );

				$results[ $pg[0] ] = array(
					'status'  => 'success',
					'message' => $plugin_data['Name'] . ' already activated.',
				);
				return $results;
			}

			$this->logger->info( 'Activating plugin: ' . $plugin_data['Name'] );
			$result = activate_plugin( $plugin );

			if ( is_wp_error( $result ) ) {
				$this->logger->warning( 'Failed to activate plugin ' . $plugin . ': ' . $result->get_error_message(), [ 'end_time' => true ] );

				$results[ $pg[0] ] = array(
					'status'  => 'error',
					'message' => $result->get_error_message(),
				);
				return $results;
			}

			$this->logger->info( $plugin_data['Name'] . ' successfully activated.', [ 'end_time' => true ] );

			$results[ $pg[0] ] = array(
				'status'  => 'success',
				'message' => $plugin_data['Name'] . ' activated.',
			);

			return $results;
		}

		if ( 'companion-elementor/companion-elementor.php' === $plugin ) {
			$response = apply_filters( 'tgda_install_companion_elementor', 'companion-elementor/companion-elementor.php' );
			if ( is_array( $response ) ) {
				if ( isset( $response['success'] ) && $response['success'] ) {
					$this->logger->info( 'Companion Elementor installed and activated.', [ 'end_time' => true ] );
					$results[ $pg[0] ] = array(
						'status'  => 'success',
						'message' => __( 'Companion Elementor installed and activated.', 'themegrill-demo-importer' ),
					);
				} else {
					$this->logger->warning( 'Failed to install plugin ' . $pg[0] . ': ' . $response['message'], [ 'end_time' => true ] );
					$results[ $pg[0] ] = array(
						'status'  => 'error',
						'message' => $response['message'],
					);
				}
			} else {
				$this->logger->warning( 'Failed to install Companion Elementor.', [ 'end_time' => true ] );
				$results[ $pg[0] ] = array(
					'status'  => 'error',
					'message' => 'Failed to install Companion Elementor.',
				);
			}
		} else {
			$api = $this->withRetry(
				function () use ( $pg ) {
					return plugins_api(
						'plugin_information',
						array(
							'slug' => sanitize_key( wp_unslash( $pg[0] ) ),
						)
					);
				},
				$pg[0],
				'fetch plugin info from WordPress.org'
			);
			if ( is_wp_error( $api ) ) {
				$this->logger->warning( 'Failed to fetch plugin info from from WordPress.org for ' . $pg[0] . ': ' . $api->get_error_message(), [ 'end_time' => true ] );

				$results[ $pg[0] ] = array(
					'status'  => 'error',
					'message' => $api->get_error_message(),
				);

				return $results;

			}

			$skin      = new \WP_Ajax_Upgrader_Skin();
			$upgrader  = new \Plugin_Upgrader( $skin );
			$installed = $this->withRetry(
				function () use ( $upgrader, $api ) {
					return $upgrader->install( $api->download_link );
				},
				$pg[0],
				'download/install plugin from WordPress.org'
			);

			if ( is_wp_error( $installed ) ) {
				$this->logger->warning( 'Failed to install plugin ' . $pg[0] . ': ' . $installed->get_error_message(), [ 'end_time' => true ] );

				$results[ $pg[0] ] = array(
					'status'  => 'error',
					'message' => $installed->get_error_message(),
				);
				return $results;
			}

			if ( false === $installed ) {
				$this->logger->warning( 'Failed to install plugin ' . $pg[0] . ': filesystem error or download failed.', [ 'end_time' => true ] );

				$results[ $pg[0] ] = array(
					'status'  => 'error',
					'message' => __( 'Plugin installation failed (filesystem or download error).', 'themegrill-demo-importer' ),
				);
				return $results;
			}

			// plugin_info() scans the actual installed directory so it returns the
			// correct basename even when the main file doesn't follow slug/slug.php
			// (e.g. learning-management-system/lms.php for Masteriyo).
			$plugin_file = $upgrader->plugin_info();
			if ( ! $plugin_file ) {
				$install_status = install_plugin_install_status( $api );
				$plugin_file    = $install_status['file'] ?? '';
			}

			if ( $plugin_file && is_plugin_inactive( $plugin_file ) ) {
				$this->logger->info( 'Activating plugin: ' . $api->name );

				$result = activate_plugin( $plugin_file );

				if ( is_wp_error( $result ) ) {
					$this->logger->warning( 'Failed to activate plugin after install ' . $pg[0] . ': ' . $result->get_error_message(), [ 'end_time' => true ] );

					$results[ $pg[0] ] = array(
						'status'  => 'error',
						'message' => $result->get_error_message(),
					);
					return $results;

				}
			}

			$this->logger->info( $api->name . ' installed and activated.', [ 'end_time' => true ] );

			$results[ $pg[0] ] = array(
				'status'  => 'success',
				/* translators: %s Plugin name */
				'message' => sprintf( __( '%s installed and activated.', 'themegrill-demo-importer' ), $api->name ),
			);
		}
		return $results;
	}
}
