<?php namespace Rj;

use Phalcon\Validation\Message,
	Phalcon\Validation\Message\Group;

class Herald {

	const TYPE_EMAIL = 1;
	const TYPE_SMS   = 2;

	protected static function _config() {
		return Config::instance()->herald->toArray();
	}

	protected static function _host() {
		return static::_config()['addr'] ?: static::_config()['host'];
	}

	protected static function _loadList($uri) {
		$context = stream_context_create([
			'http' => [
				'header' => implode("\r\n", [
					"Host: " . static::_config()['host'],
					"Project-Key: "   . sha1(static::_config()['project'] . sha1(static::_config()['api_key'])),
					""
				]),
			    'timeout' => 2,
			]
		]);

		$url = 'http://' . static::_host() . '/api01/' . static::_config()['project'] . '/' . $uri;

		$content = json_decode($raw = file_get_contents($url, null, $context), true);

		return empty($content['response']) ? [] : $content['response'];
	}

	public static function getTemplateList() {
		return static::_loadList('template/index');
	}

	public static function getTypeList() {
		return static::_loadList('mailing/type_list');
	}

	/** @return bool */
	public static function queue($data) {
		$payload = json_encode($data);
		$length  = strlen($payload);

		$headers = [
				"Host: " . static::_config()['host'],
				"Project-Key: "   . sha1(static::_config()['project'] . sha1(static::_config()['api_key'])),
				"Content-Type: multipart/form-data",
				"Content-Length: " . $length,
			];

		$context = stream_context_create([
			'http' => [
				'header' => implode("\r\n", [
					"Host: " . static::_config()['host'],
					"Project-Key: "   . sha1(static::_config()['project'] . sha1(static::_config()['api_key'])),
					"Content-Type: multipart/form-data",
					"Content-Length: " . $length,
					""
				]),
			    'method'  => 'POST',
			    'content' => $payload,
			    'timeout' => 2,
			],
		]);

		$url = 'http://' . static::_host() . '/api01/' . static::_config()['project'] . '/mailing/queue';

		$content = json_decode($raw = file_get_contents($url, null, $context), true);

		if ( ! empty($content['messages'])) {
			$group = new Group();
			foreach ($content['messages'] as $row)
				$group->appendMessage(new Message(
					$row['text'],
					empty($row['field']) ? '' : $row['field'],
					empty($row['type'])  ? '' : $row['type']
				));

			return $group;
		}

		return ! empty($content['result']) && 'ok' === $content['result'];
	}

	/** @return bool */
	public static function sendEmailMessage($subject, $to, $body) {
		$typeList = static::getTypeList();

		return static::queue([
			'subject'         => $subject,
			'type'            => static::TYPE_EMAIL,
			'mailing_type_id' => $typeList[0]['id'],
			'template_id'     => '',
			'body'            => $body,
			'list'            => $to,
		]);
	}

}
