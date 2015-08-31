<?php namespace Rj;

class Config {

	/** @return \Config */
	public function instance() {
		return \Phalcon\DI::getDefault()->getShared('config');
	}

}
