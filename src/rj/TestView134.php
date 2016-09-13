<?php namespace Rj;

use Phalcon\Mvc\View;

class TestView136 extends \Phalcon\Mvc\View
{
    public function noRender()
    {
        $this->setRenderLevel(View::LEVEL_NO_RENDER);
    }

    public function setVar($k, $v)
    {
        EngineSmarty124::getSmarty()->assign($k, $v);
    }

    public function setVars($params, $merge=null)
    {
        foreach ($params as $k => $v) {
            $this->setVar($k, $v);
        }
    }

    protected $_renderLevel;

    public function setRenderLevel($level)
    {
        $this->_renderLevel = $level;
    }

    public function start()
    {
    }

    protected $_picked, $_content;
    public $namespace = '';

    public function pick($tpl)
    {
        $this->_picked = $tpl;
    }

    public function render($controllerName, $actionName, $params=null)
    {
        if ($this->_renderLevel !== View::LEVEL_NO_RENDER) {
            $this->_content = EngineSmarty124::getSmarty()->fetch($this->_picked ? $this->_picked . '.tpl' : ($this->namespace ? $this->namespace . '/' : '') . $controllerName . '/' . $actionName . '.tpl');
        }
    }

    public function finish()
    {
    }

    public function getContent()
    {
        return $this->_content;
    }
}
