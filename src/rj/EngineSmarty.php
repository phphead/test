<?php namespace Rj;

use Phalcon\DI,
    Phalcon\Mvc\View\Engine,
    Phalcon\Mvc\View\EngineInterface;

/**
 * Phalcon\Mvc\View\Engine\Smarty
 *
 * Adapter to use Smarty library as templating engine
 */
class EngineSmarty extends Engine implements EngineInterface
{
    public static $smarty;

    /**
     * @return \Smarty
     */
    public static function getSmarty()
    {
        if ( ! static::$smarty) {
            require_once DOCROOT . '../vendor/smarty/smarty/libs/Smarty.class.php';
            $smarty = new \Smarty();

            $smarty->template_dir = DOCROOT . '../app/views';
            $smarty->compile_dir = DOCROOT . '../templates_c';
            $smarty->error_reporting = E_ALL & ~E_NOTICE;
            $smarty->error_unassigned = false;
            //$smarty->force_compile = true;

            // Странно что это вообще понядобилось
            $smarty->muteExpectedErrors();

            $smarty->registerPlugin('modifier', 'e', 'htmlspecialchars');

            $smarty->assign(array(
                'url' => DI::getDefault()->get('url'),
            ));

            static::$smarty = $smarty;
        }
        return static::$smarty;
    }

    protected $_smarty;

    protected $_params;

    /**
     * Phalcon\Mvc\View\Engine\Twig constructor
     *
     * @param \Phalcon\Mvc\ViewInterface $view
     * @param \Phalcon\DiInterface $di
     */
    public function __construct($view,  $di=null)
    {
        $this->_smarty = static::getSmarty();
        //$this->_smarty->config_dir = SMARTY_DIR . 'configs';
        //$this->_smarty->cache_dir = SMARTY_DIR . 'cache';
        //$this->_smarty->caching = false;
        //$this->_smarty->debugging = true;
        parent::__construct($view, $di);
    }

    /**
     * Renders a view
     *
     * @param string $path
     * @param array $params
     */
    public function render($path, $params, $mustClean=null)
    {
        if (!isset($params['content'])) {
            $params['content'] = $this->_view->getContent();
        }
        foreach($params as $key => $value){
            $this->_smarty->assign($key, $value);
        }
        $this->_view->setContent($this->_smarty->fetch($path));
    }
    
    /**
     * Set Smarty's options
     *
     * @param array $options
     */
    public function setOptions(array $options)
    {
        foreach ($options as $k => $v) {
             $this->_smarty->$k = $v;
        }
    }

    public function registerPlugin()
    {
        return call_user_func_array(array($this->_smarty, 'registerPlugin'), func_get_args());
    }
}
