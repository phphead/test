<?php namespace Rj;

use Exception,
	Rj\EI\ValidationErrorInterface;

class ValidationException extends Exception implements ValidationErrorInterface {

	protected $_field;

	public function __construct($message = "", $code = 0, Exception $previous = null) {
		$split = explode(':', $message, 2);

		if (count($split) == 2) {
			$this->_field = $split[0];
			$message      = $split[1];
		}

		parent::__construct($message, $code, $previous);
	}

	public function getField() {
		return $this->_field;
	}

}
