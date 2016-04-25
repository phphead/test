<?php namespace Rj;

use Exception,
	Phalcon\DI,
	Phalcon\Http\RequestInterface,
	Phalcon\Logger\Adapter\File as Phalcon_Logger_File,
	Rj\EI\DoNotMail;

class Logger_File extends Phalcon_Logger_File {

	public function exception(Exception $e) {
		if ($this->testMailing($e))
			$this->mailException($e);

		$this->error("Exception: " . get_class($e) . ": {$e->getMessage()}\nTrace: {$e->getTraceAsString()}");
	}

	public function mailException(Exception $e) {
		if (class_exists('MailQueue', true)) {
			\MailQueue::push2admin('Exception', $this->getExceptionExpandedMessage($e));
		}
	}

	public function testMailing(Exception $e) {
		if (\Config::instance()->mail_exceptions) {
			switch (true) {
				case $e instanceof DoNotMail:
					break;

				default:
					return true;
			}
		}
	}

	public function getExceptionExpandedMessage(Exception $e) {
		/** @var RequestInterface $request */
		$request    = isset(DI::getDefault()['request']) ? DI::getDefault()['request'] : null;
		$requestUri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '<!undefined>';

		if ($request) {
			return date('Y-m-d H:i:s') . ' ' . get_class($e) . ': ' . $e->getMessage() . "\n"
	           . 'UserAgent: ' . $request->getUserAgent() . "\n"
	           . 'HTTP Referrer: ' . urldecode($request->getHTTPReferer()) . "\n"
	           . $request->getClientAddress() . " URL: " . $request->getScheme() . '://' . $request->getHttpHost() . urldecode($requestUri) . "\n"
	           . $e->getTraceAsString() . "\n";

		} else {
			return date('Y-m-d H:i:s') . ' ' .  get_class($e) . ': ' . $e->getMessage() . "\n"
	           . "There is no request object\n"
	           . $e->getTraceAsString();
		}
	}

}
