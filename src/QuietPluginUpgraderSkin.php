<?php
/**
 * Quiet plugin upgrader skin for ThemeGrill Starter Templates.
 *
 * @package ThemeGrill\StarterTemplates
 * @since   2.0.0
 */
namespace ThemeGrill\StarterTemplates;

class QuietPluginUpgraderSkin extends \WP_Upgrader_Skin {
	/**
	 * Empty header method.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function header() {}

	/**
	 * Empty footer method.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function footer() {}

	/**
	 * Handle errors during plugin upgrade.
	 *
	 * @since 2.0.0
	 * @param mixed $errors The errors to handle.
	 * @return void
	 */
	public function error( $errors ) {
		if ( is_wp_error( $errors ) ) {
			$this->errors = $errors;
		}
	}
	/**
	 * Empty feedback method.
	 *
	 * @since 2.0.0
	 * @param mixed $feedback The feedback message.
	 * @param mixed ...$args Additional arguments.
	 * @return void
	 */
	public function feedback( $feedback, ...$args ) {}
}
