<?php namespace Rj\CLI;

class CronTask extends Task {

	protected $cronJobClass = 'CronJob';

	public function mainAction() {
		$i     = 25;
		$class = $this->cronJobClass;

		while (--$i > 0 && ($job = $class::pop())) {
			/** @var \Rj\Cron\Model1 $job */
			$this->log("Executing " . $job->title, false);
			$time = microtime(true);

			$result = $job->execute($this->di['console']);
			switch ($result) {
				case $class::RESULT_OK:
					$this->log("Task {$job->title} executed in " . round(microtime(true) - $time, 2) . " sec", false);
					break;

				case $class::RESULT_ERR:
					$this->log("Task {$job->title} failed in " . round(microtime(true) - $time, 2) . " sec with message \"{$job->last_message}\"", false);
					break;

				default:
					$this->log("Task {$job->title} completed with unexpected code $result");
					break;
			}
		}
	}

}
