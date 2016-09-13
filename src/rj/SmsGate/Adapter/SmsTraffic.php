<?php namespace Rj\SmsGate\Adapter;

use Rj\Config,
	Rj\SmsGate\Result as SmsGateResult;

class SmsTraffic {

	protected $_params = [];

	public function __construct($params) {
		$this->_params = $params + [
				'username' => '',
				'password' => '',
			];
	}

	/** @return SmsGateResult */
	public function request($number, $text, array $options = []) {
		$url     = 'https://www.smstraffic.ru/multi.php';
		$content = http_build_query([
			'login'        => $this->_params['username'],
		    'password'     => $this->_params['password'],
		    'want_sms_ids' => 1,
		    'phones'       => $number,
		    'message'      => $text,
		    'max_parts'    => 5,
		    'rus'          => 5,
		    'originator'   => empty($options['originator'])
			    ? (empty($this->_params['originator']) ? '' : $this->_params['originator'])
			    : $options['originator'],
		    //'udh'          => '',
		]);

		$context = stream_context_create([
			'http' => [
				'method' => 'POST',
				'content' => $content,
				'header' => implode("\r\n", [
						'Connection: close',
						'Content-Type: application/x-www-form-urlencoded',
						'Content-Length: ' . strlen($content),
					]). "\r\n",
			]
		]);

		$res = file_get_contents($url, null, $context);

		$result = new SmsGateResult();
		$result->raw = $res;

		if (false === $res) {
			$result->error = "Network error";

		} else if (strpos($res, '<result>OK</result>')) {
			if (preg_match('|<sms_id>(\d+)</sms_id>|s', $res, $pock)) {
				$result->smsId = $pock[1];

			} else {
				$result->error = "Malformed result: can't find SMS ID";
			}

		} else if (preg_match('|<description>(.+?)</description>|s', $res, $pock)) {
			$result->error = $pock[1];

		} else {
			$result->error = "Malformed result";
		}

		return $result;
	}

	public function send($number, $text, array $options = []) {
		return $this->request($number, $text, $options);
	}

	/** @return int */
	public function check($uid, array $options = []) {
		return;

		$options['uid'] = $uid;

		$response = $this->request('', '', $options);
		$status   = $response[1];

		return 'DELIVERED' === strtoupper($status);
	}

}
