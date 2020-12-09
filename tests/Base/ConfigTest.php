<?php
/**
 * ConfigTest
 *
 * @author    Nurlan Mukhanov <nurike@gmail.com>
 * @copyright 2020 Nurlan Mukhanov
 * @license   https://en.wikipedia.org/wiki/MIT_License MIT License
 * @link      https://github.com/Falseclock/dbd-php
 */

namespace DBD\Tests\Base;

use DBD\Base\Config;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    private $config;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->config = new Config("dsn", 1, "db", "user", "pass");
    }

    public function testConstruct()
    {
        $config = $this->config;
        self::assertEquals("dsn", $config->getDsn());
        self::assertEquals(1, $config->getPort());
        self::assertEquals("db", $config->getDatabase());
        self::assertEquals("user", $config->getUsername());
        self::assertEquals("pass", $config->getPassword());
        self::assertNull($config->getCacheDriver());
    }

    public function testCacheDriver()
    {
        $config = $this->config;
        $cacheDriver = new TestCacheDriver();
        self::assertNull($config->getCacheDriver());
        self::assertInstanceOf(Config::class, $config->setCacheDriver($cacheDriver));
        self::assertNotNull($config->getCacheDriver());
    }

    public function testDatabase()
    {
        $config = $this->config;
        self::assertNotNull($config->getDatabase());
        self::assertInstanceOf(Config::class, $config->setDatabase(__METHOD__));
        self::assertEquals(__METHOD__, $config->getDatabase());
    }

    public function testDsn()
    {
        $config = $this->config;
        self::assertNotNull($config->getDsn());
        self::assertInstanceOf(Config::class, $config->setDsn(__METHOD__));
        self::assertEquals(__METHOD__, $config->getDsn());
    }

    public function testPassword()
    {
        $config = $this->config;
        self::assertNotNull($config->getPassword());
        self::assertInstanceOf(Config::class, $config->setPassword(__METHOD__));
        self::assertEquals(__METHOD__, $config->getPassword());
    }

    public function testPort()
    {
        $config = $this->config;
        self::assertNotNull($config->getPort());
        self::assertInstanceOf(Config::class, $config->setPort(222));
        self::assertEquals(222, $config->getPort());
    }

    public function testUsername()
    {
        $config = $this->config;
        self::assertNotNull($config->getUsername());
        self::assertInstanceOf(Config::class, $config->setUsername(__METHOD__));
        self::assertEquals(__METHOD__, $config->getUsername());
    }
}
