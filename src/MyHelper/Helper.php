<?php namespace MyHelper;

class Helper {

	static public function mkdir($dir, $mode = 0777) {
		if ( ! file_exists($dir)) {
			$oldmask = umask(0);
			$er      = error_reporting(0);
			mkdir($dir, $mode, true);
			error_reporting($er);
			umask($oldmask);
			return file_exists($dir);
		}
	}
}
