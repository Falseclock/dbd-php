<?php
/**
 * @author    Nurlan Mukhanov <nurike@gmail.com>
 * @copyright 2020 Nurlan Mukhanov
 * @license   https://en.wikipedia.org/wiki/MIT_License MIT License
 * @link      https://github.com/Falseclock/dbd-php
 * @noinspection PhpComposerExtensionStubsInspection
 * @noinspection SqlNoDataSourceInspection
 */

declare(strict_types=1);

namespace DBD\Tests\Pg;

use DateTime;
use DateTimeZone;
use DBD\Common\DBDException;
use Exception;
use stdClass;

/**
 * @see Pg::escape()
 * @see Pg::_escape()
 * @see Pg::escapeBinary()
 * @see Pg::_escapeBinary()
 */
class PgEscapeTest extends PgAbstractTest
{
    const STRING = "string";
    const INT = 12345;
    const FLOAT = 98765.4321;
    const NUMERIC = "1234567890987654321.1234567890987654321";

    /**
     * @throws DBDException
     * @throws Exception
     */
    public function testEscape()
    {
        $result = $this->db->escapeBinary(null);
        self::assertNull($result);

        $result = $this->db->escapeBinary("");
        self::assertNotNull($result);

        // set timezone
        $timeZone = $this->db->select("SELECT current_setting('TIMEZONE')");
        date_default_timezone_set($timeZone);

        $dateTimeZone = new DateTimeZone($timeZone);

        $timestamp = (new DateTime("now", $dateTimeZone))->format("c");
        $date = (new DateTime("now", $dateTimeZone))->format("Y-m-d");

        self::assertSame("NULL", $this->db->escape(null));
        self::assertSame("'" . self::STRING . "'", $this->db->escape(self::STRING));
        self::assertTrue(is_numeric(self::NUMERIC));
        self::assertSame("'" . self::NUMERIC . "'", $this->db->escape(self::NUMERIC));
        self::assertSame("'" . self::INT . "'", $this->db->escape(self::INT));
        self::assertSame("'" . self::FLOAT . "'", $this->db->escape(self::FLOAT));
        self::assertSame("TRUE", $this->db->escape(true));
        self::assertSame("FALSE", $this->db->escape(false));
        self::assertSame("''", $this->db->escape(''));
        self::assertSame("''''", $this->db->escape("'"));
        self::assertSame("''''''", $this->db->escape("''"));
        self::assertSame("'''value'''", $this->db->escape("'value'"));
        self::assertSame("'$timestamp'", $this->db->escape($timestamp));

        $this->assertException(DBDException::class, function () {
            $this->db->escape(new stdClass());
        });

        $this->assertException(DBDException::class, function () {
            $this->db->escape([]);
        });

        $this->db->do("
            CREATE TEMPORARY TABLE escape_test
            (
                test_text        TEXT,
                test_int         INT,
                test_float       FLOAT,
                test_numeric     NUMERIC,
                test_boolean     BOOL,
                test_date        DATE,
                test_timestamp   TIMESTAMP,
                test_timestamptz TIMESTAMPTZ,
                test_bytea       bytea
            )"
        );

        /** @noinspection SqlResolve */
        $sth = $this->db->prepare("INSERT INTO escape_test (test_text, test_int, test_float, test_numeric, test_boolean, test_date, test_timestamp, test_timestamptz, test_bytea) VALUES (?,?,?,?,?,?,?,?,?) RETURNING *");
        $sth->execute(null, null, null, null, null, null, null, null, null);
        $row = $sth->fetchRow();

        foreach ($row as $column) {
            self::assertNull($column);
        }

        $bytes = random_bytes(5);

        $sth->execute(self::STRING, self::INT, self::FLOAT, self::NUMERIC, true, $date, $timestamp, $timestamp, $this->db->escapeBinary($bytes));
        $row = $sth->fetchRow();

        self::assertSame(self::STRING, $row['test_text']);
        self::assertSame((string)self::INT, $row['test_int']);
        self::assertSame((string)self::FLOAT, $row['test_float']);
        self::assertSame(self::NUMERIC, $row['test_numeric']);
        self::assertSame("t", $row['test_boolean']);
        self::assertSame($date, (new DateTime($row['test_date']))->format("Y-m-d"));
        self::assertSame($timestamp, (new DateTime($row['test_timestamp']))->format("c"));
        self::assertSame($timestamp, (new DateTime($row['test_timestamptz']))->format("c"));
        self::assertSame($bytes, pg_unescape_bytea($row['test_bytea']));

        $sth->execute(self::STRING, self::INT, self::FLOAT, self::NUMERIC, false, $date, $timestamp, $timestamp, null);
        $row = $sth->fetchRow();
        self::assertSame("f", $row['test_boolean']);
    }
}
