<?php
/**
 * BindTest
 *
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
    public function testWrongTypes()
    {
        $this->assertException(DBDException::class, function () {
            new Bind(':name', "value", NumericPrimitives::FLOAT);
        });
        $this->assertException(DBDException::class, function () {
            new Bind(':name', ["value"], NumericPrimitives::FLOAT);
        });

    }
}