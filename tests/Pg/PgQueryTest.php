<?php
/**
 * PgQueryTest
 *
 * @author    Nurlan Mukhanov <nurike@gmail.com>
 * @copyright 2020 Nurlan Mukhanov
 * @license   https://en.wikipedia.org/wiki/MIT_License MIT License
 * @link      https://github.com/Falseclock/dbd-php
 * @noinspection PhpComposerExtensionStubsInspection
 */

declare(strict_types=1);

namespace DBD\Tests\Pg;

use DBD\Common\DBDException;
use DBD\Pg;

/**
 * @see Pg::query()
 * @see Pg::_query()
 */
class PgQueryTest extends PgAbstractTest
{

    /**
     * @throws DBDException
     * @noinspection SqlResolve
     * @noinspection SqlWithoutWhere
     */
    public function testQuery()
    {
        // Test regular
        self::assertInstanceOf(Pg::class, $this->db->query("CREATE TEMPORARY TABLE test_query (id serial, test int)"));
        self::assertInstanceOf(Pg::class, $this->db->query("INSERT INTO test_query (test) VALUES (1)"));
        self::assertInstanceOf(Pg::class, $this->db->query("INSERT INTO test_query (test) VALUES (1),(1),(1)"));
        self::assertInstanceOf(Pg::class, $this->db->query("SELECT * FROM test_query"));
        self::assertInstanceOf(Pg::class, $this->db->query("UPDATE test_query SET test = 2"));
        self::assertInstanceOf(Pg::class, $this->db->query("SELECT * FROM test_query WHERE test = 2"));
        self::assertInstanceOf(Pg::class, $this->db->query("DELETE FROM test_query"));
        self::assertInstanceOf(Pg::class, $this->db->query("SELECT * FROM test_query"));

        // Test placeholder
        self::assertInstanceOf(Pg::class, $this->db->query("INSERT INTO test_query (test) VALUES (?)", 1));
        self::assertInstanceOf(Pg::class, $this->db->query("INSERT INTO test_query (test) VALUES (?),(?),(?)", 1, 1, 1));
        self::assertInstanceOf(Pg::class, $this->db->query("SELECT * FROM test_query"));
        self::assertInstanceOf(Pg::class, $this->db->query("UPDATE test_query SET test = ?", 2));
        self::assertInstanceOf(Pg::class, $this->db->query("SELECT * FROM test_query WHERE test = 2"));
        self::assertInstanceOf(Pg::class, $this->db->query("DELETE FROM test_query"));
        self::assertInstanceOf(Pg::class, $this->db->query("SELECT * FROM test_query"));

        self::assertInstanceOf(Pg::class, $this->db->query("DROP TABLE test_query"));

        self::expectException(DBDException::class);
        $this->db->query();
    }
}
