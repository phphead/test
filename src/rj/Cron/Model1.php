<?php namespace Rj\Cron;

use Exception,
	Rj\Mvc\Model as BaseModel,
	Rj\Logger,
	Rj\Logger_File,
	Rj\Assert,
	Rj\Config,
	Phalcon\CLI\Console,
	Phalcon\DI;

/* =============

create table sh_cron_job (
  cron_job_id varchar(64) not null primary key,
  locked_till int UNSIGNED not null,
  executed_at datetime null,
  completed_at datetime null,
  last_message varchar(255) not null,
  priority int not null
) engine = innodb;

============= */

class Model1 extends BaseModel {

	const RESULT_OK  = 1;
	const RESULT_ERR = 0;

	/** @return self */
	public static function pop() {
		$databaseConfig = [];
		foreach (static::find() as $row) {
			/** @var self $row */
			$databaseConfig[$row->cron_job_id] = $row->toArray();
		}

		/** @var Config $config */
		$config = DI::getDefault()['config'];
		$cronConfig = isset($config->cron) ? $config->cron->toArray() : [];

		$cron = [];
		foreach ($cronConfig as $id => $row) {
			$row += [
			    'title'       => '',
			    'delay'       => 0,
			    'locked_till' => 0,
			    'priority'    => 0,
			];

			$cron[$id] = [ 'id' => $id ] + (isset($databaseConfig[$id]) ? $databaseConfig[$id] : []) + $row;
		}

		foreach ($cron as $row) {
			if ($row['locked_till'] < time()) {
				/** @var self $model */
				$model = static::findFirst([
					'cron_job_id = ?0',
					'bind' => [ $row['id'] ],
				]) ?: new static;
				$model->assign([ 'cron_job_id' => $row['id'] ] + $row);

				return $model;
			}
		}
	}

	public $cron_job_id, $locked_till, $executed_at, $completed_at, $last_message = '', $task, $action,
		$delay, $priority;

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
				'task'   => $this->task,
				'action' => $this->action,
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
