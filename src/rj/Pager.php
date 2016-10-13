<?php namespace Rj;

use Exception,
	Phalcon\Mvc\Model,
	Phalcon\Mvc\Model\Criteria,
	Phalcon\Di;

/**
 * @property int     $page
 * @property int     $onPage
 * @property int     $count
 * @property int     $pageCount
 * @property array   $options
 * @property Model[] $list
 */
class Pager {

	protected static $_di;

	public static function setDI($di) {
		static::$_di = $di;
	}

	public static function getDI() {
		return static::$_di ?: DI::getDefault();
	}

	protected $_model, $_loaded = false, $_page, $_onPage = 50, $_count, $_pageCount, $_list, $_options = [],
		$_html;

	protected function _load() {
		if ( ! $this->_loaded) {
			if (null === $this->_count)
				$this->_count = call_user_func([ get_class($this->_model), 'count' ], $this->_options);

			if (null === $this->_page)
				$this->_page = static::getDI()['request']->get('page', 'uint', 0);

			$this->_pageCount = ceil($this->_count / $this->_onPage);
			$this->_page      = max(0, min($this->_page, $this->_pageCount - 1));

			$this->_list = call_user_func([ get_class($this->_model), 'find' ], [
					'limit'  => $this->_onPage,
					'offset' => $this->_page * $this->_onPage,
				] + $this->_options);

			$this->_loaded = true;
		}
	}

	public function __construct($model, array $options = []) {
		if ($model instanceof Model) {
			$this->_model   = $model;
			$this->_options = $options;

		} else if ($model instanceof Criteria) {
			$counter = $model->execute();

			if (null === $this->_count)
				$this->_count = $counter->count();
				//$this->_count = call_user_func([ get_class($this->_model), 'count' ], $this->_options);

			if (null === $this->_page)
				$this->_page = static::getDI()['request']->get('page', 'uint', 0);

			$this->_count     = count($counter);
			$this->_pageCount = ceil($this->_count / $this->_onPage);
			$this->_page      = max(0, min($this->_page, $this->_pageCount - 1));

			$model->limit($this->_onPage, $this->_page * $this->_onPage);
			$this->_list = $model->execute();

			$this->_loaded = true;

		} else {
			throw new Exception("Bad parameter");
		}
	}

	public function __set($key, $value) {
		switch ($key) {
			case 'page':
			case 'count':
			case 'onPage':
			case 'options':
				$this->{'_' . $key} = $value;
				break;

			default:
				trigger_error("Undefined property " . __CLASS__ . '::$' . $key);
		}
	}

	public function __get($key) {
		switch ($key) {
			case 'page':
			case 'onPage':
			case 'count':
			case 'pageCount':
			case 'list':
			case 'options':
				$this->_load();
				return $this->{'_' . $key};

			default:
				trigger_error("Undefined property " . __CLASS__ . '::$' . $key);
		}
	}

	public function render() {
		if (null === $this->_html) {
			$get = isset($_GET) && is_array($_GET) ? $_GET : [];
			$this->_html = '';
			$this->_load();
			$last = 0;
			for ($i = 0; $i < $this->pageCount; $i++)
				if ($i == 0 || $i == $this->pageCount - 1 || ($i > $this->page - 4 && $i < $this->page + 4)) {
					$get['page'] = $i;
					if ($i > 0 && $last < $i - 1)
						$this->_html .= ' ... ';
					$this->_html .= '<a class="btn btn-xs ' . ($this->page == $i ? 'btn-primary' : 'btn-default') . '"'
						. ' href="?' . http_build_query($get) . '">' . htmlspecialchars($i + 1) . '</a>' . "\n";
					$last = $i;
				}
		}

		return $this->_html;
	}

}
