<?php namespace Rj\CLI;

use Exception, Rj\Logger,
	Phalcon\Logger as PhalconLogger,
	Phalcon\CLI\Task as BaseTask;

class Task extends BaseTask {

	public function log($message, $logger = null, $type = null) {
		if (null === $logger)
			$logger = Logger::messages();

		echo date('Y-m-d H:i:s') . ' ' . $message . "\n";

		if ($logger)
			$logger->log($message, $type);
	}

	public function logException(Exception $e, $logger = null, $type = PhalconLogger::CRITICAL) {
		if (null === $logger)
			$logger = Logger::messages();

		if (method_exists($logger, 'testMailing') && $logger->testMailing($e))
			$logger->mailException($e);

		$this->log(sprintf(
			'Exception %s %d: %s in %s:%d',
			get_class($e),
			$e->getCode(),
			$e->getMessage(),
			$e->getFile(),
			$e->getLine()
		), $logger, $type);
	}

}
