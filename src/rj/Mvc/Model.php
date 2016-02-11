<?php namespace Rj\Mvc;

use ReflectionClass,
	Phalcon\Mvc\Model as Phalcon_Model;

/**
 * @method Model   findFirst($parameters = null) static
 * @method Model[] find($parameters = null) static
 */
class Model extends Phalcon_Model {

	public function save($data = null, $whiteList = null) {
		$ref = new ReflectionClass($this);

		if ($ref->hasProperty('created_at') && ! $this->created_at) {
			$this->created_at = date('Y-m-d H:i:s');
		}

		if ($ref->hasProperty('modified_at')) {
			$this->modified_at = date('Y-m-d H:i:s');
		}

		return parent::save($data, $whiteList);
	}

}
