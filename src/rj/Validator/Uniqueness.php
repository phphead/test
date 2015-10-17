<?php namespace Rj\Validator;

use Rj\Assert,
	Phalcon\Validation\Message,
	Phalcon\Validation\Validator,
	Phalcon\Validation\ValidatorInterface;

class Uniqueness extends Validator implements ValidatorInterface
{

	/**
	 * Executes the validation
	 *
	 * @param \Phalcon\Validation $validation
	 * @param string $field
	 * @return boolean
	 */
	public function validate($validator, $attribute) {
		Assert::true($modelName = $this->getOption('model'));
		Assert::true($fieldName = ($this->getOption('field') ?: $attribute));

		$model = $modelName::findFirst([
			"$fieldName = ?0",
			'bind' => [ $validator->getValue($attribute) ]
		]);

		if ($model) {
			$message = $this->getOption('message') ?: "Field '$attribute' is not unique";
			$validator->appendMessage(new Message($message, $attribute));
			return false;
		}
	}

}
