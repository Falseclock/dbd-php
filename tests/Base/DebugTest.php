<?php
/**
 * @author    Nurlan Mukhanov <nurike@gmail.com>
 * @copyright 2020 Nurlan Mukhanov
 * @license   https://en.wikipedia.org/wiki/MIT_License MIT License
 * @link      https://github.com/Falseclock/dbd-php
 */

declare(strict_types=1);

namespace DBD\Tests\Base;

use DBD\Base\Debug;
use DBD\Base\Helper;
use DBD\Base\Query;
use DBD\Common\DBDException;
use DBD\Tests\CommonTest;

class DebugTest extends CommonTest
{
    /**
     * @throws DBDException
     */
    public function testCommon()
    {
        self::assertIsArray(Debug::getQueries());
        self::assertCount(0, Debug::getQueries());

        $cost = 0.0;
        for ($i = 1; $i <= 10; $i++) {
            $cost += $i;
            Debug::storeQuery(new Query(sprintf("SELECT %s", $i), $i, Helper::caller($this), "test"));
            self::assertCount($i, Debug::getQueries());
        }

        self::assertSame($cost, Debug::getTotalCost());
        self::assertSame(10, Debug::getTotalQueries());
        $perDriver = Debug::getPerDriver();
        self::assertCount(1, $perDriver);
        self::assertTrue(isset($perDriver['test']));
        self::assertCount(10, $perDriver['test']);

        Debug::me()->startTimer();
        sleep(1);
        $cost = Debug::me()->endTimer();
        self::assertGreaterThan(1000, $cost);
        self::assertLessThan(2000, $cost);
    }
}
