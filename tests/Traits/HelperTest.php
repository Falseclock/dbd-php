<?php
/**
 * @author    Nurlan Mukhanov <nurike@gmail.com>
 * @copyright 2009-2022 Nurlan Mukhanov
 * @license   https://en.wikipedia.org/wiki/MIT_License MIT License
 * @link      https://github.com/Falseclock/dbd-php
 * @noinspection SqlNoDataSourceInspection
 * @noinspection SqlResolve
 * @noinspection SqlWithoutWhere
 * @noinspection PhpUnused
 */

declare(strict_types=1);

namespace DBD\Tests\Traits;

use DBD\Common\CRUD;
use DBD\Common\DBDException;
use DBD\Helpers\Helper;
use DBD\Helpers\InsertArguments;
use DBD\Helpers\UpdateArguments;
use DBD\Tests\DBDTest;

trait HelperTest
{
    /**
     * @throws DBDException
     */
    public function testCompileUpdateArguments()
    {
        $this->db->do("DROP TABLE IF EXISTS test_helper");
        $this->db->do("CREATE TABLE IF NOT EXISTS test_helper (id int, name varchar(32), state boolean)");
        $record = [
            'id' => 1,
            'name' => "name",
            'state' => true,
        ];

        $insert = Helper::compileUpdateArgs($record, $this->db);
        self::assertInstanceOf(UpdateArguments::class, $insert);
        $this->db->update("test_helper", $record);

        $record = [
            'id' => [1],
            'name' => ["name"],
            'state' => [false],
        ];

        $insert = Helper::compileUpdateArgs($record, $this->db);
        self::assertInstanceOf(UpdateArguments::class, $insert);
        $this->db->update("test_helper", $record);


        $record = [
            'id' => [1, "int"],
            'name' => ["name", "varchar"],
            'state' => [1, "bool"],
        ];
        $insert = Helper::compileUpdateArgs($record, $this->db);
        self::assertInstanceOf(UpdateArguments::class, $insert);
        $this->db->update("test_helper", $record);

        $lastQuery = end($this->db::$executedStatements);
        self::assertSame("UPDATE test_helper SET id = '1'::int, name = 'name'::varchar, state = '1'::bool", $lastQuery);

        $record = [
            'id' => [1, "int", 2],
            'name' => ["name", "varchar", 3],
            'state' => [1, "bool", 4],
        ];

        $this->assertException(DBDException::class, function () use ($record) {
            Helper::compileUpdateArgs($record, $this->db);
        }, CRUD::ERROR_UNKNOWN_UPDATE_FORMAT);

        $this->db->do("DROP TABLE test_helper");
    }

    /**
     * @throws DBDException
     */
    public function testCompileInsertArguments()
    {
        $this->db->do("DROP TABLE IF EXISTS test_helper");
        $this->db->do("CREATE TABLE IF NOT EXISTS test_helper (id int, name varchar(32), state boolean)");
        $record = [
            'id' => 1,
            'name' => "name",
            'state' => true,
        ];

        $insert = Helper::compileInsertArguments($record, $this->db);
        self::assertInstanceOf(InsertArguments::class, $insert);
        $this->db->insert("test_helper", $record);
        self::assertEquals(1, $this->db->select("SELECT count(*) FROM test_helper"));

        $record = [
            'id' => [1],
            'name' => ["name"],
            'state' => [false],
        ];

        $insert = Helper::compileInsertArguments($record, $this->db);
        self::assertInstanceOf(InsertArguments::class, $insert);
        $this->db->insert("test_helper", $record);
        self::assertEquals(2, $this->db->select("SELECT count(*) FROM test_helper"));

        $record = [
            'id' => [1, "int"],
            'name' => ["name", "varchar"],
            'state' => [1, "bool"],
        ];
        $insert = Helper::compileInsertArguments($record, $this->db);
        self::assertInstanceOf(InsertArguments::class, $insert);
        $this->db->insert("test_helper", $record);

        $lastQuery = end($this->db::$executedStatements);
        self::assertSame("INSERT INTO test_helper (id, name, state) VALUES ('1'::int, 'name'::varchar, '1'::bool) ", $lastQuery);

        self::assertEquals(3, $this->db->select("SELECT count(*) FROM test_helper"));

        $record = [
            'id' => [1, "int", 2],
            'name' => ["name", "varchar", 3],
            'state' => [1, "bool", 4],
        ];

        $this->assertException(DBDException::class, function () use ($record) {
            Helper::compileInsertArguments($record, $this->db);
        }, CRUD::ERROR_UNKNOWN_INSERT_FORMAT);

        self::assertEquals(3, $this->db->select("SELECT count(*) FROM test_helper"));

        $this->db->do("DROP TABLE test_helper");
    }
}
