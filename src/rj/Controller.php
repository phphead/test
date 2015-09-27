<?php namespace Rj;

use Exception;

class Controller extends \Phalcon\Mvc\Controller {

	public function assertNoMessages(\Phalcon\Mvc\Model $model) {
		Assert::noMessages($model);
	}

	public function assertTrue($assertion, $message = 'Bad request') {
		Assert::true($assertion, $message);
	}

}
