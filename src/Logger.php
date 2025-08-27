<?php

namespace ThemeGrill\Demo\Importer;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Log\InvalidArgumentException;

class Logger implements LoggerInterface {

	private string $logFile;
	private array $logLevels;
	private string $dateFormat                = 'Y-m-d H:i:s';
	private static ?LoggerInterface $instance = null;

	private function __construct() {
		$this->logFile = WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'tdi-debug.log';

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

		add_action( 'shutdown', [ $this, 'flushLogs' ], 99 );
	}

	private function __clone() {}

	public function __wakeup() {
		throw new \Exception( 'Cannot unserialize singleton' );
	}

	public static function getInstance() {
		if ( null === self::$instance ) {
			self::$instance = new static();
		}
		return self::$instance;
	}

	public function emergency( $message, array $context = [] ): void {
		$this->log( LogLevel::EMERGENCY, $message, $context );
	}

	public function alert( $message, array $context = [] ): void {
		$this->log( LogLevel::ALERT, $message, $context );
	}

	public function critical( $message, array $context = [] ): void {
		$this->log( LogLevel::CRITICAL, $message, $context );
	}

	public function error( $message, array $context = [] ): void {
		$this->log( LogLevel::ERROR, $message, $context );
	}

	public function warning( $message, array $context = [] ): void {
		$this->log( LogLevel::WARNING, $message, $context );
	}
	public function notice( $message, array $context = [] ): void {
		$this->log( LogLevel::NOTICE, $message, $context );
	}

	public function info( $message, array $context = [] ): void {
		$this->log( LogLevel::INFO, $message, $context );
	}

	public function debug( $message, array $context = [] ): void {
		$this->log( LogLevel::DEBUG, $message, $context );
	}

	public function log( $level, $message, array $context = [] ): void {
		if ( ! isset( $this->logLevels[ $level ] ) ) {
			throw new InvalidArgumentException( esc_html( "Invalid log level: {$level}" ) );
		}

		$interpolatedMessage = $this->interpolate( (string) $message, $context );

		$timestamp  = gmdate( $this->dateFormat );
		$levelUpper = strtoupper( $level );
		$logEntry   = "[{$timestamp}] {$levelUpper}: {$interpolatedMessage}" . PHP_EOL;

		$this->writeToFile( $logEntry );
	}

	private function interpolate( string $message, array $context ): string {
		$replace = [];
		foreach ( $context as $key => $val ) {
			if ( ! is_array( $val ) && ( ! is_object( $val ) || method_exists( $val, '__toString' ) ) ) {
				$replace[ '{' . $key . '}' ] = $val;
			} elseif ( is_array( $val ) || is_object( $val ) ) {
				$replace[ '{' . $key . '}' ] = wp_json_encode( $val );
			}
		}

		return strtr( $message, $replace );
	}

	private function writeToFile( string $logEntry ): void {
		@file_put_contents( $this->logFile, $logEntry, FILE_APPEND | LOCK_EX ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents, WordPress.PHP.NoSilencedErrors.Discouraged
	}

	public function setLogFile( string $logFile ): void {
		$this->logFile = $logFile;
	}

	public function getLogFile(): string {
		return $this->logFile;
	}

	public function flushLogs() {}
}
