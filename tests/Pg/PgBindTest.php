<?php
/**
 * PgBindTest
 *
 * @author    Nurlan Mukhanov <nurike@gmail.com>
 * @copyright 2021 Nurlan Mukhanov
 * @license   https://en.wikipedia.org/wiki/MIT_License MIT License
 * @link      https://github.com/Falseclock/dbd-php
 * @noinspection PhpComposerExtensionStubsInspection
 */

declare(strict_types=1);

namespace DBD\Tests\Pg;

use DBD\Common\Bind;
use DBD\Common\DBDException;
use DBD\Entity\Primitives\NumericPrimitives;
use DBD\Entity\Primitives\StringPrimitives;
use Exception;

class PgBindTest extends PgAbstractTest
{
    public function testBindNotInteger16()
    {
        self::expectException(DBDException::class);
        new Bind(':some', '1', NumericPrimitives::Int16);
    }

    public function testBindNotInteger32()
    {
        self::expectException(DBDException::class);
        new Bind(':some', '1', NumericPrimitives::Int32);
    }

    public function testBindNotInteger64()
    {
        self::expectException(DBDException::class);
        new Bind(':some', '1', NumericPrimitives::Int64);
    }

    public function testBindNotIntegerArray16()
    {
        self::expectException(DBDException::class);
        new Bind(':some', [1, '1', 2], NumericPrimitives::Int16);
    }

    public function testBindNotIntegerArray32()
    {
        self::expectException(DBDException::class);
        new Bind(':some', [1, '1', 2], NumericPrimitives::Int32);
    }

    public function testBindNotIntegerArray64()
    {
        self::expectException(DBDException::class);
        new Bind(':some', [1, '1', 2], NumericPrimitives::Int64);
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
