<?php
/*************************************************************************************
 *   MIT License                                                                     *
 *                                                                                   *
 *   Copyright (C) 2009-2019 by Nurlan Mukhanov <nurike@gmail.com>                   *
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
 *   FITNESS FOR A PARTICULAR PURPOSE AND NON-INFRINGEMENT. IN NO EVENT SHALL THE    *
 *   AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER          *
 *   LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,   *
 *   OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE   *
 *   SOFTWARE.                                                                       *
 ************************************************************************************/

namespace DBD;

use DateInterval;
use Falseclock\DBD\Common\DBDException;
use Falseclock\DBD\Common\Singleton;
use Psr\SimpleCache\CacheInterface;

abstract class Cache extends Singleton implements CacheInterface
{
	const DEFAULT_TTL = 10;
	/** @var bool $useCompression if caching server supports compression */
	public $useCompression = false;
	/** @var int $defaultTtl */
	public $defaultTtl = self::DEFAULT_TTL;
	/** @var mixed[] Server list with variable options */
	public $servers = null;

	public function __destruct() {
		$this->disconnect();
	}

	/**
	 * Opens cached server connection
	 *
	 * @return bool Returns <b>TRUE</b> on success or <b>FALSE</b> on failure.
	 */
	abstract public function connect();

	/**
	 * Close cached server connection
	 *
	 * @return boolean Returns <b>TRUE</b> on success or <b>FALSE</b> on failure.
	 */
	abstract public function disconnect();

	/**
	 * @param null|int|float|DateInterval|string
	 *
	 * @return int
	 */
	public function getTtl($ttl) {
		if(!isset($ttl)) {
			return $this->defaultTtl;
		}

		if($ttl instanceof DateInterval) {
			return $ttl->format("%s");
		}

		if(is_int($ttl)) {
			return $ttl;
		}

		if(is_float($ttl)) {
			return intval($ttl);
		}

		if(is_string($ttl)) {
			if(preg_match("/\s*(\d+)\s*(.*)?/", $ttl, $matches)) {
				$value = intval($matches[1]);
				$multiplier = strtolower(trim($matches[2]));

				if($multiplier) {
					switch($multiplier) {
						case 'm':
						case 'min':
						case 'mins':
						case 'minute':
						case 'minutes':
							return $value * 60;
							break;

						case 'h':
						case 'hr':
						case 'hour':
						case 'hours':
							return $value * 60 * 60;
							break;

						case 'd':
						case 'day':
						case 'days':
							return $value * 60 * 60 * 24;
							break;

						case 'w':
						case 'week':
						case 'weeks':
							return $value * 60 * 60 * 24 * 7;
							break;

						case 'mon':
						case 'month':
						case 'months':
							return $value * 60 * 60 * 24 * 30;
							break;

						case 'y':
						case 'year':
						case 'years':
							return $value * 60 * 60 * 24 * 365;
							break;

						default:
						case 's':
						case 'sec':
						case 'second':
						case 'seconds':
							return $value;
					}
				}
			}
		}

		return $this->defaultTtl;
	}

	/**
	 * Disallow to construct, callCache::me()->setup()->connect();
	 *
	 * @return $this
	 * @throws DBDException
	 * @example MemCache::me()->setup()->connect();
	 */
	public static function me() {
		return Singleton::getInstance(get_called_class());
	}

	/**
	 * @param array $servers
	 * @param bool  $useCompression
	 * @param int   $defaultTtl
	 *
	 * @return $this
	 */
	public function setup($servers = [], $useCompression = false, $defaultTtl = self::DEFAULT_TTL) {
		$this->servers = $servers;
		$this->useCompression = $useCompression;
		$this->defaultTtl = $defaultTtl;

		return $this;
	}
}