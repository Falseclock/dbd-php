<?php
/**
 * @author    Nurlan Mukhanov <nurike@gmail.com>
 * @copyright 2009-2022 Nurlan Mukhanov
 * @license   https://en.wikipedia.org/wiki/MIT_License MIT License
 * @link      https://github.com/Falseclock/dbd-php
 */

declare(strict_types=1);

namespace DBD\Tests\Pg;

use DBD\Common\DBDException;
use DBD\Tests\Entities\TestBaseJson;

class PgEntityTest extends PgAbstractTest
{
    public function testForJsonConversion()
    {
        $array = ['foo' => true, 'bar' => false];

        $entity = new TestBaseJson();
        $entity->value = $array;
        $entity->id = 1;

        $this->assertException(DBDException::class, function () use ($entity) {
            $this->db->entityUpdate($entity);
        });

        self::assertIsString($entity->value);
        self::assertEquals(json_encode($array, JSON_UNESCAPED_UNICODE), $entity->value);
        self::assertSame(1, $entity->id);
    }
}
