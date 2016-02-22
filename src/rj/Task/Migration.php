<?php namespace Rj;

use Phalcon\Cli\Task;

class TaskMigration extends Task {

	public function getDir() {
		return DOCROOT;
	}

	public function dumpAction() {
		Migration::dump();
	}

	public function genAction() {
		Migration::setDir($this->getDir());
		Migration::gen();
	}

	public function runAction() {
		Migration::setDir($this->getDir());
		Migration::run();
	}

}
