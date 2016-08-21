<?php namespace Rj;

use Closure,
	Phalcon\Http\RequestInterface,
	Phalcon\Http\ResponseInterface,
	Phalcon\Db\AdapterInterface,
	Phalcon\Mvc\DispatcherInterface,
	Phalcon\Mvc\RouterInterface,
	Rj\Mvc\Model;

/**
 * @property ResponseInterface   $response
 * @property RequestInterface    $request
 * @property AdapterInterface    $db
 * @property DispatcherInterface $dispatcher
 * @property RouterInterface     $router
 * @property TestView124         $view
 *
 * @method Model      crud_getModel()
 * @method Validation crud_getValidation()
 * @method mixed      crud_modelSave(Model $model, Validation $validation)
 * @method mixed      _save()
 */
trait CrudTrait {

	/** @var Model */
	protected $_crud_model;

	public function crud_initialize() {
		if ($id = (int) $this->dispatcher->getParam('id', 'uint')) {
			$model = $this->crud_getModel();
			Assert::true($this->_crud_model = $model::findFirst($id));
		}
	}

	public function crud_delete() {
		Assert::found($this->_crud_model);
		$this->view->noRender();
		$this->_crud_model->delete();

		return $this->response->redirect([
			'for'        => $this->router->getMatchedRoute()->getName(),
			'controller' => $this->dispatcher->getControllerName(),
			'action'     => 'index',
		]);
	}

	public function crud_create($model = null, Closure $success = null) {
		if ( ! $model instanceof Model)
			$model = clone $this->crud_getModel();

		if ($this->request->isPost()) {
			return $this->_save(
				$this->crud_getValidation($model),
				function (Validation $validation) use ($model) {
					$this->crud_modelSave($model, $validation);
				},
				function () use ($success, $model) {
					return $success($model) ?: $this->response->redirect([
						'for'        => $this->router->getMatchedRoute()->getName(),
					    'controller' => $this->dispatcher->getControllerName(),
					    'action'     => 'index',
					]);
				}
			);
		}
	}

	public function crud_edit(Closure $success = null) {
		Assert::found($this->_crud_model);
		$this->view->setVar('userData', $this->_crud_model->toArray());

		return $this->crud_create($this->_crud_model, $success);
	}

}
