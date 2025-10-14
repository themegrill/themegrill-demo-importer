<?php
/**
 * Activation service class for ThemeGrill Starter Templates.
 *
 * @package ThemeGrill\StarterTemplates\Services
 * @since   2.0.0
 */

namespace ThemeGrill\StarterTemplates\Services;

use ThemeGrill\StarterTemplates\Traits\Hooks;

class ActivationService {

	use Hooks;

	/**
	 * Handle plugin activation.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public static function activate() {
		$instance = new self();

		$instance->doAction( 'themegrill:starter-templates:activate' );

		$instance->doAction( 'themegrill:starter-templates:activated' );
	}
}
