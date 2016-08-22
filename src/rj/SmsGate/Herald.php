<?php namespace Rj\SmsGate;

use Exception, SoapClient,
	Rj\Config;

class Herald {

	/** @return int */
	public function request($number, $text, array $options = []) {
		if ( ! class_exists('SoapClient', false)) {
			throw new Exception("Class SoapClient not found");
		}

		$extCode = Config::instance()->herald->ext_code;
		$extId   = empty($options['uid']) ? 0 : $options['uid'];

		if ( ! $extId) {
			throw new Exception("Missing 'uid' parameter");
		}

		$str = '<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/"><soap:Body>'
		       . '<ns2:sendMsg xmlns="http://ws.herald.it/types" xmlns:ns2="http://ws.herald.it/wsdl"><ns2:request>'
		       . '<extCode>' . htmlspecialchars($extCode) . '</extCode><extId>' . htmlspecialchars($extId)
		       . '</extId><extMsgType>notify</extMsgType><address>'
		       . htmlspecialchars($number) . '</address><message>' . htmlspecialchars($text)
		       . '</message></ns2:request></ns2:sendMsg></soap:Body></soap:Envelope>';


		$context = stream_context_create([
			'http' => [
				'method' => 'POST',
				'content' => $str,
				'header' => implode("\r\n", [
						'Content-Type: text/xml; charset=UTF-8',
						'SOAPAction: urn:sendMsg',
						'Encoding: UTF-8',
						'Accept: */*',
					]). "\r\n",
			]
		]);

		$res = file_get_contents(Config::instance()->herald->url, null, $context);

		if (preg_match('#<smsStatus>([^<]+)</smsStatus>#iD', $res, $pock1)) {
			$status = $pock1[1];

		} else {
			$status = null;
		}

		if (preg_match('#<respCode>(\d+)</respCode>#iD', $res, $pock)) {
			return [ $pock[1], $status ];

		} else {
			\Logger::messages()->error("ERROR IN " . $res);
			throw new Exception("Unknown error");
		}
	}

	public function send($number, $text, array $options = []) {
		$response = $this->request($number, $text, $options);
		$code     = $response[0];

		if (0 == $code) {
			return true;

		} else {
			throw new Exception("Error code " . $code);
		}
	}

	/** @return int */
	public function check($uid, array $options = []) {
		$options['uid'] = $uid;

		$response = $this->request('', '', $options);
		$status   = $response[1];

		return 'DELIVERED' === strtoupper($status);
	}

}
