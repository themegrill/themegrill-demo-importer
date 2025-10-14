<?php

namespace ThemeGrill\StarterTemplates\Services;

use ThemeGrill\StarterTemplates\Traits\Hooks;

class DeactivationService {

	use Hooks;

	public static function deactivate() {
		$instance = new self();

		$instance->doAction( 'themegrill:starter-templates:deactivate' );

		$instance->doAction( 'themegrill:starter-templates:deactivated' );
	}
}
