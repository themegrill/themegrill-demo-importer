<?php
/**
 * Abstract REST API controller class for ThemeGrill Starter Templates.
 *
 * @package ThemeGrill\StarterTemplates\Controllers\V1
 * @since   2.0.0
 */

namespace ThemeGrill\StarterTemplates\Controllers\V1;

use ThemeGrill\StarterTemplates\Logger;

/**
 * Abstract controller class.
 */
abstract class Controller extends \WP_REST_Controller {

	/** {@inheritDoc} */
	protected $namespace = 'themegrill-starter-templates/v1';

	/**
	 * Constructor.
	 *
	 * @since 2.0.0
	 * @param Logger $logger The logger instance.
	 */
	public function __construct( protected Logger $logger ) {}
}
