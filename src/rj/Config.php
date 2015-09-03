<?php namespace Rj;

class Config {

	/** @var bool */
	public $mail_exceptions;

	/** @var string */
	public $display_errors;

	/** @var int */
	public $error_reporting;

	public $smpt_hostname, $mailer_sender_email;

	/** @return \Config */
	public static function instance() {
		return \Phalcon\DI::getDefault()->getShared('config');
	}

}
