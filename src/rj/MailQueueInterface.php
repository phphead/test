<?php namespace Rj;


interface MailQueueInterface {

	/** @return MailQueueInterface */
	public static function push2admin($subj, $msg, $headers = '');

	/** @return MailQueueInterface */
	public static function push($to, $subj, $msg, $headers = '');

}
