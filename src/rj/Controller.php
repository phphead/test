<?php namespace Rj;

use Exception, Closure,
	Phalcon\Validation;

class Controller extends \Phalcon\Mvc\Controller {

	/** @deprecated use Assert::noMessages() instead */
	public function assertNoMessages(\Phalcon\Mvc\Model $model) {
		Assert::noMessages($model);
	}

	/** @deprecated use Assert::true() instead */
	public function assertTrue($assertion, $message = 'Bad request') {
		Assert::true($assertion, $message);
	}

	/** @return mixed */
	protected function _save(Validation $validation, Closure $callback, $success) {
		$this->db->begin();
		try {

			$messages = $validation->validate($this->request->getPost());
			if (count($messages) == 0) {

				$callback($validation);

				$this->db->commit();

				if ($success instanceof Closure) {
					return $success();
				} else {
					return $success;
				}

			} else {
				$this->view->setVar('messages', $messages);
				$this->view->setVar('userData', $this->request->getPost());
			}

		} catch (Exception $e) {
			$this->db->rollback();
			throw $e;
		}
	}

}
