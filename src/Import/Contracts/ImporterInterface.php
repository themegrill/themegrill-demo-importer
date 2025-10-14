<?php
/**
 * Importer interface for ThemeGrill Starter Templates.
 *
 * @package ThemeGrill\StarterTemplates\Import\Contracts
 * @since   2.0.0
 */

namespace ThemeGrill\StarterTemplates\Import\Contracts;

interface ImporterInterface {

	/**
	 * Import data.
	 *
	 * @since 2.0.0
	 * @param array $data The data to import.
	 * @return mixed The import result.
	 */
	public function import( array $data );
}
