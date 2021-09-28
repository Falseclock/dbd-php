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

/**
 * @see Pg::rows()
 * @see Pg::_rows()
 */
class PgRowsTest extends PgAbstractTest
{
    /**
     * @throws DBDException
     * @noinspection SqlResolve
     */
    public function testRowsCountAfterFetch()
    {
        $check = 3;
        // Test regular
        self::assertInstanceOf(Pg::class, $this->db->query("CREATE TEMPORARY TABLE test_rows_count (id serial, test int)"));
        for ($i = 1; $i <= $check; $i++) {
            self::assertInstanceOf(Pg::class, $this->db->query("INSERT INTO test_rows_count (test) VALUES (?)", $i));
        }

        // Check through fetchRow()
        $sth = $this->db->prepare("SELECT * FROM test_rows_count");
        $sth->execute();

        for ($i = 1; $i <= $check; $i++) {
            $sth->fetchRow();
            self::assertSame($check, $sth->rows());
        }

        // Check through fetchRowSet()
        $sth->execute();
        self::assertSame($check, $sth->rows());
        self::assertCount($check, $sth->fetchRowSet());
        self::assertSame($check, $sth->rows());

        // Check through fetch()
        $sth->execute();
        while ($sth->fetch()) {
            self::assertSame($check, $sth->rows());
        }
    }

    /**
     * @throws DBDException
     * @noinspection SqlResolve
     * @noinspection SqlWithoutWhere
     */
    public function testRows()
    {
        $sth = $this->db->query("SELECT 1 UNION SELECT 2 UNION SELECT 3");
        self::assertSame(3, $sth->rows());
        $sth->cache(__METHOD__ . __LINE__)->execute();
        self::assertSame(3, $sth->rows());

        $sth = $this->db->query("SELECT 1");
        self::assertSame(1, $sth->rows());
        $sth->cache(__METHOD__ . __LINE__)->execute();
        self::assertSame(1, $sth->rows());

        $sth = $this->db->query("DROP TABLE IF EXISTS fake_table");
        self::assertSame(0, $sth->rows());

        $sth = $this->db->prepare("SELECT 1 UNION SELECT 2 UNION SELECT 3")->execute();
        self::assertSame(3, $sth->rows());
        $sth->cache(__METHOD__ . __LINE__)->execute();
        self::assertSame(3, $sth->rows());

        $sth = $this->db->prepare("SELECT 1")->execute();
        self::assertSame(1, $sth->rows());
        $sth->cache(__METHOD__ . __LINE__)->execute();
        self::assertSame(1, $sth->rows());

        $sth = $this->db->prepare("DROP TABLE IF EXISTS fake_table")->execute();
        self::assertSame(0, $sth->rows());

        // Test through prepare
        $sth = $this->db->prepare("CREATE TEMPORARY TABLE test_rows AS SELECT test, MD5(random()::text) from generate_series(1,10) test")->execute();
        self::assertSame(10, $sth->rows());
        self::assertSame(0, $this->db->do("DROP TABLE test_rows"));

        // Test through do
        self::assertSame(10, $this->db->do("CREATE TEMPORARY TABLE test_rows AS SELECT test, MD5(random()::text) from generate_series(1,10) test"));
        self::assertSame(0, $this->db->do("DROP TABLE test_rows"));

        // Test through query
        $sth = $this->db->query("CREATE TEMPORARY TABLE test_rows AS SELECT test, MD5(random()::text) from generate_series(1,10) test");
        self::assertSame(10, $sth->rows());

        // Test through prepare
        $sth = $this->db->prepare("SELECT * FROM test_rows")->execute();
        self::assertSame(10, $sth->rows());
        $sth->cache(__METHOD__ . __LINE__)->execute();
        self::assertSame(10, $sth->rows());

        // Test through do
        self::assertSame(10, $this->db->do("SELECT * FROM test_rows"));

        // Test through query
        $sth = $this->db->query("SELECT * FROM test_rows");
        self::assertSame(10, $sth->rows());
        $sth->cache(__METHOD__ . __LINE__)->execute();
        self::assertSame(10, $sth->rows());

        // -------------- UPDATE --------------

        // Test through prepare
        $sth = $this->db->prepare("UPDATE test_rows SET test = null");
        $sth->execute();
        self::assertSame(10, $sth->rows());

        // Test through do
        self::assertSame(10, $this->db->do("UPDATE test_rows SET test = null"));

        // Test through query
        $sth = $this->db->query("UPDATE test_rows SET test = null");
        self::assertSame(10, $sth->rows());

        // -------------- DELETION --------------

        // Test through prepare
        $sth = $this->db->prepare("DELETE FROM test_rows");
        $sth->execute();
        self::assertSame(10, $sth->rows());

        // Test through do
        self::assertSame(10, $this->db->do("INSERT INTO test_rows (test, md5) SELECT test, MD5(random()::text) from generate_series(1,10) test"));
        self::assertSame(10, $this->db->do("DELETE FROM test_rows"));

        // Test through query
        self::assertSame(10, $this->db->do("INSERT INTO test_rows (test, md5) SELECT test, MD5(random()::text) from generate_series(1,10) test"));
        $sth = $this->db->query("DELETE FROM test_rows");
        self::assertSame(10, $sth->rows());

        self::assertSame(0, $this->db->do("DROP TABLE test_rows"));
    }
}
