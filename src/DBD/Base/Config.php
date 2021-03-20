<?php
/**
 * Config
 *
 * @author    Nurlan Mukhanov <nurike@gmail.com>
 * @copyright 2020 Nurlan Mukhanov
 * @license   https://en.wikipedia.org/wiki/MIT_License MIT License
 * @link      https://github.com/Falseclock/dbd-php
 */

declare(strict_types = 1);

namespace DBD\Base;

use DBD\Cache;
use Psr\SimpleCache\CacheInterface;

final class Config
{
	/** @var CacheInterface */
	public $cacheDriver = null;
	/** @var string */
	private $database;
	/** @var string */
	private $dsn;
	/** @var string */
	private $host;
	/** @var string */
	private $password;
	/** @var int */
	private $port;
	/** @var string */
	private $username;

	/**
	 * Config constructor.
	 *
	 * @param $host
	 * @param $port
	 * @param $database
	 * @param $username
	 * @param $password
	 */
	public function __construct(string $host, ?int $port, ?string $database, string $username, string $password) {
		$this->host = $host;
		$this->port = $port;
		$this->database = $database;
		$this->username = $username;
		$this->password = $password;
	}

	/**
	 * @return Cache|CacheInterface
	 */
	public function getCacheDriver() {
		return $this->cacheDriver;
	}

	/**
	 * @return string
	 */
	public function getDatabase(): string {
		return $this->database;
	}

	/**
	 * @return string
	 */
	public function getDsn(): string {
		return $this->dsn;
	}

	/**
	 * @return string
	 */
	public function getHost(): string {
		return $this->host;
	}

	/**
	 * @return string
	 */
	public function getPassword(): string {
		return $this->password;
	}

	/**
	 * @return int
	 */
	public function getPort(): int {
		return $this->port;
	}

	/**
	 * @return string|null
	 */
	public function getUsername(): ?string {
		return $this->username;
	}

	/**
	 * @param CacheInterface|null $cacheDriver
	 *
	 * @return Config
	 */
	public function setCacheDriver(?CacheInterface $cacheDriver): Config {
		$this->cacheDriver = $cacheDriver;

		return $this;
	}

	/**
	 * @param string $database
	 *
	 * @return Config
	 */
	public function setDatabase(string $database): Config {
		$this->database = $database;

		return $this;
	}

	/**
	 * @param string $dsn
	 *
	 * @return $this
	 */
	public function setDsn(string $dsn): Config {
		$this->dsn = $dsn;

		return $this;
	}

	/**
	 * @param string $host
	 *
	 * @return Config
	 */
	public function setHost(string $host): Config {
		$this->host = $host;

		return $this;
	}

	/**
	 * @param string $password
	 *
	 * @return Config
	 */
	public function setPassword(string $password): Config {
		$this->password = $password;

		return $this;
	}

	/**
	 * @param int $port
	 *
	 * @return Config
	 */
	public function setPort(int $port): Config {
		$this->port = $port;

		return $this;
	}

	/**
	 * @param string $username
	 *
	 * @return Config
	 */
	public function setUsername(string $username): Config {
		$this->username = $username;

		return $this;
	}
}
