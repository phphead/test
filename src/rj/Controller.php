<?php namespace Rj;

use Exception, Closure,
	Rj\EI\ValidationErrorInterface,
	Phalcon\Validation,
	Phalcon\Mvc\Model as PhalconModel;

class Controller extends \Phalcon\Mvc\Controller {

	/** @deprecated use Assert::noMessages() instead */
	public function assertNoMessages(PhalconModel $model) {
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
				throw new ValidationException();
			}

		} catch (ValidationErrorInterface $e) {
			if (isset($messages)) {
				$this->view->setVar('messages', $messages);
				$this->view->setVar('userData', $this->request->getPost());

			} else {
				throw $e;
			}

		} catch (Exception $e) {
			$this->db->rollback();
			throw $e;
		}
	}

}
