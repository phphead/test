<?php namespace Rj;

use Exception, Phalcon\Mvc\Model;

class Assert {

	public static function noMessages(Model $model) {
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

}
