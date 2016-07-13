<?php namespace Rj;

use Exception, Closure,
	Phalcon\HTTP\RequestInterface;

class Assert {

	protected static function _generateExceptionMessage($default = 'Assertion failure') {
		$data = debug_backtrace()[1];

		$lines = explode("\n", str_replace("\r", "", file_get_contents($data['file'])));
		$str   = trim($lines[$data['line'] - 1]);

		if (preg_match('/\((.*)\)/iD', $str, $pock)) {
			$str = $pock[1];
		}

		return $str ?: $default;
	}

	public static function saved($model) {
		static::noMessages($model);
	}

	public static function noMessages($model) {
		/** @var \Rj\Mvc\Model $model */
		$ret = '';
		if ($messages = $model->getMessages())
			foreach ($model->getMessages() as $message) {
				$ret .= ($message ? "\n" : "") . $message->getMessage();
			}
		if ($ret) throw new Exception($ret);
	}

	public static function true($condition, $message = null, $exceptionClass = 'Exception') {
		if ($condition instanceof Closure)
			$condition = $condition();

		if ( ! $condition) {
			if (null === $message)
				$message = 'Assertion failure in "' . static::_generateExceptionMessage() . '"';

			throw new $exceptionClass($message);
		}
	}

	public static function found($cond, $message = 'Page not found', $exceptionClass = 'Exception') {
		static::true($cond, $message, $exceptionClass);
	}

	public static function post(RequestInterface $request, $message = "Only POST requests allowed") {
		Assert::true($request->isPost(), $message);
	}

}
