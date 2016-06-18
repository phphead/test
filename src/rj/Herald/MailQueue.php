<?php namespace Rj\Herald;

use Rj\Herald,
	Rj\MailQueueInterface;

class MailQueue implements MailQueueInterface {

	public static function push2admin($subj, $msg, $headers = '') {
		return static::push('maroon.dragon@gmail.com', $subj, $msg, $headers);
	}

	/** @return null */
	public static function push($to, $subj, $msg, $headers = '') {
		return Herald::sendEmailMessage($subj, $to, $msg);
	}

}
