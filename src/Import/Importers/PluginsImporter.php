<?php
/**
 * PluginsImporter.
 */

namespace ThemeGrill\StarterTemplates\Import\Importers;

use Psr\Log\LoggerInterface;
use ThemeGrill\StarterTemplates\Import\Contracts\ImporterInterface;
use ThemeGrill\StarterTemplates\QuietPluginUpgraderSkin;
use ThemeGrill\StarterTemplates\Traits\Hooks;

/**
 * PluginsImporter class.
 */
class PluginsImporter implements ImporterInterface {

	use Hooks;

	/**
	 * Constructor.
	 *
	 * @param LoggerInterface $logger
	 */
	public function __construct( private LoggerInterface $logger ) {}

	/**
	 * {@inheritDoc}
	 * @return array
	 */
	public function import( array $data ) {

		$this->logger->info( 'Starting plugin import process...' );

		$this->doAction( 'themegrill:starter-templates:import-plugins-start', $data );

		require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/misc.php';
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

		$result = [];

		foreach ( $data as $pluginBasename ) {
			$this->doAction( 'themegrill:starter-templates:import-plugin', $pluginBasename );

			if ( $this->isActivated( $pluginBasename ) ) {
				$this->logger->info( "Plugin $pluginBasename is already activated..." );
				$result[ $pluginBasename ] = true;
				continue;
			}
			if ( $this->isInstalled( $pluginBasename ) ) {
				$this->logger->info( "Plugin $pluginBasename is already installed..." );
				$result[ $pluginBasename ] = $this->activatePlugin( $pluginBasename );
				continue;
			}
			$result[ $pluginBasename ] = $this->installPlugin( $pluginBasename );

			$this->doAction( 'themegrill:starter-templates:plugin-imported', $pluginBasename, $result[ $pluginBasename ] );
		}

		$this->doAction( 'themegrill:starter-templates:plugins-import-complete', $result, $data );

		return $result;
	}

	/**
	 * Get plugin info from the WordPress API.
	 *
	 * @param string $pluginBasename
	 * @return object|\WP_Error
	 */
	private function getPluginInfo( string $pluginBasename ) {
		return plugins_api(
			'plugin_information',
			[
				'slug'   => dirname( $pluginBasename ),
				'fields' => [
					'download_link' => true,
					'version'       => true,
					'requires'      => true,
					'tested'        => true,
				],
			]
		);
	}

	/**
	 * Install a plugin.
	 *
	 * @param string $pluginBasename
	 * @return bool
	 * @throws \Exception
	 */
	private function installPlugin( string $pluginBasename ) {
		$info = $this->getPluginInfo( $pluginBasename );
		if ( is_wp_error( $info ) ) {
			$this->logger->error(
				"Failed to get plugin info for $pluginBasename: {$info->get_error_message()}"
			);
			return false;
		}

		$upgrader = new \Plugin_Upgrader( new QuietPluginUpgraderSkin() );

		$result = $upgrader->install( $info->download_link );

		if ( is_wp_error( $result ) ) {
			$this->logger->error(
				"Failed to install plugin $pluginBasename: {$result->get_error_message()}"
			);
			return false;
		}

		if ( ! $result ) {
			throw new \Exception( 'Installation failed for unknown reason' );
		}

		$this->logger->info( "Successfully installed: {$pluginBasename}" );
		$this->activatePlugin( $pluginBasename );

		return true;
	}

	/**
	 * Activate a plugin.
	 *
	 * @param string $pluginBasename
	 * @return bool
	 */
	private function activatePlugin( string $pluginBasename ) {
		$this->logger->info( "Activating plugin: $pluginBasename" );
		$activationResult = activate_plugin( $pluginBasename );
		if ( is_wp_error( $activationResult ) ) {
			$this->logger->error(
				"Failed to activate plugin $pluginBasename: {$activationResult->get_error_message()}"
			);
			return false;
		}
		$this->logger->info( "Successfully activated: {$pluginBasename}" );
		return true;
	}

	/**
	 * Check if a plugin is installed.
	 *
	 * @param string $pluginBasename
	 * @return bool
	 */
	private function isInstalled( string $pluginBasename ) {
		return file_exists( WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . $pluginBasename );
	}

	/**
	 * Check if a plugin is activated.
	 *
	 * @param string $pluginBasename
	 * @return bool
	 */
	private function isActivated( string $pluginBasename ) {
		return is_plugin_active( $pluginBasename );
	}
}
