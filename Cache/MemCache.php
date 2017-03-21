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

use DBD\Cache\CacheInterface as CacheInterface;
use DBD\Cache as Cache;

class MemCache extends Cache implements CacheInterface{

    /**
     * @return Cache
     */
    public function open()
    {
        $this->link = new \Memcache();

        foreach ($this->SERVERS as $server) {
            $this->link->addServer($server['host'],$server['port']);
        }

        return $this;
    }

    public function close() {
        return $this->link->close();
    }

    public function set($key, $var, $expire = null) {
        if (!$expire)
            $expire = $this->EXPIRE;

        $expire = preg_replace_callback("/(\d+)\s*(.*)?/",Cache::secCalc,$expire);

        return $this->link->set($key, $var, $this->COMPRESS, $expire);
    }

    public function get($key) {
        return $this->link->get($key);
    }

    public function replace($key, $var, $expire = null) {
        if (!$expire)
            $expire = $this->EXPIRE;

        $expire = preg_replace_callback("/(\d+)\s*(.*)?/",Cache::secCalc,$expire);

        // If we trying to replace non exist cache, just set it
        if ( !$this->link->replace($key, $var, $this->COMPRESS, $expire) )
            $this->set($key, $var, $expire);
    }

    public function delete($key) {
        return $this->link->delete($key);
    }

    public function getStats() {
        return $this->link->getStats();
    }

}