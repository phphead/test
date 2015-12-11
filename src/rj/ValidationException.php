<?php namespace Rj;

use Exception,
	Rj\EI\ValidationErrorInterface;

class ValidationException extends Exception implements ValidationErrorInterface {

	protected $_field;

	public function __construct($message = "", $code = 0, Exception $previous = null) {
		if ( @ list($field, $msg) = explode(':', $message, 2)) {
			$this->_field = $field;
			$message      = $msg;
		}

		parent::__construct($message, $code, $previous);
	}

	public function getField() {
		return $this->_field;
	}

}
