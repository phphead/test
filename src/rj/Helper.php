<?php namespace Rj;

use Exception,
	Phalcon\DI,
	Phalcon\Mvc\Application,
	Phalcon\Mvc\Dispatcher\Exception as PhalconException,
	Rj\EI\Http404Interface;

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

	public static function escapeArray($adapter, array $values) {
		$ret = '';
		foreach ($values as $value) {
			$ret .= ($ret ? ',' : '') . $adapter->escapeString($value);
		}
		return $ret;
	}

	public static function dateDiffInDays($date, $now = null) {
		if (null === $now) {
			$now = time();
		}

		if (is_string($date)) {
			$date = strtotime($date);
		}

		return floor((($date - $now) / 86400) + 1);
	}

	public static function strEnd($num, $endings = '') {
		list($s1,$s2,$s3) = explode(',', $endings);
		$num = abs($num);
		$num = $num % 100;

		if (($num > 4) && ($num < 21)) {
			return $s3;
		}
		$num = $num % 10;
		if (($num == 0) || ($num > 4)) {
			return $s3;
		}
		if ($num == 1) {
			return $s1;
		}

		return $s2;
	}

	public static function logException(Exception $e, $mail = true)
	{
		$di = DI::getDefault();

		if ($di->has('request')) {

			/** @var \Phalcon\Http\Request $request */
			$request = $di->getShared('request');

			$requestUri  = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '<!undefined>';
			//$queryString = isset($_SERVER['QUERY_STRING']) ? ($_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : '') : '?<!undefined>';

			$message = date('Y-m-d H:i:s') . ' ' . get_class($e) . ': ' . $e->getMessage() . "\n"
				. 'UserAgent: ' . $request->getUserAgent() . "\n"
				. 'HTTP Referrer: ' . urldecode($request->getHTTPReferer()) . "\n"
				. $request->getClientAddress() . " URL: " . $request->getScheme() . '://' . $request->getHttpHost() . urldecode($requestUri) . "\n"
				. $e->getTraceAsString() . "\n";

		} else {
			$message = date('Y-m-d H:i:s') . ' ' . $e->getMessage() . "\n"
				. "There is no request object\n"
				. $e->getTraceAsString();
		}

		if (Config::instance()->mail_exceptions && $mail) {
			switch (true) {
//			case $e instanceof PageNotFound:
//			case $e instanceof Phalcon\Mvc\Dispatcher\Exception:
//				break;

				default:
					\MailQueue::push2admin('Exception', $message);
					break;
			}
		}

		Logger::messages()->error($message);
	}

	public static function setExceptionHandler() {
		set_exception_handler(function(Exception $e) {
			static::logException($e);

			if ( ! Config::instance()->production || PHP_SAPI == 'cli') {
				throw $e;

			} else {
				$app = new Application(DI::getDefault());

				switch (true) {
					case $e instanceof Http404Interface:
					case $e instanceof PhalconException:
						header('HTTP/1.1 404 Not Found');
						header('Status: 404 Not Found');
						exit($app->handle('/error/show404')->getContent());

					default:
						header('HTTP/1.1 503 Service Temporarily Unavailable');
						header('Status: 503 Service Temporarily Unavailable');
						header('Retry-After: 3600');
						exit($app->handle('/error/show503')->getContent());
				}
			}
		});
	}

	public static function setErrorHandler() {
		set_error_handler(function($errno, $errstr, $errfile, $errline) {
			switch ($errno) {
				case E_USER_NOTICE:
				case E_STRICT:
					break;

				default:
					Logger::messages()->error(sprintf("Error: #%d %s at %s:%d", $errno, $errstr, $errfile, $errline));
			}
		});
	}

	public static function fileUnlinkOnShutdown($fileName) {
		register_shutdown_function(function() use ($fileName) {
			if (file_exists($fileName)) unlink($fileName);
		});
	}

	public static function fileDownload($url, $fileName) {
		try {
			Assert::true($f = fopen($url, 'r'));
			Assert::true($fd = fopen($fileName, 'w+'));
			while ( ! feof($f)) fwrite($fd, fread($f, 8 * 1024));
			fclose($f);
			fclose($fd);

			return true;

		} catch (Exception $e) {
			return false;
		}
	}
}
