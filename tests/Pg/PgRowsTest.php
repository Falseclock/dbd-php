<?php
/**
 * PgRowsTest
 *
 * @author    Nurlan Mukhanov <nurike@gmail.com>
 * @copyright 2021 Nurlan Mukhanov
 * @license   https://en.wikipedia.org/wiki/MIT_License MIT License
 * @link      https://github.com/Falseclock/dbd-php
 */

declare(strict_types=1);

namespace DBD\Tests\Pg;

use DBD\Common\DBDException;
use DBD\Pg;
use DBD\Tests\PgTest;

class PgRowsTest extends PgTest
{
    /**
     * @throws DBDException
     */
    public function testRowsCountAfterFetchRow()
    {
        $this->db->do("DROP TABLE IF EXISTS test_rows_count");
        // Test regular
        self::assertInstanceOf(Pg::class, $this->db->query("CREATE TABLE test_rows_count (id serial, test int)"));
        self::assertInstanceOf(Pg::class, $this->db->query("INSERT INTO test_rows_count (test) VALUES (1)"));
        self::assertInstanceOf(Pg::class, $this->db->query("INSERT INTO test_rows_count (test) VALUES (2)"));
        self::assertInstanceOf(Pg::class, $this->db->query("INSERT INTO test_rows_count (test) VALUES (3)"));

        $sth = $this->db->prepare("SELECT * FROM test_rows_count");
        $sth->execute();

        $rows = $sth->rows();

        self::assertSame(3, $rows);

        $sth->fetchRow();

        $rows = $sth->rows();

        self::assertSame(3, $rows);

        $sth->fetchRowSet();

        $rows = $sth->rows();

        self::assertSame(3, $rows);
    }
}
