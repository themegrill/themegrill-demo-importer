<?php
/**
 * Upgrade API: TG_Demo_Pack_Upgrader class
 *
 * Core class used for upgrading/installing demo packs.
 *
 * It is designed to upgrade/install demo from a local zip, remote zip URL,
 * or uploaded zip file.
 *
 * @since 1.5.0
 * @see WP_Upgrader
 * @package ThemeGrill_Demo_Importer\Class
 */
class TG_Demo_Pack_Upgrader extends WP_Upgrader {

	/**
	 * Result of the demo pack upgrade.
	 *
	 * @var array|WP_Error $result
	 * @see WP_Upgrader::$result
	 */
	public $result;

	/**
	 * Whether a bulk upgrade/installation is being performed.
	 *
	 * @var bool $bulk
	 */
	public $bulk = false;

	/**
	 * Initialize the install strings.
	 */
	public function install_strings() {
		$this->strings['no_package'] = __( 'Install package not available.', 'themegrill-demo-importer' );
		/* translators: %s: package URL */
		$this->strings['downloading_package'] = __( 'Downloading install package from <span class="code">%s</span>&#8230;', 'themegrill-demo-importer' );
		$this->strings['unpack_package']      = __( 'Unpacking the package&#8230;', 'themegrill-demo-importer' );
		$this->strings['remove_old']          = __( 'Removing the old version of the demo&#8230;', 'themegrill-demo-importer' );
		$this->strings['remove_old_failed']   = __( 'Could not remove the old demo.', 'themegrill-demo-importer' );
		$this->strings['installing_package']  = __( 'Installing the demo&#8230;', 'themegrill-demo-importer' );
		$this->strings['no_files']            = __( 'The demo contains no files.', 'themegrill-demo-importer' );
		$this->strings['process_failed']      = __( 'Demo install failed.', 'themegrill-demo-importer' );
		$this->strings['process_success']     = __( 'Demo installed successfully.', 'themegrill-demo-importer' );
	}

	/**
	 * Install a demo package.
	 *
	 * @param string $package The full local path or URI of the package.
	 * @param array  $args {
	 *     Optional. Other arguments for installing a demo package. Default empty array.
	 *
	 *     @type bool $clear_update_cache Whether to clear the updates cache if successful.
	 *                                    Default true.
	 * }
	 *
	 * @return bool|WP_Error True if the install was successful, false or a WP_Error object otherwise.
	 */
	public function install( $package, $args = array() ) {

		$defaults    = array(
			'clear_update_cache' => true,
		);
		$parsed_args = wp_parse_args( $args, $defaults );

		$this->init();
		$this->install_strings();

		add_filter( 'upgrader_source_selection', array( $this, 'check_package' ) );

		$this->run(
			array(
				'package'           => $package,
				'destination'       => TGDM_DEMO_DIR,
				'clear_destination' => true, // Do overwrite files.
				'clear_working'     => true,
				'hook_extra'        => array(
					'type'   => 'demo',
					'action' => 'install',
				),
			)
		);

		remove_filter( 'upgrader_source_selection', array( $this, 'check_package' ) );

		if ( ! $this->result || is_wp_error( $this->result ) ) {
			return $this->result;
		}

		return true;
	}

	/**
	 * Check a source package to be sure it contains a demo.
	 *
	 * Hooked to the {@see 'upgrader_source_selection'} filter by
	 * TG_Demo_Upgrader::install().
	 *
	 * @global WP_Filesystem_Base $wp_filesystem Subclass
	 *
	 * @param string $source The full path to the package source.
	 * @return string|WP_Error The source as passed, or a WP_Error object
	 *                         if no demos were found.
	 */
	public function check_package( $source ) {
		global $wp_filesystem;

		if ( is_wp_error( $source ) ) {
			return $source;
		}

		// Check the folder contains a valid demo.
		$working_directory = str_replace( $wp_filesystem->wp_content_dir(), trailingslashit( WP_CONTENT_DIR ), $source );
		if ( ! is_dir( $working_directory ) ) { // Sanity check, if the above fails, let's not prevent installation.
			return $source;
		}

		// Check the folder contains at least 1 valid demo.
		if ( ! file_exists( $working_directory . 'screenshot.jpg' ) ) {
			return new WP_Error( 'incompatible_archive_no_demos', $this->strings['incompatible_archive'], __( 'No valid demos were found.', 'themegrill-demo-importer' ) );
		}

		return $source;
	}
}
