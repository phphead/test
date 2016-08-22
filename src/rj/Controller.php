<?php namespace Rj;

use Exception, Closure,
	Rj\EI\ValidationErrorInterface,
	Phalcon\Validation,
	Phalcon\Validation\Message,
	Phalcon\Mvc\Model as PhalconModel;

/**
 * @property TestView124 $view
 */
class Controller extends \Phalcon\Mvc\Controller {

	public function initialize() {
		if (count($ex = explode('\\', get_class($this))) > 1)
			$this->view->namespace = strtolower($ex[0]);
	}

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

				$messages = $callback($validation);

				if ($messages instanceof Message\Group)
					throw new ValidationException();

				$this->db->commit();

			} else {
				throw new ValidationException();
			}

		} catch (ValidationErrorInterface $e) {
			$this->db->rollback();

			if (isset($messages)) {

				if ($e->getMessage()) {
					$messages->appendMessage(new Message($e->getMessage(), $e->getField()));
				}

				$this->view->setVar('messages', $messages);
				$this->view->setVar('userData', $this->request->getPost());

			} else {
				throw $e;
			}

			$success = false;

		} catch (Exception $e) {
			$this->db->rollback();
			throw $e;
		}

		if (false === $success) {
			// pass

		} else if ($success instanceof Closure) {
			return $success();
		} else {
			return $success;
		}
	}

}
