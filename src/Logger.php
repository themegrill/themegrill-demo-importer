<?php

namespace ThemeGrill\Demo\Importer;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Log\InvalidArgumentException;

class Logger implements LoggerInterface {

	private array $logLevels;
	private string $dateFormat                = 'Y-m-d H:i:s';
	private static ?LoggerInterface $instance = null;
	private $startTime                        = null;
	private $importContentStartTime           = null;
	private $fetchStartTime                   = null;
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

		// Handle timing
		if ( isset( $context['start_time'] ) && true === $context['start_time'] ) {
			$this->startTime = microtime( true );
			$message        .= ' [Timing started]';
		} elseif ( isset( $context['end_time'] ) && true === $context['end_time'] ) {
			if ( null !== $this->startTime ) {
				$elapsed         = microtime( true ) - $this->startTime;
				$message        .= sprintf( ' [Execution time: %.4f sec]', $elapsed );
				$this->startTime = null;
			} else {
				$message .= ' [No timing was started]';
			}
		}

		// Handle import content timing separately
		if ( isset( $context['import_content_start_time'] ) && true === $context['import_content_start_time'] ) {
			$this->importContentStartTime = microtime( true );
			$message                     .= ' [Import content timing started]';
		} elseif ( isset( $context['import_content_end_time'] ) && true === $context['import_content_end_time'] ) {
			if ( null !== $this->importContentStartTime ) {
				$importElapsed                = microtime( true ) - $this->importContentStartTime;
				$message                     .= sprintf( ' [Execution time: %.4f sec]', $importElapsed );
				$this->importContentStartTime = null;
			} else {
				$message .= ' [No import content timing was started]';
			}
		}

		// Handle fetch remote file timing separately
		if ( isset( $context['fetch_start_time'] ) && true === $context['fetch_start_time'] ) {
			$this->fetchStartTime = microtime( true );
			$message             .= ' [Fetch timing started]';
		} elseif ( isset( $context['fetch_end_time'] ) && true === $context['fetch_end_time'] ) {
			if ( null !== $this->fetchStartTime ) {
				$fetchElapsed         = microtime( true ) - $this->fetchStartTime;
				$message             .= sprintf( ' [Execution time: %.4f sec]', $fetchElapsed );
				$this->fetchStartTime = null;
			} else {
				$message .= ' [No fetch timing was started]';
			}
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
