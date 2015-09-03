<?php namespace Rj;

class Config {

	/** @var bool */
	public $mail_exceptions;

	/** @var string */
	public $display_errors;

	/** @var int */
	public $error_reporting;

	public $smtp_hostname, $mailer_sender_email;

	/** @var string */
	public $mailer;

	/** @return \Config */
	public static function instance() {
		return \Phalcon\DI::getDefault()->getShared('config');
	}

}
