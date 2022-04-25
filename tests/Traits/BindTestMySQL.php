<?php
/**
 * @author    Nurlan Mukhanov <nurike@gmail.com>
 * @copyright 2009-2022 Nurlan Mukhanov
 * @license   https://en.wikipedia.org/wiki/MIT_License MIT License
 * @link      https://github.com/Falseclock/dbd-php
 * @noinspection PhpUnused
 */

declare(strict_types=1);

namespace DBD\Tests\Traits;

use DBD\Common\Bind;
use DBD\Common\CRUD;
use DBD\Common\DBDException;
use DBD\Entity\Primitives\NumericPrimitives;
use DBD\Entity\Primitives\StringPrimitives;
use Exception;

trait BindTestMySQL
{
    public function testBindNotInteger16()
    {
        $this->assertException(DBDException::class, function () {
            new Bind(':some', '1', NumericPrimitives::Int16);
        }, sprintf(CRUD::ERROR_BOUND_IS_NOT_INTEGER, ':some'));
    }

    public function testBindNotInteger32()
    {
        $this->assertException(DBDException::class, function () {
            new Bind(':some', '1', NumericPrimitives::Int32);
        }, sprintf(CRUD::ERROR_BOUND_IS_NOT_INTEGER, ':some'));
    }

    public function testBindNotInteger64()
    {
        $this->assertException(DBDException::class, function () {
            new Bind(':some', '1', NumericPrimitives::Int64);
        }, sprintf(CRUD::ERROR_BOUND_IS_NOT_INTEGER, ':some'));
    }

    public function testBindNotIntegerArray16()
    {
        $this->assertException(DBDException::class, function () {
            new Bind(':some', [1, '1', 2], NumericPrimitives::Int16);
        }, sprintf(CRUD::ERROR_BOUNDS_IS_NOT_INTEGER, ':some'));
    }

    public function testBindNotIntegerArray32()
    {
        $this->assertException(DBDException::class, function () {
            new Bind(':some', [1, '1', 2], NumericPrimitives::Int32);
        }, sprintf(CRUD::ERROR_BOUNDS_IS_NOT_INTEGER, ':some'));
    }

    public function testBindNotIntegerArray64()
    {
        $this->assertException(DBDException::class, function () {
            new Bind(':some', [1, '1', 2], NumericPrimitives::Int64);
        }, sprintf(CRUD::ERROR_BOUNDS_IS_NOT_INTEGER, ':some'));
    }

    /**
     * @throws DBDException
     * @throws Exception
     */
    public function testBind()
    {
        $binary = 'binary';
        $sth = $this->db->prepare("
            SELECT 
            :int as first, 
            :string as seconds, 
            :binary::bytea as third, 
            ?::smallint as fourth, 
            ?::text as fifth,
            :float_val::float as sixes, 
            ARRAY[:int_array] as array_of_int,
            ARRAY[:float_array]::float[] as array_of_float,
            ARRAY[:text_array] as array_of_text
        ")
            ->bind(':int', 1, NumericPrimitives::Int16)
            ->bind(':float_val', 1.00011122, NumericPrimitives::FLOAT)
            ->bind(':string', 'some string')
            ->bind(':binary', $binary, StringPrimitives::Binary)
            ->bind(':int_array', [1, 2, 3, 4, 5], NumericPrimitives::Int16)
            ->bind(':float_array', [1.1111, 2.2222, 3.33333, 4.4444, 5.5555], NumericPrimitives::FLOAT)
            ->bind(':text_array', ['foo', 'bar', 'false', null]);

        $sth->execute(2, 'another string');
        $row = $sth->fetchRow();

        self::assertIsArray($row);
        self::assertEquals(1, $row['first']);
        self::assertSame('some string', $row['seconds']);
        self::assertSame($binary, hex2bin(substr($row['third'], 2)));
        self::assertEquals(2, $row['fourth']);
        self::assertSame('another string', $row['fifth']);
        self::assertSame('{1,2,3,4,5}', $row['array_of_int']);
        self::assertSame('{1.1111,2.2222,3.33333,4.4444,5.5555}', $row['array_of_float']);
        self::assertSame('1.00011122', $row['sixes']);
        self::assertSame('{foo,bar,false,NULL}', $row['array_of_text']);
    }
}
