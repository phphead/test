<?php namespace Rj;

use Phalcon\DI;

/**
 * @property string $smtp_hostname
 * @property bool   $mail_exceptions
 * @property bool   $production
 * @property object $herald
 */
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

	/** @return Config */
	public static function instance() {
		return DI::getDefault()->getShared('config');
	}

}
