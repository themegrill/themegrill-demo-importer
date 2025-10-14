<?php

namespace ThemeGrill\StarterTemplates\Services;

use Psr\Log\LoggerInterface;

class LoggerService {

	private static LoggerInterface $logger;

	public static function setLogger( $logger ) {
		self::$logger = $logger;
	}

	public static function getLogger() {
		return self::$logger;
	}
}
