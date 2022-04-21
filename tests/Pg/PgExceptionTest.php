<?php
/**
 * @author    Nurlan Mukhanov <nurike@gmail.com>
 * @copyright 2009-2022 Nurlan Mukhanov
 * @license   https://en.wikipedia.org/wiki/MIT_License MIT License
 * @link      https://github.com/Falseclock/dbd-php
 * @noinspection SqlResolve
 * @noinspection SqlNoDataSourceInspection
 */

declare(strict_types=1);

namespace DBD\Tests\Pg;

use DBD\Common\DBDException;

class PgExceptionTest extends PgAbstractTest
{
    public function testCommon()
    {
        /** @var DBDException $exception */
        $exception = $this->assertException(DBDException::class, function () {
            $this->db->select("SELECT * FROM bla WHERE a=?", 1);
        });

        self::assertNotNull($exception);
        self::assertCount(1, $exception->getArguments());
        self::assertIsArray($exception->getFullTrace());
        self::assertIsArray($exception->getShortTrace());
        self::assertSame("SELECT * FROM bla WHERE a='1'", $exception->getQuery());

        $this->db->getOptions()->setPrepareExecute(true);
        /** @var DBDException $exception */

        $exception = $this->assertException(DBDException::class, function () {
            $this->db->select("SELECT * FROM bla WHERE a=?", 1);
        });

        self::assertNotNull($exception->getArguments());
        $arguments = $exception->getArguments();
        self::assertSame(1, array_shift($arguments));
    }
}
