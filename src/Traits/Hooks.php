<?php

namespace ThemeGrill\StarterTemplates\Traits;

trait Hooks {

	/**
	 * Add a callback to an action or filter hook.
	 *
	 * @param string $type 'action' or 'filter'
	 * @param string $hook_name The name of the hook to add the callback to.
	 * @param callable|string|array $callback The callback to be run.
	 * @param int $priority Optional. Default 10.
	 * @param int $accepted_args Optional. Default 1.
	 * @return bool Always returns true.
	 */
	private function addHook( string $type, string $hook_name, callable|string|array $callback, int $priority = 10, int $accepted_args = 1 ): bool {
		$function = "add_{$type}";
		return call_user_func_array( $function, [ $hook_name, $callback, $priority, $accepted_args ] );
	}

	/**
	 * Add a callback to an action hook.
	 *
	 * @param string $hook_name The name of the hook to add the callback to.
	 * @param callable|string|array $callback The callback to be run.
	 * @param int $priority Optional. Default 10.
	 * @param int $accepted_args Optional. Default 1.
	 * @return bool Always returns true.
	 */
	public function addAction( string $hook_name, callable|string|array $callback, int $priority = 10, int $accepted_args = 1 ): bool {
		return $this->addHook( 'action', $hook_name, $callback, $priority, $accepted_args );
	}

	/**
	 * Add a callback to an filter hook.
	 *
	 * @param string $hook_name The name of the hook to add the callback to.
	 * @param callable|string|array $callback The callback to be run.
	 * @param int $priority Optional. Default 10.
	 * @param int $accepted_args Optional. Default 1.
	 * @return bool Always returns true.
	 */
	public function addFilter( string $hook_name, callable|string|array $callback, int $priority = 10, int $accepted_args = 1 ): bool {
		return $this->addHook( 'filter', $hook_name, $callback, $priority, $accepted_args );
	}

	/**
	 * Removes a callback function from a filter or action hook.
	 *
	 * @param string $type 'filter' or 'action'
	 * @param string $hook_name The hook name.
	 * @param callable|string|array $callback The callback to be removed.
	 * @param int $priority Optional. Default 10.
	 * @return bool Whether the function existed before it was removed.
	 */
	private function removeHook( string $type, string $hook_name, $callback, int $priority = 10 ): bool {
		$function = "remove_{$type}";
		return $function( $hook_name, $callback, $priority );
	}

	/**
	 * Removes a callback function from a filter hook.
	 *
	 * @param string $hook_name The hook name.
	 * @param callable|string|array $callback The callback to be removed.
	 * @param int $priority Optional. Default 10.
	 * @return bool Whether the function existed before it was removed.
	 */
	public function removeFilter( string $hook_name, callable|string|array $callback, int $priority = 10 ): bool {
		return $this->removeHook( 'filter', $hook_name, $callback, $priority );
	}

	/**
	 * Removes a callback function from a action hook.
	 *
	 * @param string $type 'filter' or 'action'
	 * @param string $hook_name The hook name.
	 * @param callable|string|array $callback The callback to be removed.
	 * @param int $priority Optional. Default 10.
	 * @return bool Whether the function existed before it was removed.
	 */
	public function removeAction( string $hook_name, callable|string|array $callback, int $priority = 10 ): bool {
		return $this->removeHook( 'action', $hook_name, $callback, $priority );
	}

	/**
	 * Do action with arguments.
	 *
	 * @param string $hook_name The action hook to execute.
	 * @param mixed ...$args Additional arguments.
	 * @return void
	 */
	public function doAction( string $hook_name, ...$args ): void {
		do_action_ref_array( $hook_name, $args );
	}

	/**
	 * Apply filters with arguments.
	 *
	 * @param string $hook_name The filter hook to apply.
	 * @param mixed $value The value to filter.
	 * @param mixed ...$args Additional arguments.
	 * @return mixed The filtered value.
	 */
	public function applyFilters( string $hook_name, mixed $value, ...$args ) {
		return apply_filters_ref_array( $hook_name, array_merge( [ $value ], $args ) );
	}
}
