<?php
/**
 * @author    Nurlan Mukhanov <nurike@gmail.com>
 * @copyright 2020 Nurlan Mukhanov
 * @license   https://en.wikipedia.org/wiki/MIT_License MIT License
 * @link      https://github.com/Falseclock/dbd-php
 */

declare(strict_types=1);

namespace DBD\Tests\Common;

use DBD\Common\Config;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    private $config;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->config = new Config("dsn", 1, "db", "user", "pass");
    }

    public function testCacheDriver()
    {
        $config = $this->config;
        $cacheDriver = new TestCacheDriver();
        self::assertNull($config->getCacheDriver());
        self::assertInstanceOf(Config::class, $config->setCacheDriver($cacheDriver));
        self::assertNotNull($config->getCacheDriver());
    }

    public function testConstruct()
    {
        $config = new Config("dsn", 1, "db", "user", "pass");
        self::assertSame("dsn", $config->getHost());
        self::assertSame(1, $config->getPort());
        self::assertSame("db", $config->getDatabase());
        self::assertSame("user", $config->getUsername());
        self::assertSame("pass", $config->getPassword());
        self::assertNull($config->getCacheDriver());
    }

    public function testDatabase()
    {
        $config = $this->config;
        self::assertNotNull($config->getDatabase());
        self::assertInstanceOf(Config::class, $config->setDatabase(__METHOD__));
        self::assertSame(__METHOD__, $config->getDatabase());
    }

    public function testDsn()
    {
        $config = $this->config;
        self::assertEmpty($config->getDsn());
        self::assertInstanceOf(Config::class, $config->setDsn(__METHOD__));
        self::assertSame(__METHOD__, $config->getDsn());
    }

    public function testHost()
    {
        $config = $this->config;
        self::assertNotNull($config->getHost());
        self::assertInstanceOf(Config::class, $config->setHost(__METHOD__));
        self::assertSame(__METHOD__, $config->getHost());
    }

    public function testPassword()
    {
        $config = $this->config;
        self::assertNotNull($config->getPassword());
        self::assertInstanceOf(Config::class, $config->setPassword(__METHOD__));
        self::assertSame(__METHOD__, $config->getPassword());
    }

    public function testPort()
    {
        $config = $this->config;
        self::assertNotNull($config->getPort());
        self::assertInstanceOf(Config::class, $config->setPort(222));
        self::assertSame(222, $config->getPort());
    }

    public function testUsername()
    {
        $config = $this->config;
        self::assertNotNull($config->getUsername());
        self::assertInstanceOf(Config::class, $config->setUsername(__METHOD__));
        self::assertSame(__METHOD__, $config->getUsername());
    }
}
