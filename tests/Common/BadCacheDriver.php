<?php
/**
 * @author       Nurlan Mukhanov <nurike@gmail.com>
 * @copyright    2020 Nurlan Mukhanov
 * @license      https://en.wikipedia.org/wiki/MIT_License MIT License
 * @link         https://github.com/Falseclock/dbd-php
 * @noinspection PhpMethodParametersCountMismatchInspection
 */

namespace DBD\Tests\Common;

use DBD\Cache;
use Exception;
use Psr\SimpleCache\InvalidArgumentException;

class BadCacheDriver extends Cache
{
    public function clear(): bool
    {
        return true;
    }

    public function delete($key): bool
    {
        return true;
    }

    public function deleteMultiple($keys): bool
    {
        return true;
    }

    public function get($key, $default = null): mixed
    {
        return $default;
    }

    public function getMultiple($keys, $default = null): iterable
    {
        return [];
    }

    public function has($key): bool
    {
        return false;
    }

    /**
     * @throws Exception
     */
    public function set($key, $value, $ttl = null): bool
    {
        throw new Exception("true");
    }

    public function setMultiple($values, $ttl = null): bool
    {
        return true;
    }

    public function disconnect(): bool
    {
        return true;
    }

    public function connect(): bool
    {
        return true;
    }
}
