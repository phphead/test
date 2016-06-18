<?php namespace Rj\Model;

/*
CREATE TABLE `sms_queue` (
  `sms_queue_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `phone` varchar(255) NOT NULL,
  `text` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL,
  `sent_at` datetime DEFAULT NULL,
  `err` varchar(255) DEFAULT NULL,
  `status` smallint(1) NOT NULL,
  `event_id` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`sms_queue_id`) USING BTREE
) ENGINE=InnoDB;
*/

use Exception,
	Rj\Helper,
	Rj\Mvc\Model, Rj\Assert, Rj\Config;

class SmsQueue extends Model {

	const STATUS_QUEUED = 0;
	const STATUS_OK     = 1;
	const STATUS_ERR    = 2;

	public static function push2admin($text) {
		return SmsQueue::push('+79163226408', $text);
	}

	/** @return SmsQueue */
	public static function push($number, $text, array $options = []) {
		$q = new static();
		$q->save([
			'phone'      => $number,
			'text'       => $text,
			'created_at' => date('Y-m-d H:i:s'),
			'status'     => static::STATUS_QUEUED,
		] + $options);
		Assert::noMessages($q);

		return $q;
	}

	/** @return SmsQueue */
	public static function pop() {
		return static::findFirst([
			'sent_at is null and status = 0',
			'order' => 'sms_queue_id asc',
		]);
	}

	public $sms_queue_id, $phone, $text, $created_at, $sent_at, $err, $status = 0, $event_id;

	public function getSource() {
		return 'sms_queue';
	}

	public function send() {
		/** @var \Rj\SmsGate\Herald $mailer */
		$gate = $this->getDI()->getShared('SmsGate');

		try {
			if ($num = Helper::sanitizePhone($this->phone)) {
				$result = $gate->send($num, $this->text, [
					'uid' => $this->sms_queue_id,
				]);

			} else {
				trigger_error('Invalid phone number ' . $this->phone);
				$result = false;
			}

			if ($result) {
				$this->sent_at = date('Y-m-d H:i:s');
				$this->err     = null;
				$this->status  = static::STATUS_OK;

			} else if ($error = error_get_last()) {
				$this->err    = $error['message'];
				$this->status = static::STATUS_ERR;

			} else {
				$this->status = static::STATUS_ERR;
			}

		} catch (Exception $e) {
			$this->err    = $e->getMessage();
			$this->status = static::STATUS_ERR;
		}

		$this->save();
		Assert::noMessages($this);
	}

	public function cleanQueue() {
		$this->getWriteConnection()->query("
            delete from {$this->getSource()} where sent_at is not null and sent_at < now() - interval 1 month
        ");
	}

}
