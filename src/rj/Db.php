<?php namespace Rj;

use Exception, Closure,
	Phalcon\Db\Adapter\Pdo\Mysql as Mysql_Adapter,
	Phalcon\Db\AdapterInterface,
	Phalcon\DI;

class Db
{
    protected $_connection;
    protected $_profile = array();

	/**
	 * @param $param1 AdapterInterface|Closure
	 * @param $param2 Closure|null
	 *
	 * @throws Exception
	 * @return mixed
	 */
	public static function transaction() {
		switch (func_num_args()) {
			case 1:
				$db       = DI::getDefault()['db'];
				$callback = func_get_arg(0);
				break;

			case 2:
				$db       = func_get_arg(0);
				$callback = func_get_arg(1);
				break;

			default:
				throw new Exception("Bad parameter count");
		}

		/** @var $db       AdapterInterface */
		/** @var $callback Closure          */

		//Assert::true($db instanceof AdapterInterface);
		Assert::true($callback instanceof Closure);

		$db->begin();
		try {
			$ret = $callback($db);
			$db->commit();

			return $ret;

		} catch (Exception $e) {
			$db->rollback();
			throw $e;
		}
	}

    protected function _profileQuery($query)
    {
        if (isset($this->_profile[$query])) {
            $this->_profile[$query]++;
        } else {
            $this->_profile[$query] = 1;
        }
    }

    public function logProfiles($logger)
    {
        $msg = "";
        foreach ($this->_profile as $query => $count) {
            if ($count > 1) {
                $msg .= "$count $query\n";
            }
        }
        if ($msg) $logger->log("\n" . $msg);
    }

    /**
     * Create connection.
     *
     * @param array $config Connection configuration.
     */
    public function __construct(array $config)
    {
        $this->_connection = new Mysql_Adapter($config);
        $this->_connection->query('set names utf8');
    }

    public function __call($method, $args)
    {
        //Phalcon\DI::getDefault()->getShared('logger')->log($method . ' ' . json_encode($args));
        return call_user_func_array(array($this->_connection, $method), $args);
    }

    /**
     * @return \Phalcon\Db\ResultInterface
     */
    public function query($query, $bind = array())
    {
        $query = $this->bind($query, $bind);
        $this->_profileQuery($query);
        try {
            return $this->_connection->query($query, $bind);
        } catch (Exception $e) {
            //exit($query);
            throw $e;
        }
    }

    public function fetchOne($query, $fetchMode, $bind = array())
    {
        $query = $this->bind($query, $bind);
        $this->_profileQuery($query);
        try {
            return $this->_connection->fetchOne($query, $fetchMode, $bind);
        } catch (Exception $e) {
            //exit($query);
            throw $e;
        }
    }

    public function bind($query, $bind)
    {
        // А остальное - параметры для бинда.
        if (is_array($bind) && count($bind)) {
            // В запросе есть знаки вопроса, которые нам нужны
            // Для получения этих знаков разобьем строку
            $parts = preg_split('/(\?)/', $query, null, PREG_SPLIT_DELIM_CAPTURE);
            // Это количество вопросов
            $count = 0;
            // Тут их посчитаем
            foreach ($parts as $part) {
                if ($part == '?') {
                    $count++;
                }
            }

            // Кол-во вопросов должно совпадать с кол-вом параметров
            if ($count != count($bind)) {
                return $query;
                //throw new Exception('Query parameter count does not match in ' . $query);
            }

            // А тут заменим
            foreach ($parts as & $part) {
                if ($part == '?') {
                    $arg = array_shift($bind);
                    if (is_array($arg)) {
                        $_arg = '';
                        foreach ($arg as $a) {
                            $_arg .= $_arg ? ', ' : '';
                            $_arg .= $this->_connection->escapeString($a);
                        }
                        //$part = '(' . $_arg . ')';
                        $part = $_arg;
                    } else {
                        $part = $this->_connection->escapeString($arg);
                    }
                }
            }

            // Склеим на
            $query = implode($parts);
        }

        return $query;
    }
}
