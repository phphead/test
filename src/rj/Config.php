<?php namespace Rj;

class Config {

	/** @return \Config */
	public static function instance() {
		return \Phalcon\DI::getDefault()->getShared('config');
	}

}
