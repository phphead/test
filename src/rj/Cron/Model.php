<?php namespace Rj\Cron;

use Exception,
	Rj\Mvc\Model as BaseModel,
	Rj\Logger,
	Rj\Logger_File,
	Rj\Assert,
	Phalcon\CLI\Console,
	Phalcon\DI;

/* =============

create table sh_cron_job (
  cron_job_id int unsigned not null primary key auto_increment,
  delay int UNSIGNED not null,
  locked_till int UNSIGNED not null,
  title VARCHAR(255) not null,
  task_name varchar(255) not null,
  action_name varchar (255) not null,
  executed_at datetime null,
  completed_at datetime null,
  last_message varchar(255) not null,
  priority int not null
) engine = innodb;

============= */

class Model extends BaseModel {

	const RESULT_OK  = 1;
	const RESULT_ERR = 0;

	/** @return self */
	public static function pop() {
		return static::findFirst([
			'locked_till < ?0',
		    'bind'  => [ time() ],
		    'order' => 'priority asc, executed_at asc, cron_job_id asc',
		]);
	}

	public $cron_job_id, $delay, $locked_till, $task_name, $action_name, $executed_at, $completed_at,
		$last_message = '', $title;

	/** @return Logger_File */
	public function getLogger() {
		return Logger::messages();
	}

	public function execute(Console $console) {
		$this->getWriteConnection()->execute("
			update {$this->getSource()} set executed_at = ? where cron_job_id = ?
		", [ $this->executed_at = date("Y-m-d H:i:s"), $this->cron_job_id ]);

		try {
			$console->handle(array(
				'task'   => $this->task_name,
				'action' => $this->action_name,
			));

			$this->save([
				'last_message' => '',
			    'completed_at' => date('Y-m-d H:i:s'),
			    'locked_till'  => time() + $this->delay,
			]);
			Assert::saved($this);

			return static::RESULT_OK;

		} catch (Exception $e) {
			$this->getLogger()->exception($e);

			$this->save([
				'last_message' => get_class($e) . ': ' . $e->getMessage(),
				'completed_at' => null,
				'locked_till'  => time() + $this->delay,
			]);

			return static::RESULT_ERR;
		}
	}

}
