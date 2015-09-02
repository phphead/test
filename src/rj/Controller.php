<?php namespace Rj;

use Exception;

class Controller extends \Phalcon\Mvc\Controller {

	public function assertNoMessages(\Phalcon\Mvc\Model $model) {
		$messages = $model->getMessages();
		if (count($messages)) {
			$result = '';
			foreach ($messages as $message) {
				$result .= $message->getMessage() . "\n";
			}
			throw new Exception($result);
		}
	}

}
