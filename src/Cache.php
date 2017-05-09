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

namespace DBD;

use DBD\Base\Singleton;
use DBD\Base\Instantiatable;

abstract class Cache extends Singleton implements Instantiatable
{
    /** @var Cache $link */
    public    $link     = null;
    public    $COMPRESS = [];
    public    $EXPIRE   = 1;
    protected $SERVERS  = null;

    abstract public function open();
    abstract public function get($key);
    abstract public function delete($key);
    abstract public function replace($key, $var, $expire);
    abstract public function set($key,$var,$expire);
    abstract public function close();

    public static function me()
    {
        return Singleton::getInstance(get_called_class());
    }

    /**
     * @param array $servers
     * @param bool $compress
     * @param int $expire
     *
     * @return Cache
     */
    public function create($servers = [], $compress = false, $expire = 300)
    {
        $this->SERVERS  = $servers;
        $this->COMPRESS = $compress;
        $this->EXPIRE  = $expire;

        return $this;
    }

    public function getExpire()
    {
        return $this->EXPIRE;
    }

    public static function secCalc($matches)
    {
        if(!$matches || !$matches[1])
        {
            return Cache::getExpire();
        }

        $val  = $matches[1];
        $mult = $matches[2];

        if($mult)
        {
            switch(strtolower($mult))
            {
                case 'm':
                case 'min':
                case 'mins':
                case 'minutes':
                    $val = $val * 60;
                break;

                case 'h':
                case 'hr':
                case 'hour':
                case 'hours':
                    $val = $val * 60 * 60;
                break;

                case 'd':
                case 'day':
                case 'days':
                    $val = $val * 60 * 60 * 24;
                break;

                case 'w':
                case 'week':
                case 'weeks':
                    $val = $val * 60 * 60 * 24 * 7;
                break;

                case 'month':
                case 'monthes':
                    $val = $val * 60 * 60 * 24 * 30;
                break;

                case 'y':
                case 'year':
                case 'years':
                    $val = $val * 60 * 60 * 24 * 365;
                break;

                default:
                case 's':
                case 'sec':
                case 'second':
                case 'seconds':
            }
        }

        return $val;
    }
}