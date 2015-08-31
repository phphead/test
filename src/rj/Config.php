<?php namespace Rj;

class Config {

	/** @var bool */
	public $mail_exceptions;

	/** @return \Config */
	public static function instance() {
		return \Phalcon\DI::getDefault()->getShared('config');
	}

}
