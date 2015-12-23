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
					/** @var MailerInterface $mailer */
					$mailer = $di->getShared('mailer');
					$mailer::push2admin('Exception', $message);
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
}
