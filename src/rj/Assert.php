<?php namespace Rj;

use Exception,
	Phalcon\HTTP\RequestInterface;

class Assert {

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

	public static function true($condition, $message = 'Assertion failure') {
		if ( ! $condition) {
			throw new Exception($message);
		}
	}

	public static function found($cond, $message = 'Page not found') {
		static::true($cond, $message);
	}

	public static function post(RequestInterface $request, $message = "Only POST requests allowed") {
		Assert::true($request->isPost(), $message);
	}

}
