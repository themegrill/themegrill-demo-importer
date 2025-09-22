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

	private function installActivatePlugin( $plugin ) {
		$pg          = explode( '/', $plugin );
		$plugin_file = WP_PLUGIN_DIR . '/' . $plugin;
		$results     = array();
		$this->logger->info( 'Starting installation for plugin: ' . $pg[0], [ 'start_time' => true ] );

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
				}

				$this->logger->info( $plugin_data['Name'] . ' successfully activated.', [ 'end_time' => true ] );

				$results[ $pg[0] ] = array(
					'status'  => 'success',
					'message' => $plugin_data['Name'] . ' activated.',
				);

				return $results;
			}

			$api = plugins_api(
				'plugin_information',
				array(
					'slug' => sanitize_key( wp_unslash( $pg[0] ) ),
				)
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
			$installed = $upgrader->install( $api->download_link );

			if ( is_wp_error( $installed ) ) {
				$this->logger->warning( 'Failed to install plugin ' . $pg[0] . ': ' . $installed->get_error_message(), [ 'end_time' => true ] );

				$results[ $pg[0] ] = array(
					'status'  => 'error',
					'message' => $installed->get_error_message(),
				);
				return $results;

			}

			$install_status = install_plugin_install_status( $api );

			if ( is_plugin_inactive( $install_status['file'] ) ) {
				$this->logger->info( 'Activating plugin: ' . $api->name );

				$result = activate_plugin( $install_status['file'] );

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
