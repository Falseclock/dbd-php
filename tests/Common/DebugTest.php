<?php
/**
 * @author    Nurlan Mukhanov <nurike@gmail.com>
 * @copyright 2020 Nurlan Mukhanov
 * @license   https://en.wikipedia.org/wiki/MIT_License MIT License
 * @link      https://github.com/Falseclock/dbd-php
 */

declare(strict_types=1);

namespace DBD\Tests\Common;

use DBD\Common\DBDException;
use DBD\Common\Debug;
use DBD\Common\Query;
use DBD\Helpers\Helper;
use DBD\Tests\CommonTest;

class DebugTest extends CommonTest
{
    /**
     * @throws DBDException
     */
    public function testCommon()
    {
        self::assertIsArray(Debug::getQueries());
        $executedQueries = count(Debug::getQueries());

        $rounds = 10;
        $cost = 0.0;
        for ($i = 1; $i <= $rounds; $i++) {
            $cost += $i;
            Debug::storeQuery(new Query(sprintf("SELECT %s", $i), $i, Helper::caller($this), "test"));
            self::assertCount($i + $executedQueries, Debug::getQueries());
        }

        //self::assertSame($cost, Debug::getTotalCost());
        self::assertSame($rounds + $executedQueries, Debug::getTotalQueries());
        //$perDriver = Debug::getPerDriver();
        //self::assertCount(1, $perDriver);
        //self::assertTrue(isset($perDriver['test']));
        //self::assertCount(10, $perDriver['test']);

        Debug::me()->startTimer();
        sleep(1);
        $cost = Debug::me()->endTimer();
        self::assertGreaterThan(1000, $cost);
        self::assertLessThan(2000, $cost);
    }
}
