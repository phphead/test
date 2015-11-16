<?php namespace Rj;

use Exception,
	Phalcon\Logger\Adapter\File as Phalcon_Logger_File;

class Logger_File extends Phalcon_Logger_File {

	public function exception(Exception $e) {
		$this->error("Exception: " . get_class($e) . ": {$e->getMessage()}\nTrace: {$e->getTraceAsString()}");
	}

}
