<?php namespace Rj;

use Exception,
	Rj\EI\ValidationErrorInterface;

class ValidationException extends Exception implements ValidationErrorInterface {

	protected $_field;

	public function __construct($message = "", $code = 0, Exception $previous = null) {
		if (is_array($message)) {
			$this->_field = $message['field'];
			$message      = $message['message'];
		}

		parent::__construct($message, $code, $previous);
	}

	public function getField() {
		return $this->_field;
	}

}
