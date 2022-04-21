<?php
/**
 * @author    Nurlan Mukhanov <nurike@gmail.com>
 * @copyright 2009-2022 Nurlan Mukhanov
 * @license   https://en.wikipedia.org/wiki/MIT_License MIT License
 * @link      https://github.com/Falseclock/dbd-php
 */

declare(strict_types=1);

namespace DBD\Tests\Cache;

use DateInterval;
use DBD\Cache\MemCache;
use DBD\Common\DBDException;
use DBD\Tests\CommonTest;

class MemCacheTest extends CommonTest
{
    public function testCommon()
    {
        $key = "test_key";
        $hosts = [
            [
                MemCache::HOST => getenv('MEMCACHE_HOST') ?: '127.0.0.1', MemCache::PORT => getenv('MEMCACHE_PORT') ?: 11211
            ]
        ];

        $cache = new MemCache($hosts);
        self::assertTrue($cache->connect());
        self::assertIsBool($cache->clear());
        self::assertTrue($cache->set($key, 1));
        self::assertTrue($cache->has($key));
        self::assertSame(1, $cache->get($key));
        self::assertTrue($cache->delete($key));
        self::assertFalse($cache->get($key, false));

        $this->assertException(DBDException::class, function () use ($cache) {
            $cache->setMultiple([]);
        });

        $this->assertException(DBDException::class, function () use ($cache) {
            $cache->getMultiple([]);
        });

        $this->assertException(DBDException::class, function () use ($cache) {
            $cache->deleteMultiple([]);
        });

        self::assertSame(3600, $cache->getTtl(new DateInterval('PT1H')));
        self::assertSame(3600, $cache->getTtl(3600));
        self::assertSame(3600, $cache->getTtl(3600.0));

        self::assertSame(60, $cache->getTtl("1m"));
        self::assertSame(60, $cache->getTtl("1 min"));
        self::assertSame(60, $cache->getTtl("1mins"));
        self::assertSame(60, $cache->getTtl("1 minute"));
        self::assertSame(60, $cache->getTtl("1minutes"));

        self::assertSame(60 * 60, $cache->getTtl("1h"));
        self::assertSame(60 * 60, $cache->getTtl("1 hr"));
        self::assertSame(60 * 60, $cache->getTtl("1hour"));
        self::assertSame(60 * 60, $cache->getTtl("1 hours"));
        self::assertSame(60 * 60, $cache->getTtl("1hrs"));

        self::assertSame(60 * 60 * 24, $cache->getTtl("1d"));
        self::assertSame(60 * 60 * 24, $cache->getTtl("1 day"));
        self::assertSame(60 * 60 * 24, $cache->getTtl("1days"));

        self::assertSame(60 * 60 * 24 * 7, $cache->getTtl("1w"));
        self::assertSame(60 * 60 * 24 * 7, $cache->getTtl("1 week"));
        self::assertSame(60 * 60 * 24 * 7, $cache->getTtl("1weeks"));

        self::assertSame(60 * 60 * 24 * 30, $cache->getTtl("1mon"));
        self::assertSame(60 * 60 * 24 * 30, $cache->getTtl("1 month"));
        self::assertSame(60 * 60 * 24 * 30, $cache->getTtl("1months"));

        self::assertSame(60 * 60 * 24 * 365, $cache->getTtl("1y"));
        self::assertSame(60 * 60 * 24 * 365, $cache->getTtl("1 year"));
        self::assertSame(60 * 60 * 24 * 365, $cache->getTtl("1years"));

        self::assertSame(2, $cache->getTtl("2s"));
        self::assertSame(3, $cache->getTtl("3 sec"));
        self::assertSame(4, $cache->getTtl("4 secs"));
        self::assertSame(5, $cache->getTtl("5 second"));
        self::assertSame(6, $cache->getTtl("6 seconds"));

        self::assertSame($cache->defaultTtl, $cache->getTtl(true));

        self::assertTrue($cache->disconnect());
        self::assertTrue($cache->disconnect());
    }
}
