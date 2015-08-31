<?php namespace Rj;

use Exception,
	Phalcon\DI;

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
				. 'HTTP Referer: ' . urldecode($request->getHTTPReferer()) . "\n"
				. $request->getClientAddress() . " URL: " . $request->getScheme() . '://' . $request->getHost() . urldecode($requestUri) . "; Logged user: " . (User::getLoggedUser() ? User::getLoggedUser()->username : 'none') . "\n"
				. $e->getTraceAsString() . "\n";

		} else {
			$message = date('Y-m-d H:i:s') . ' ' . $e->getMessage() . "\n"
				. "There is no request object\n"
				. $e->getTraceAsString();
		}

		if (Config::instance()->mail_exceptions && $mail) {
			try {
				//if ( ! $e instanceof PageNotFound) {
				/** @var MailerInterface $mailer */
				$mailer = $di->getShared('mailer');
				$mailer::push2admin('Exception', $message);
				//}

			} catch (Exception $e) {
			}
		}

		Logger::messages()->error($message);
	}
}
