<?php
/**
 * Config
 *
 * @author    Nurlan Mukhanov <nurike@gmail.com>
 * @copyright 2020 Nurlan Mukhanov
 * @license   https://en.wikipedia.org/wiki/MIT_License MIT License
 * @link      https://github.com/Falseclock/dbd-php
 */

namespace DBD\Base;

use DBD\Cache;
use Psr\SimpleCache\CacheInterface;

final class Config
{
    /** @var string $dsn */
    private $dsn;
    /** @var int $port */
    private $port;
    /** @var string $database */
    private $database;
    /** @var string $username */
    private $username;
    /** @var string $password */
    private $password;
    /** @var CacheInterface $cacheDriver */
    private $cacheDriver = null;

    /**
     * Config constructor.
     * @param $dsn
     * @param $port
     * @param $database
     * @param $username
     * @param $password
     */
    public function __construct(string $dsn, int $port, string $database, string $username, string $password)
    {
        $this->dsn = $dsn;
        $this->port = $port;
        $this->database = $database;
        $this->username = $username;
        $this->password = $password;
    }

    /**
     * @return Cache|CacheInterface
     */
    public function getCacheDriver()
    {
        return $this->cacheDriver;
    }

    /**
     * @param Cache|CacheInterface $cacheDriver
     *
     * @return Config
     */
    public function setCacheDriver(CacheInterface $cacheDriver): Config
    {
        $this->cacheDriver = $cacheDriver;

        return $this;
    }

    /**
     * @return string
     */
    public function getDatabase(): string
    {
        return $this->database;
    }

    /**
     * @param string $database
     *
     * @return Config
     */
    public function setDatabase(string $database): Config
    {
        $this->database = $database;

        return $this;
    }

    /**
     * @return string
     */
    public function getDsn(): string
    {
        return $this->dsn;
    }

    /**
     * @param string $dsn
     *
     * @return Config
     */
    public function setDsn(string $dsn): Config
    {
        $this->dsn = $dsn;

        return $this;
    }

    /**
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @param string $password
     *
     * @return Config
     */
    public function setPassword(string $password): Config
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @return int
     */
    public function getPort(): int
    {
        return $this->port;
    }

    /**
     * @param int $port
     *
     * @return Config
     */
    public function setPort(int $port): Config
    {
        $this->port = $port;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getUsername(): ?string
    {
        return $this->username;
    }

    /**
     * @param string $username
     *
     * @return Config
     */
    public function setUsername(string $username): Config
    {
        $this->username = $username;

        return $this;
    }
}
