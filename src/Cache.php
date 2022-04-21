<?php
/**
 * @author    Nurlan Mukhanov <nurike@gmail.com>
 * @copyright 2020 Nurlan Mukhanov
 * @license   https://en.wikipedia.org/wiki/MIT_License MIT License
 * @link      https://github.com/Falseclock/dbd-php
 */

declare(strict_types=1);

namespace DBD;

use DateInterval;
use DateTimeImmutable;
use Psr\SimpleCache\CacheInterface;

abstract class Cache implements CacheInterface
{
    const DEFAULT_TTL = 10;
    /** @var bool If caching server supports compression */
    public $useCompression = false;
    /** @var int */
    public $defaultTtl = self::DEFAULT_TTL;
    /** @var array Servers list with variable options */
    public $servers = null;

    /**
     * @param array $servers
     * @param bool $useCompression
     * @param int $defaultTtl
     *
     * @return $this
     */
    public function __construct(array $servers, bool $useCompression = false, int $defaultTtl = self::DEFAULT_TTL)
    {
        $this->servers = $servers;
        $this->useCompression = $useCompression;
        $this->defaultTtl = $defaultTtl;

        return $this;
    }

    /**
     *
     */
    public function __destruct()
    {
        $this->disconnect();
    }

    /**
     * Close cached server connection
     *
     * @return boolean Returns <b>TRUE</b> on success or <b>FALSE</b> on failure.
     */
    abstract public function disconnect(): bool;

    /**
     * Opens cached server connection
     *
     * @return bool Returns <b>TRUE</b> on success or <b>FALSE</b> on failure.
     */
    abstract public function connect(): bool;

    /**
     * @param null|int|float|DateInterval|string
     *
     * @return int
     */
    public function getTtl($ttl)
    {
        if (is_null($ttl))
            return $this->defaultTtl;

        if ($ttl instanceof DateInterval) {
            $reference = new DateTimeImmutable();
            $endTime = $reference->add($ttl);
            return $endTime->getTimestamp() - $reference->getTimestamp();
        }

        if (is_int($ttl))
            return $ttl;

        if (is_float($ttl))
            return intval($ttl);

        if (is_string($ttl) && preg_match("/\s*(\d+)\s*(.*)?/", $ttl, $matches)) {

            $value = intval($matches[1]);
            $multiplier = strtolower(trim($matches[2]));

            if ($multiplier) {
                switch ($multiplier) {
                    case 'm':
                    case 'min':
                    case 'mins':
                    case 'minute':
                    case 'minutes':
                        return $value * 60;

                    case 'h':
                    case 'hr':
                    case 'hrs':
                    case 'hour':
                    case 'hours':
                        return $value * 60 * 60;

                    case 'd':
                    case 'day':
                    case 'days':
                        return $value * 60 * 60 * 24;

                    case 'w':
                    case 'week':
                    case 'weeks':
                        return $value * 60 * 60 * 24 * 7;

                    case 'mon':
                    case 'month':
                    case 'months':
                        return $value * 60 * 60 * 24 * 30;

                    case 'y':
                    case 'year':
                    case 'years':
                        return $value * 60 * 60 * 24 * 365;

                    default:
                    case 's':
                    case 'sec':
                    case 'secs':
                    case 'second':
                    case 'seconds':
                        return $value;
                }
            }
        }

        return $this->defaultTtl;
    }
}
