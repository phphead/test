<?php namespace Rj;

use Phalcon\Di;

/**
 * Class Logger
 *
 * @method Logger_File messages() static
 *
 * @package Rj]
 */
class Logger {

	/** @return Logger_File */
	public static function __callStatic($name, $args) {
		return static::instance($name);
	}

	public static function getLoggerInstance($name, $logFileName) {
		return new Logger_File($logFileName);
	}

	/** @return Logger_File */
	public static function instance($name) {
		$serviceName = 'logger_' . strtolower($name);
		$di          = DI::getDefault();

		if ($di->has($serviceName)) {
			return $di->getShared($serviceName);

		} else {
			if ( ! $logFileName = Config::instance()->$serviceName) {
				exit("Logger $name is not configured");
			}

			if ( ! file_exists($logFileName) || ! is_writable($logFileName)) {
				exit('Log file is does not exists or not writeable ' . $logFileName);
			}

			$logger = static::getLoggerInstance($name, $logFileName);
			$di->set($serviceName, $logger, true);

			return $logger;
		}
	}

}
