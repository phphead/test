<?php namespace Rj\Validator;

use Closure,
	Phalcon\Validation\Validator\PresenceOf,
	Phalcon\Validation\Message,
	Phalcon\Validation;

class Callback extends PresenceOf {

	public function validate($validation, $attribute) {
		$callback = $this->getOption('callback');

		if ($callback instanceof Closure) {
			return $callback($validation, $attribute);

		} else {
			$validation->appendMessage(new Message('Callback must be instance of closure', $attribute));
		}

		return true;
	}

}
