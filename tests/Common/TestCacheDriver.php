<?php
/**
 * TestCacheDriver
 *
 * @author       Nurlan Mukhanov <nurike@gmail.com>
 * @copyright    2020 Nurlan Mukhanov
 * @license      https://en.wikipedia.org/wiki/MIT_License MIT License
 * @link         https://github.com/Falseclock/dbd-php
 */

namespace DBD\Tests\Common;

use Psr\SimpleCache\CacheInterface;

class TestCacheDriver implements CacheInterface
{
	public function clear(): bool {
		return true;
	}

	public function delete($key): bool {
		return true;
	}

	public function deleteMultiple($keys): bool {
		return true;
	}

	public function get($key, $default = null): mixed {
		return $default;
	}

	public function getMultiple($keys, $default = null): iterable {
		return [];
	}

	public function has($key): bool {
		return false;
	}

	public function set($key, $value, $ttl = null): bool {
		return true;
	}

	public function setMultiple($values, $ttl = null): bool {
		return true;
	}
}
