<?php namespace Rj;

use Closure,
	Phalcon\DI,
	Phalcon\Cache\BackendInterface;

class Cache {

	public static function get($key, $lifetime = null, Closure $callback) {
		/** @var BackendInterface $cache */
		$cache = DI::getDefault()['cache'];

		if (null === ($data = $cache->get($key, $lifetime))) {
			$cache->save($key, $data = $callback(), $lifetime);
		}

		return $data;
	}

}
