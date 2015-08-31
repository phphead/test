<?php namespace Rj;

use Phalcon\Di, Phalcon\Logger\Adapter\File as Logger_File;

/**
 * Class Logger
 *
 * @method Logger_File messages() static
 *
 * @package Rj]
 */
class Logger {

	/** @return Logger_File */
	public static function __callStatic($name) {
		return static::instance($name);
	}

	/** @return Logger_File */
	public static function instance($name) {
		$serviceName = 'logger_' . strtolower($name);
		$di          = DI::getDefault();

		if ($logger = $di->getShared($serviceName)) {
			return $logger;

		} else {
			if ( ! $logFileName = Config::instance()->$serviceName) {
				exit("Logger $name is not configured");
			}

			if ( ! file_exists($logFileName) || ! is_writable($logFileName)) {
				exit('Log file is does not exists or not writeable ' . $logFileName);
			}

			$logger = new Logger_File($logFileName);
			$di->set($serviceName, $logger, true);

			return $logger;
		}
	}

}
