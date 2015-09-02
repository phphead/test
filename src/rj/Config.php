<?php namespace Rj;

class Config {

	/** @var bool */
	public $mail_exceptions;

	/** @var string */
	public $display_errors;

	/** @var int */
	public $error_reporting;

	/** @return \Config */
	public static function instance() {
		return \Phalcon\DI::getDefault()->getShared('config');
	}

}
