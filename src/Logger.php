<?php
/**
 * Logger class implementing PSR-3 LoggerInterface for ThemeGrill Starter Templates.
 *
 * @package ThemeGrill\StarterTemplates
 * @since   2.0.0
 */

namespace ThemeGrill\StarterTemplates;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Log\InvalidArgumentException;
use ThemeGrill\StarterTemplates\Cache\TransientCache;

class Logger implements LoggerInterface {

	private array $logLevels;
	private string $dateFormat                = 'Y-m-d H:i:s';
	private static ?LoggerInterface $instance = null;

	/**
	 * Constructor.
	 *
	 * @since 2.0.0
	 */
	private function __construct() {
		$this->logLevels = [
			LogLevel::EMERGENCY => 0,
			LogLevel::ALERT     => 1,
			LogLevel::CRITICAL  => 2,
			LogLevel::ERROR     => 3,
			LogLevel::WARNING   => 4,
			LogLevel::NOTICE    => 5,
			LogLevel::INFO      => 6,
			LogLevel::DEBUG     => 7,
		];
	}

	/**
	 * Prevent cloning of the singleton instance.
	 *
	 * @since 2.0.0
	 */
	private function __clone() {}

	/**
	 * Prevent unserialization of the singleton instance.
	 *
	 * @since 2.0.0
	 * @throws \Exception Always throws an exception.
	 */
	public function __wakeup() {
		throw new \Exception( 'Cannot unserialize singleton' );
	}

	/**
	 * Get the singleton instance of the Logger.
	 *
	 * @since 2.0.0
	 * @return LoggerInterface The logger instance.
	 */
	public static function getInstance() {
		if ( null === self::$instance ) {
			self::$instance = new static();
		}
		return self::$instance;
	}

	/**
	 * Log an emergency level message.
	 *
	 * @since 2.0.0
	 * @param mixed $message The log message.
	 * @param array $context Additional context data.
	 * @return void
	 */
	public function emergency( $message, array $context = [] ): void {
		$this->log( LogLevel::EMERGENCY, $message, $context );
	}

	/**
	 * Log an alert level message.
	 *
	 * @since 2.0.0
	 * @param mixed $message The log message.
	 * @param array $context Additional context data.
	 * @return void
	 */
	public function alert( $message, array $context = [] ): void {
		$this->log( LogLevel::ALERT, $message, $context );
	}

	/**
	 * Log a critical level message.
	 *
	 * @since 2.0.0
	 * @param mixed $message The log message.
	 * @param array $context Additional context data.
	 * @return void
	 */
	public function critical( $message, array $context = [] ): void {
		$this->log( LogLevel::CRITICAL, $message, $context );
	}

	/**
	 * Log an error level message.
	 *
	 * @since 2.0.0
	 * @param mixed $message The log message.
	 * @param array $context Additional context data.
	 * @return void
	 */
	public function error( $message, array $context = [] ): void {
		$this->log( LogLevel::ERROR, $message, $context );
	}

	/**
	 * Log a warning level message.
	 *
	 * @since 2.0.0
	 * @param mixed $message The log message.
	 * @param array $context Additional context data.
	 * @return void
	 */
	public function warning( $message, array $context = [] ): void {
		$this->log( LogLevel::WARNING, $message, $context );
	}
	/**
	 * Log a notice level message.
	 *
	 * @since 2.0.0
	 * @param mixed $message The log message.
	 * @param array $context Additional context data.
	 * @return void
	 */
	public function notice( $message, array $context = [] ): void {
		$this->log( LogLevel::NOTICE, $message, $context );
	}

	/**
	 * Log an info level message.
	 *
	 * @since 2.0.0
	 * @param mixed $message The log message.
	 * @param array $context Additional context data.
	 * @return void
	 */
	public function info( $message, array $context = [] ): void {
		$this->log( LogLevel::INFO, $message, $context );
	}

	/**
	 * Log a debug level message.
	 *
	 * @since 2.0.0
	 * @param mixed $message The log message.
	 * @param array $context Additional context data.
	 * @return void
	 */
	public function debug( $message, array $context = [] ): void {
		$this->log( LogLevel::DEBUG, $message, $context );
	}

	/**
	 * Log a message with the specified level.
	 *
	 * @since 2.0.0
	 * @param mixed $level The log level.
	 * @param mixed $message The log message.
	 * @param array $context Additional context data.
	 * @return void
	 * @throws InvalidArgumentException If the log level is invalid.
	 */
	public function log( $level, $message, array $context = [] ): void {
		if ( ! isset( $this->logLevels[ $level ] ) ) {
			throw new InvalidArgumentException( esc_html( "Invalid log level: {$level}" ) );
		}

		$interpolatedMessage = $this->interpolate( (string) $message, $context );

		$timestamp  = gmdate( $this->dateFormat );
		$levelUpper = strtoupper( $level );
		$logEntry   = "[{$timestamp}] {$levelUpper}: {$interpolatedMessage}" . PHP_EOL;

		$this->write( $logEntry );
	}

	/**
	 * Interpolate context values into the message.
	 *
	 * @since 2.0.0
	 * @param string $message The message with placeholders.
	 * @param array $context The context data to interpolate.
	 * @return string The interpolated message.
	 */
	private function interpolate( string $message, array $context ): string {
		$replace = [];

		foreach ( $context as $key => $val ) {
			$placeholder = '{' . $key . '}';

			if ( is_scalar( $val ) || ( is_object( $val ) && method_exists( $val, '__toString' ) ) ) {
				$replace[ $placeholder ] = (string) $val;
			} elseif ( is_array( $val ) || is_object( $val ) ) {
				$jsonEncoded             = wp_json_encode( $val );
				$replace[ $placeholder ] = $jsonEncoded !== false ? $jsonEncoded : '[encoding_failed]';
			} else {
				$replace[ $placeholder ] = '[unsupported_type]';
			}
		}

		return strtr( $message, $replace );
	}


	/**
	 * Write a log entry to the transient storage.
	 *
	 * @since 2.0.0
	 * @param string $message The log message to write.
	 * @return bool True on success, false on failure.
	 */
	private function write( string $message ): bool {
		$existingLog = $this->getLog();
		$newLog      = $existingLog . $message;
		return TransientCache::put( 'log', $newLog, DAY_IN_SECONDS );
	}

	/**
	 * Get the current log content.
	 *
	 * @since 2.0.0
	 * @return string The log content.
	 */
	public function getLog(): string {
		$log = TransientCache::get( 'log', '' );
		return (string) $log;
	}

	/**
	 * Clear the log content.
	 *
	 * @since 2.0.0
	 * @return bool True on success, false on failure.
	 */
	public function truncateLog(): bool {
		return TransientCache::forget( 'log' );
	}
}
