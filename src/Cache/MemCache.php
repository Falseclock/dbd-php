<?php
/*************************************************************************************
 *   MIT License                                                                     *
 *                                                                                   *
 *   Copyright (C) 2009-2017 by Nurlan Mukhanov <nurike@gmail.com>                   *
 *                                                                                   *
 *   Permission is hereby granted, free of charge, to any person obtaining a copy    *
 *   of this software and associated documentation files (the "Software"), to deal   *
 *   in the Software without restriction, including without limitation the rights    *
 *   to use, copy, modify, merge, publish, distribute, sublicense, and/or sell       *
 *   copies of the Software, and to permit persons to whom the Software is           *
 *   furnished to do so, subject to the following conditions:                        *
 *                                                                                   *
 *   The above copyright notice and this permission notice shall be included in all  *
 *   copies or substantial portions of the Software.                                 *
 *                                                                                   *
 *   THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR      *
 *   IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,        *
 *   FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE     *
 *   AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER          *
 *   LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,   *
 *   OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE   *
 *   SOFTWARE.                                                                       *
 ************************************************************************************/

namespace DBD\Cache;

use DBD\Cache;

class MemCache extends Cache
{
    /** @var \Memcache $link */
    private $link = null;

    /**
     * Wipes clean the entire cache's keys.
     *
     * @return bool True on success and false on failure.
     */
    public function clear() {
        return $this->link->flush();
    }

    /**
     * Establishes connection to Memcached server
     *
     * @return \DBD\Cache\MemCache
     */
    public function connect() {

        $this->link = new \Memcache;

        foreach($this->servers as $server) {
            $this->link->addServer($server['host'], $server['port']);
        }

        return $this;
    }

    /**
     * Delete an item from the cache by its unique key.
     *
     * @param string $key The unique cache key of the item to delete.
     *
     * @return bool True if the item was successfully removed. False if there was an error.
     *
     */
    public function delete($key) {
        return $this->link->delete($key);
    }

    /**
     * Deletes multiple cache items in a single operation.
     *
     * @param iterable $keys A list of string-based keys to be deleted.
     *
     * @return void True if the items were successfully removed. False if there was an error.
     * @throws \Exception
     */
    public function deleteMultiple($keys) {
        throw new \Exception("Not supported method");
    }

    /**
     * (PECL memcache &gt;= 0.4.0)
     * Close memcached server connection
     *
     * @link https://php.net/manual/en/memcache.close.php
     * @return boolean Returns <b>TRUE</b> on success or <b>FALSE</b> on failure.
     */
    public function disconnect() {
        return $this->link->close();
    }

    /**
     * Fetches a value from the cache.
     *
     * @param string $key     The unique key of this item in the cache.
     * @param mixed  $default Default value to return if the key does not exist.
     *
     * @return mixed The value of the item from the cache, or $default in case of cache miss.
     *
     */
    public function get($key, $default = null) {
        $value = $this->link->get($key);
        if($value === false && isset($default)) {
            return $default;
        }

        return $value;
    }

    /**
     * Obtains multiple cache items by their unique keys.
     *
     * @param iterable $keys    A list of keys that can obtained in a single operation.
     * @param mixed    $default Default value to return for keys that do not exist.
     *
     * @return void A list of key => value pairs. Cache keys that do not exist or are stale will have $default as value.
     * @throws \Exception
     */
    public function getMultiple($keys, $default = null) {
        throw new \Exception("Not supported method");
    }

    /**
     * Determines whether an item is present in the cache.
     *
     * NOTE: It is recommended that has() is only to be used for cache warming type purposes
     * and not to be used within your live applications operations for get/set, as this method
     * is subject to a race condition where your has() will return true and immediately after,
     * another script can remove it making the state of your app out of date.
     *
     * @param string $key The cache item key.
     *
     * @return bool
     *
     */
    public function has($key) {
        return $this->link->get($key) === false ? false : true;
    }

    /**
     * Persists data in the cache, uniquely referenced by a key with an optional expiration TTL time.
     *
     * @param string                       $key   The key of the item to store.
     * @param mixed                        $value The value of the item to store, must be serializable.
     * @param null|int|float|\DateInterval $ttl   Optional. The TTL value of this item. If no value is sent and
     *                                            the driver supports TTL then the library may set a default value
     *                                            for it or let the driver take care of that.
     *
     * @return bool True on success and false on failure.
     *
     */
    public function set($key, $value, $ttl = null) {
        return $this->link->set($key, $value, $this->useCompression, $this->getTtl($ttl));
    }

    /**
     * Persists a set of key => value pairs in the cache, with an optional TTL.
     *
     * @param iterable               $values A list of key => value pairs for a multiple-set operation.
     * @param null|int|\DateInterval $ttl    Optional. The TTL value of this item. If no value is sent and
     *                                       the driver supports TTL then the library may set a default value
     *                                       for it or let the driver take care of that.
     *
     * @return void True on success and false on failure.
     * @throws \Exception
     */
    public function setMultiple($values, $ttl = null) {
        throw new \Exception("Not supported method");
    }
}