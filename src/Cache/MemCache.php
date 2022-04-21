<?php
/**
 * @author    Nurlan Mukhanov <nurike@gmail.com>
 * @copyright 2020 Nurlan Mukhanov
 * @license   https://en.wikipedia.org/wiki/MIT_License MIT License
 * @link      https://github.com/Falseclock/dbd-php
 * @noinspection PhpComposerExtensionStubsInspection
 */

declare(strict_types=1);

namespace DBD\Cache;

use DateInterval;
use DBD\Cache;
use DBD\Common\DBDException;

class MemCache extends Cache
{
    const HOST = "host";
    const PORT = "port";
    /** @var \Memcache $link */
    private $link = null;

    /**
     * Wipes clean the entire cache's keys.
     *
     * @return bool True on success and false on failure.
     */
    public function clear(): bool
    {
        return $this->link->flush();
    }

    /**
     * Establishes connection to Memcached server
     *
     * @return bool
     */
    public function connect(): bool
    {
        $this->link = new \Memcache;

        foreach ($this->servers as $server) {
            $this->link->addServer($server[self::HOST], $server[self::PORT]);
        }

        return true;
    }

    /**
     * Delete an item from the cache by its unique key.
     *
     * @param string $key The unique cache key of the item to delete.
     *
     * @return bool True if the item was successfully removed. False if there was an error.
     */
    public function delete($key): bool
    {
        return $this->link->delete($key);
    }

    /**
     * Deletes multiple cache items in a single operation.
     *
     * @param iterable $keys A list of string-based keys to be deleted.
     *
     * @return void True if the items were successfully removed. False if there was an error.
     * @throws DBDException
     */
    public function deleteMultiple($keys)
    {
        throw new DBDException("Not supported method");
    }

    /**
     * (PECL memcache &gt;= 0.4.0)
     * Close memcached server connection
     *
     * @link https://php.net/manual/en/memcache.close.php
     * @return boolean Returns <b>TRUE</b> on success or <b>FALSE</b> on failure.
     */
    public function disconnect(): bool
    {
        $state = true;

        if (!is_null($this->link))
            $state = $this->link->close();

        $this->link = null;

        return $state;
    }

    /**
     * Obtains multiple cache items by their unique keys.
     *
     * @param iterable $keys A list of keys that can obtained in a single operation.
     * @param mixed $default Default value to return for keys that do not exist.
     *
     * @return void A list of key => value pairs. Cache keys that do not exist or are stale will have $default as value.
     * @throws DBDException
     */
    public function getMultiple($keys, $default = null)
    {
        throw new DBDException("Not supported method");
    }

    /**
     * Determines whether an item is present in the cache.
     * NOTE: It is recommended that has() is only to be used for cache warming type purposes
     * and not to be used within your live applications operations for get/set, as this method
     * is subject to a race condition where your has() will return true and immediately after,
     * another script can remove it making the state of your app out of date.
     *
     * @param string $key The cache item key.
     *
     * @return bool
     */
    public function has($key): bool
    {
        return !($this->link->get($key) === false);
    }

    /**
     * Fetches a value from the cache.
     *
     * @param string $key The unique key of this item in the cache.
     * @param mixed $default Default value to return if the key does not exist.
     *
     * @return mixed The value of the item from the cache, or $default in case of cache miss.
     */
    public function get($key, $default = null)
    {
        $value = $this->link->get($key);
        if ($value === false) {
            return $default;
        }

        return $value;
    }

    /**
     * Persists data in the cache, uniquely referenced by a key with an optional expiration TTL time.
     *
     * @param string $key The key of the item to store.
     * @param mixed $value The value of the item to store, must be serializable.
     * @param null|int|float|DateInterval $ttl Optional. The TTL value of this item. If no value is sent and
     *                                            the driver supports TTL then the library may set a default value
     *                                            for it or let the driver take care of that.
     *
     * @return bool True on success and false on failure.
     */
    public function set($key, $value, $ttl = null): bool
    {
        return $this->link->set($key, $value, $this->useCompression ? MEMCACHE_COMPRESSED : 0, $this->getTtl($ttl));
    }

    /**
     * Persists a set of key => value pairs in the cache, with an optional TTL.
     *
     * @param iterable $values A list of key => value pairs for a multiple-set operation.
     * @param null|int|DateInterval $ttl Optional. The TTL value of this item. If no value is sent and
     *                                       the driver supports TTL then the library may set a default value
     *                                       for it or let the driver take care of that.
     *
     * @return void True on success and false on failure.
     * @throws DBDException
     */
    public function setMultiple($values, $ttl = null)
    {
        throw new DBDException("Not supported method");
    }
}
