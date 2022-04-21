<?php
/**
 * TestCacheDriver
 *
 * @author       Nurlan Mukhanov <nurike@gmail.com>
 * @copyright    2020 Nurlan Mukhanov
 * @license      https://en.wikipedia.org/wiki/MIT_License MIT License
 * @link         https://github.com/Falseclock/dbd-php
 * @noinspection PhpMissingReturnTypeInspection
 */

namespace DBD\Tests\Common;

use Psr\SimpleCache\CacheInterface;

class TestCacheDriver implements CacheInterface
{
	public function clear() {
	}

	public function delete($key) {
	}

	public function deleteMultiple($keys) {
	}

	public function get($key, $default = null) {
	}

	public function getMultiple($keys, $default = null) {
	}

	public function has($key) {
	}

	public function set($key, $value, $ttl = null) {
	}

	public function setMultiple($values, $ttl = null) {
	}
}
