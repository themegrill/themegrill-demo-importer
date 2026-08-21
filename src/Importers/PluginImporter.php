<?php

namespace ThemeGrill\Demo\Importer\Importers;

use Exception;
use ThemeGrill\Demo\Importer\Helpers\DemoRequirements;
use ThemeGrill\Demo\Importer\Logger;

class PluginImporter {

	/**
	 * Companion Elementor main plugin file.
	 */
	const COMPANION_ELEMENTOR = 'companion-elementor/companion-elementor.php';

	/**
	 * Packaged ZIP for Companion Elementor (not available on WordPress.org).
	 */
	const COMPANION_ELEMENTOR_ZIP = 'https://github.com/themegrill/themegrill-demo-pack/raw/master/packages/companion-elementor/companion-elementor.zip';

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

	public function installPlugins( $plugins, $demo_config = array() ) {
		/**
		 * `get_filesystem_method()`'s ownership probe (comparing `fileowner()` on a
		 * freshly written temp file against a core WP file) is unreliable on a lot of
		 * Windows/local dev stacks (e.g. Local by Flywheel), so it falls back to
		 * `ftpext`/`ftpsockets` even though direct writes work fine. In a REST/AJAX
		 * request there is no page to render an FTP-credentials form on, so
		 * `request_filesystem_credentials()` just returns false and `WP_Upgrader::run()`
		 * bails with a bare `false` - no WP_Error, no message - which is why a plugin
		 * that actually needs installing (rather than just activating) can fail with
		 * no usable reason.
		 *
		 * This only ever runs from an authenticated action that already required the
		 * `install_plugins` capability, so forcing the direct method here is safe: it
		 * either fixes the false-positive above, or - on a host where direct writes
		 * genuinely aren't possible - turns that silent failure into a real WP_Error
		 * we can log and surface instead.
		 */
		add_filter( 'filesystem_method', array( __CLASS__, 'force_direct_filesystem_method' ) );

		try {
			$results = array_map(
				function ( $plugin ) use ( $demo_config ) {
					return $this->installActivatePlugin( $plugin, $demo_config );
				},
				$plugins
			);
		} finally {
			remove_filter( 'filesystem_method', array( __CLASS__, 'force_direct_filesystem_method' ) );
		}

		return $results;
	}

	/**
	 * `filesystem_method` filter callback - see installPlugins().
	 *
	 * @return string
	 */
	public static function force_direct_filesystem_method() {
		return 'direct';
	}

	/**
	 * Message for the one `WP_Upgrader::run()` failure mode that reaches us as a
	 * bare `false` instead of a `WP_Error`: the filesystem connection was refused.
	 * Since installPlugins() already forces the direct method, seeing this here
	 * means direct writes to wp-content genuinely aren't available on this host.
	 *
	 * @return string
	 */
	private static function filesystem_unavailable_message() {
		return __( 'Plugin installation failed: WordPress could not write to the plugins directory (no direct filesystem access, and no FTP/SSH credentials configured).', 'themegrill-demo-importer' );
	}

	/**
	 * Retry a WordPress.org network call once on failure.
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

				if ( function_exists( 'sleep' ) ) {
					sleep( 1 );
				}
			}
		}

		return $result;
	}

	/**
	 * Whether this plugin entry is Companion Elementor.
	 *
	 * @param string $plugin Plugin basename from the demo config.
	 * @return bool
	 */
	private function is_companion_elementor( $plugin ) {
		$plugin = ltrim( str_replace( '\\', '/', (string) $plugin ), '/' );
		$slug   = explode( '/', $plugin )[0] ?? '';

		return 'companion-elementor' === $slug || self::COMPANION_ELEMENTOR === $plugin;
	}

	/**
	 * User Registration's membership tables only ever get created once the
	 * plugin is confirmed active (whether it already was, or we just activated
	 * it) — call this right after that point so later import steps (which run
	 * as separate requests) never hit a table that doesn't exist yet.
	 *
	 * Also re-applies the `user-registration-membership` feature flag here as a
	 * safety net: ImportService already sets it before activation runs, but this
	 * covers the flag being missing if activation is ever reached another way.
	 *
	 * @param string $slug        Plugin slug, e.g. 'user-registration'.
	 * @param array  $demo_config Demo config.
	 */
	private function maybe_create_urm_tables( $slug, $demo_config = array() ) {
		if ( 'user-registration' === $slug ) {
			DemoRequirements::maybe_enable_urm_membership_feature( $demo_config );
			DemoRequirements::ensure_urm_membership_tables();
		}
	}

	private function installActivatePlugin( $plugin, $demo_config = array() ) {
		// Companion Elementor is not on WordPress.org — handle it separately.
		if ( $this->is_companion_elementor( $plugin ) ) {
			return $this->installActivateCompanionElementor();
		}

		$pg          = explode( '/', $plugin );
		$plugin_file = WP_PLUGIN_DIR . '/' . $plugin;
		$results     = array();
		$this->logger->info( 'Starting installation for plugin: ' . $pg[0], [ 'start_time' => true ] );

		if ( is_file( $plugin_file ) ) {
			$plugin_data = get_plugin_data( $plugin_file );

			$this->logger->info( $plugin_data['Name'] . ' already installed, checking activation status.' );

			if ( is_plugin_active( $plugin ) ) {
				$this->logger->info( $plugin_data['Name'] . ' already active, skipping activation.', [ 'end_time' => true ] );

				$this->maybe_create_urm_tables( $pg[0], $demo_config );

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

			$this->maybe_create_urm_tables( $pg[0], $demo_config );

			$results[ $pg[0] ] = array(
				'status'  => 'success',
				'message' => $plugin_data['Name'] . ' activated.',
			);

			return $results;
		}

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
			$message = self::filesystem_unavailable_message();
			$this->logger->warning( 'Failed to install plugin ' . $pg[0] . ': ' . $message, [ 'end_time' => true ] );

			$results[ $pg[0] ] = array(
				'status'  => 'error',
				'message' => $message,
			);
			return $results;
		}

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

		$this->maybe_create_urm_tables( $pg[0], $demo_config );

		$results[ $pg[0] ] = array(
			'status'  => 'success',
			/* translators: %s Plugin name */
			'message' => sprintf( __( '%s installed and activated.', 'themegrill-demo-importer' ), $api->name ),
		);

		return $results;
	}

	/**
	 * Install/activate Companion Elementor from ThemeGrill's packaged GitHub ZIP.
	 *
	 * If it is already installed and active, returns success immediately so demo
	 * import does not fail for a plugin that is not on WordPress.org.
	 *
	 * @return array
	 */
	private function installActivateCompanionElementor() {
		$slug    = 'companion-elementor';
		$plugin  = self::COMPANION_ELEMENTOR;
		$results = array();

		$this->logger->info( 'Starting installation for plugin: ' . $slug, [ 'start_time' => true ] );

		/**
		 * Optional override for hosts that ship their own Companion Elementor installer.
		 *
		 * @param null|array $response Null to use the default ZIP install, or
		 *                             array{ success: bool, message?: string }.
		 */
		$override = apply_filters( 'tgda_install_companion_elementor', null );
		if ( is_array( $override ) ) {
			if ( ! empty( $override['success'] ) ) {
				$this->logger->info( 'Companion Elementor installed and activated.', [ 'end_time' => true ] );
				$results[ $slug ] = array(
					'status'  => 'success',
					'message' => __( 'Companion Elementor installed and activated.', 'themegrill-demo-importer' ),
				);
			} else {
				$message = isset( $override['message'] ) ? (string) $override['message'] : __( 'Failed to install Companion Elementor.', 'themegrill-demo-importer' );
				$this->logger->warning( 'Failed to install plugin ' . $slug . ': ' . $message, [ 'end_time' => true ] );
				$results[ $slug ] = array(
					'status'  => 'error',
					'message' => $message,
				);
			}
			return $results;
		}

		// Already installed — activate if needed, never hit wordpress.org.
		if ( is_file( WP_PLUGIN_DIR . '/' . $plugin ) ) {
			$plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin );

			if ( is_plugin_active( $plugin ) ) {
				$this->logger->info( $plugin_data['Name'] . ' already active, skipping activation.', [ 'end_time' => true ] );
				$results[ $slug ] = array(
					'status'  => 'success',
					'message' => $plugin_data['Name'] . ' already activated.',
				);
				return $results;
			}

			$result = activate_plugin( $plugin );
			if ( is_wp_error( $result ) ) {
				$this->logger->warning( 'Failed to activate Companion Elementor: ' . $result->get_error_message(), [ 'end_time' => true ] );
				$results[ $slug ] = array(
					'status'  => 'error',
					'message' => $result->get_error_message(),
				);
				return $results;
			}

			$this->logger->info( $plugin_data['Name'] . ' activated.', [ 'end_time' => true ] );
			$results[ $slug ] = array(
				'status'  => 'success',
				'message' => $plugin_data['Name'] . ' activated.',
			);
			return $results;
		}

		/**
		 * Filter the Companion Elementor ZIP download URL.
		 *
		 * @param string $zip_url ZIP URL.
		 */
		$zip_url = apply_filters( 'themegrill_demo_importer_companion_elementor_zip', self::COMPANION_ELEMENTOR_ZIP );

		$this->logger->info( 'Installing Companion Elementor from packaged ZIP.' );

		$skin      = new \WP_Ajax_Upgrader_Skin();
		$upgrader  = new \Plugin_Upgrader( $skin );
		$installed = $this->withRetry(
			function () use ( $upgrader, $zip_url ) {
				return $upgrader->install( $zip_url );
			},
			$slug,
			'download/install Companion Elementor ZIP'
		);

		if ( is_wp_error( $installed ) ) {
			$this->logger->warning( 'Failed to install Companion Elementor: ' . $installed->get_error_message(), [ 'end_time' => true ] );
			$results[ $slug ] = array(
				'status'  => 'error',
				'message' => $installed->get_error_message(),
			);
			return $results;
		}

		if ( false === $installed ) {
			$skin_errors = method_exists( $skin, 'get_errors' ) ? $skin->get_errors() : null;
			$message     = ( is_wp_error( $skin_errors ) && $skin_errors->has_errors() )
				? $skin_errors->get_error_message()
				: self::filesystem_unavailable_message();

			$this->logger->warning( 'Failed to install Companion Elementor: ' . $message, [ 'end_time' => true ] );
			$results[ $slug ] = array(
				'status'  => 'error',
				'message' => $message,
			);
			return $results;
		}

		$plugin_file = $upgrader->plugin_info();
		if ( ! $plugin_file ) {
			$plugin_file = $plugin;
		}

		if ( $plugin_file && is_file( WP_PLUGIN_DIR . '/' . $plugin_file ) && is_plugin_inactive( $plugin_file ) ) {
			$result = activate_plugin( $plugin_file );
			if ( is_wp_error( $result ) ) {
				$this->logger->warning( 'Failed to activate Companion Elementor: ' . $result->get_error_message(), [ 'end_time' => true ] );
				$results[ $slug ] = array(
					'status'  => 'error',
					'message' => $result->get_error_message(),
				);
				return $results;
			}
		}

		$this->logger->info( 'Companion Elementor installed and activated.', [ 'end_time' => true ] );
		$results[ $slug ] = array(
			'status'  => 'success',
			'message' => __( 'Companion Elementor installed and activated.', 'themegrill-demo-importer' ),
		);

		return $results;
	}
}
