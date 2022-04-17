<?php
/**
 * @author    Nurlan Mukhanov <nurike@gmail.com>
 * @copyright 2020 Nurlan Mukhanov
 * @license   https://en.wikipedia.org/wiki/MIT_License MIT License
 * @link      https://github.com/Falseclock/dbd-php
 */

declare(strict_types=1);

namespace DBD\Tests\Base;

use DBD\Base\Bind;
use DBD\Common\DBDException;
use DBD\Entity\Primitives\NumericPrimitives;
use DBD\Tests\CommonTest;

class BindTest extends CommonTest
{
    /**
     * @throws DBDException
     */
    public function testConstruct()
    {
        $bind = new Bind(':name', 1, NumericPrimitives::Int16);
        self::assertSame(1, $bind->value);
        $bind = new Bind(':name', 2, NumericPrimitives::Int32);
        self::assertSame(2, $bind->value);
        $bind = new Bind(':name', 3, NumericPrimitives::Int64);
        self::assertSame(3, $bind->value);

        $bind = new Bind(':name', 3.12345, NumericPrimitives::FLOAT);
        self::assertSame(3.12345, $bind->value);

        $bind = new Bind(':name', 3.12345, NumericPrimitives::Double);
        self::assertSame(3.12345, $bind->value);
    }

    public function testWrongTypes()
    {
        $this->assertException(DBDException::class, function () {
            new Bind(':name', "value", NumericPrimitives::Int16);
        });
        $this->assertException(DBDException::class, function () {
            new Bind(':name', "value", NumericPrimitives::Int32);
        });
        $this->assertException(DBDException::class, function () {
            new Bind(':name', "value", NumericPrimitives::Int64);
        });
        $this->assertException(DBDException::class, function () {
            new Bind(':name', [1, 2, 3, 4, true], NumericPrimitives::Int64);
        });
        $this->assertException(DBDException::class, function () {
            new Bind(':name', "value", NumericPrimitives::FLOAT);
        });
        $this->assertException(DBDException::class, function () {
            new Bind(':name', ["value"], NumericPrimitives::FLOAT);
        });
    }
}
