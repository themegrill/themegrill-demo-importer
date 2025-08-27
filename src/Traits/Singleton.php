<?php

namespace ThemeGrill\Demo\Importer\Traits;

trait Singleton {
	/**
	 * Instance storage
	 */
	protected static $instance = null;

	/**
	 * Get singleton instance
	 *
	 * @return static
	 */
	public static function instance() {
		if ( is_null( static::$instance ) ) {
			static::$instance = new static();
		}
		return static::$instance;
	}

	/**
	 * Prevent direct instantiation
	 */
	private function __construct() {
		$this->init();
	}

	/**
	 * Prevent cloning
	 */
	private function __clone() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Cheatin&#8217; huh?', 'themegrill-demo-importer' ), '1.4' );
	}

	/**
	 * Prevent unserialization
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Cheatin&#8217; huh?', 'themegrill-demo-importer' ), '1.4' );
	}

	/**
	 * Initialize the class - override in implementing classes
	 */
	protected function init() {
		// Override in child classes
	}
}
