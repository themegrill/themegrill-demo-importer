<?php

namespace ThemeGrill\Demo\Importer;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Log\InvalidArgumentException;

class Logger implements LoggerInterface {

	private array $logLevels;
	private string $dateFormat                = 'Y-m-d H:i:s';
	private static ?LoggerInterface $instance = null;
	const LOG_TRANSIENT_KEY                   = 'themegrill_starter_templates_log';

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

		$this->write( $logEntry );
	}

	private function interpolate( string $message, array $context ): string {
		$replace = [];

		foreach ( $context as $key => $val ) {
			$placeholder = '{' . $key . '}';

			if ( is_scalar( $val ) || ( is_object( $val ) && method_exists( $val, '__toString' ) ) ) {
				$replace[ $placeholder ] = (string) $val;
			} elseif ( is_array( $val ) || is_object( $val ) ) {
				$jsonEncoded             = wp_json_encode( $val );
				$replace[ $placeholder ] = false !== $jsonEncoded ? $jsonEncoded : '[encoding_failed]';
			} else {
				$replace[ $placeholder ] = '[unsupported_type]';
			}
		}

		return strtr( $message, $replace );
	}


	private function write( string $message ): bool {
		$existingLog = $this->getLog();
		$newLog      = $existingLog . $message;
		return set_transient( self::LOG_TRANSIENT_KEY, $newLog, DAY_IN_SECONDS );
	}

	public function getLog(): string {
		$log = get_transient( self::LOG_TRANSIENT_KEY );
		return false !== $log ? (string) $log : '';
	}

	public function truncateLog(): bool {
		return delete_transient( self::LOG_TRANSIENT_KEY );
	}
}
