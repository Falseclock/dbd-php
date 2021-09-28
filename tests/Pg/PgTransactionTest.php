<?php
/**
 * PgTransactionTest
 * @note Tests of transactions
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

class PgTransactionTest extends PgBaseTest
{
    /**
     * @throws DBDException
     * @see Pg::begin()
     * @see Pg::_begin()
     */
    public function testBegin()
    {
        // Test successful begin
        $this->db->disconnect();

        self::assertTrue($this->db->begin());
        $sth = $this->db->prepare("SELECT version()");
        $sth->execute();
        self::assertTrue($this->db->commit());

        // Start new transaction and then call it again
        self::assertTrue($this->db->begin());
        $this->assertException(DBDException::class, function () {
            $this->db->begin();
        });
        self::assertTrue($this->db->commit());

        // Test PGSQL_TRANSACTION_INTRANS
        $this->db->do("BEGIN");
        $this->assertException(DBDException::class, function () {
            $this->db->begin();
        });
        $this->db->do("ROLLBACK");

        // Test PGSQL_TRANSACTION_INERROR
        $this->db->do("BEGIN");
        $this->assertException(DBDException::class, function () {
            /** @noinspection SqlResolve */
            $this->db->do("SELECT * FROM unknown_table");
        });
        $this->assertException(DBDException::class, function () {
            $this->db->begin();
        });
    }

    /**
     * @throws DBDException
     * @see Pg::commit()
     * @see Pg::_commit()
     */
    public function testCommit()
    {
        // Test commit without begin
        $this->db->disconnect();
        $this->assertException(DBDException::class, function () {
            $this->db->commit();
        }, "No connection established yet");

        // Test normal case
        $this->db->do("ROLLBACK");
        self::assertTrue($this->db->begin());
        self::assertTrue($this->db->commit());

        // Test transaction fail state
        self::assertTrue($this->db->begin());
        $this->assertException(DBDException::class, function () {
            /** @noinspection SqlResolve */
            $this->db->do("SELECT * FROM unknown_table");
        });
        $this->assertException(DBDException::class, function () {
            $this->db->commit();
        }, "Commit not possible, in a failed transaction block");
        $this->db->rollback();

    }

    /**
     * @throws DBDException
     * @see Pg::rollback()
     * @see Pg::_rollback()
     */
    public function testRollback()
    {
        // starting transaction
        self::assertTrue($this->db->begin());

        // create table
        self::assertSame(0, $this->db->do("CREATE TEMPORARY TABLE test_rollback (id INT)"));

        // check table is created
        $sth = $this->db->prepare("SELECT 'test_rollback'::regclass");
        self::assertInstanceOf(Pg::class, $sth);
        $sth->execute();
        self::assertSame("test_rollback", $sth->fetch());

        // rollback
        self::assertTrue($this->db->rollback());

        // check table not exist
        $this->assertException(DBDException::class, function () {
            $this->db->do("SELECT 'test_rollback'::regclass");
        });

        // Test normal case
        self::assertTrue($this->db->begin());
        self::assertTrue($this->db->rollback());

        // Test transaction fail state
        self::assertTrue($this->db->begin());
        $this->db->do("ROLLBACK;");

        $this->assertException(DBDException::class, function () {
            $this->db->rollback();
        }, "There is no transaction in progress");
    }

    /**
     * @throws DBDException
     * @see Pg::_inTransaction()
     * @see Pg::inTransaction()
     */
    public function testInTransaction()
    {
        self::assertFalse($this->db->inTransaction());
        $this->db->begin();
        self::assertTrue($this->db->inTransaction());
        $this->db->rollback();

        self::assertFalse($this->db->inTransaction());

        $this->db->begin();
        self::assertTrue($this->db->inTransaction());
        $this->db->commit();
        self::assertFalse($this->db->inTransaction());

    }
}
