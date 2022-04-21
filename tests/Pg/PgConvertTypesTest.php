<?php
/**
 * PgConvertTypesTest
 *
 * @author    Nurlan Mukhanov <nurike@gmail.com>
 * @copyright 2021 Nurlan Mukhanov
 * @license   https://en.wikipedia.org/wiki/MIT_License MIT License
 * @link      https://github.com/Falseclock/dbd-php
 * @noinspection PhpComposerExtensionStubsInspection
 */

declare(strict_types=1);

namespace DBD\Tests\Pg;

use DBD\Common\DBDException;

class PgConvertTypesTest extends PgAbstractTest
{
    /**
     * @throws DBDException
     * @see Pg::_convertTypes()
     */
    public function testConvertTypes()
    {
        // ini_set('precision', (string)17);

        $this->db->getOptions()->setConvertBoolean(true);
        $this->db->getOptions()->setConvertNumeric(true);

        $sth = $this->db->query("
            SELECT
                123::INT                                AS value_int,      -- integer
                124::INT2                               AS value_int2,     -- smallint
                125::INT4                               AS value_int4,     -- integer
                126::INT8                               AS value_int8,     -- bigint
                130::SMALLINT                           AS value_smallint, -- smallint
                131::BIGINT                             AS value_bigint,   -- bigint
            
                231.00000000000000001::REAL             AS value_real_0,   -- real 231
                231.11111111111111111::REAL             AS value_real_1,   -- real 231.11111
                232.22222222222222222::REAL             AS value_real_2,   -- real 232.22223
                233.33333333333333333::REAL             AS value_real_3,   -- real 233.33333
                233.44444444444444444::REAL             AS value_real_4,   -- real 233.44444
                233.55555555555555555::REAL             AS value_real_5,   -- real 233.55556
                233.66666666666666666::REAL             AS value_real_6,   -- real 233.66667
                233.77777777777777777::REAL             AS value_real_7,   -- real 233.77777
                233.88888888888888888::REAL             AS value_real_8,   -- real 233.88889
                233.99999999999999999::REAL             AS value_real_9,   -- real 234
                234.01234567890123456::REAL             AS value_real,     -- real 234.01234
                235.987654321::FLOAT4                   AS value_float4,   -- real 235.98766
            
                301.00000000000000001::DOUBLE PRECISION AS value_double_0, -- double 301
                301.11111111111111111::DOUBLE PRECISION AS value_double_1, -- double 301.1111111111111
                301.22222222222222222::DOUBLE PRECISION AS value_double_2, -- double 301.22222222222223
                301.33333333333333333::DOUBLE PRECISION AS value_double_3, -- double 301.3333333333333
                301.44444444444444444::DOUBLE PRECISION AS value_double_4, -- double 301.44444444444446
                301.55555555555555555::DOUBLE PRECISION AS value_double_5, -- double 301.55555555555554
                301.66666666666666666::DOUBLE PRECISION AS value_double_6, -- double 301.6666666666667
                301.77777777777777777::DOUBLE PRECISION AS value_double_7, -- double 301.77777777777777
                301.88888888888888888::DOUBLE PRECISION AS value_double_8, -- double 301.8888888888889
                301.99999999999999999::DOUBLE PRECISION AS value_double_9, -- double 302
            
                337.01234567890123456::FLOAT            AS value_float,    -- double 337.0123456789012
                339.01234567898765432::FLOAT8           AS value_float8,   -- double 339.01234567898763
            
                --'1234567890.0987654321'::DECIMAL        AS value_decimal,  -- numeric
                --'9876543210.9876543210'::NUMERIC        AS value_numeric,  -- numeric
            
                TRUE                                    AS value_true,
                FALSE                                   AS value_false,
                NULL::BOOLEAN                           AS value_null,
                NULL::BOOLEAN                           AS value_null,
                NULL::BOOLEAN                           AS \"VALUE_NULL\",
                TRUE                                    AS value_true,
                FALSE                                   AS value_false
        ");
        $row = $sth->fetchRow();
        self::assertSame(123, $row['value_int']);
        self::assertSame(124, $row['value_int2']);
        self::assertSame(125, $row['value_int4']);
        self::assertSame(126, $row['value_int8']);
        self::assertSame(130, $row['value_smallint']);
        self::assertSame(131, $row['value_bigint']);
        self::assertSame(true, $row['value_true']);
        self::assertSame(false, $row['value_false']);
        self::assertSame(null, $row['value_null']);

        self::assertSame(231.0, $row['value_real_0']);
        self::assertSame(231.11111, $row['value_real_1']);
        self::assertSame(232.22223, $row['value_real_2']);
        self::assertSame(233.33333, $row['value_real_3']);
        self::assertSame(233.44444, $row['value_real_4']);
        self::assertSame(233.55556, $row['value_real_5']);
        self::assertSame(233.66667, $row['value_real_6']);
        self::assertSame(233.77777, $row['value_real_7']);
        self::assertSame(233.88889, $row['value_real_8']);
        self::assertSame(234.0, $row['value_real_9']);
        self::assertSame(234.01234, $row['value_real']);
        self::assertSame(235.98766, $row['value_float4']);

        self::assertSame(301.0, $row['value_double_0']);
        self::assertSame(301.1111111111111, $row['value_double_1']);
        self::assertSame(301.22222222222223, $row['value_double_2']);
        self::assertSame(301.3333333333333, $row['value_double_3']);
        self::assertSame(301.44444444444446, $row['value_double_4']);
        self::assertSame(301.55555555555554, $row['value_double_5']);
        self::assertSame(301.6666666666667, $row['value_double_6']);
        self::assertSame(301.77777777777777, $row['value_double_7']);
        self::assertSame(301.8888888888889, $row['value_double_8']);
        self::assertSame(302.0, $row['value_double_9']);
        self::assertSame(337.0123456789012, $row['value_float']);
        self::assertSame(339.01234567898763, $row['value_float8']);

        $sth->execute();
        while ($column = $sth->fetch()) {
            self::assertTrue(!is_string($column));
        }
    }
}
