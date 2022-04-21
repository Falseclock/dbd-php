<?php
/**
 * @author       Nurlan Mukhanov <nurike@gmail.com>
 * @copyright    2020 Nurlan Mukhanov
 * @license      https://en.wikipedia.org/wiki/MIT_License MIT License
 * @link         https://github.com/Falseclock/dbd-php
 * @noinspection PhpMissingReturnTypeInspection
 * @noinspection PhpMethodParametersCountMismatchInspection
 */

namespace DBD\Tests\Common;

use DBD\Cache;
use Exception;
use Psr\SimpleCache\InvalidArgumentException;

class BadCacheDriver extends Cache
{
    public function clear()
    {
    }

    public function delete($key)
    {
    }

    public function deleteMultiple($keys)
    {
    }

    public function get($key, $default = null)
    {
    }

    public function getMultiple($keys, $default = null)
    {
    }

    public function has($key)
    {
    }

    /**
     * @throws Exception
     */
    public function set($key, $value, $ttl = null)
    {
        throw new Exception("true");
    }

    public function setMultiple($values, $ttl = null)
    {
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
