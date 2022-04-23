<?php
/**
 * @author    Nurlan Mukhanov <nurike@gmail.com>
 * @copyright 2021 Nurlan Mukhanov
 * @license   https://en.wikipedia.org/wiki/MIT_License MIT License
 * @link      https://github.com/Falseclock/dbd-php
 * @noinspection SqlNoDataSourceInspection
 * @noinspection SqlResolve
 * @noinspection SqlWithoutWhere
 * @noinspection PhpUnused
 */

declare(strict_types=1);

namespace DBD\Tests\Traits;

use DBD\Common\DBDException;

/**
 * @see Pg::insert()
 * @see Pg::_compileInsert()
 */
trait InsertTest
{
    /**
     * @throws DBDException
     * @noinspection SqlResolve
     */
    public function testInsert()
    {
        $this->db->query("CREATE TEMPORARY TABLE test_insert (id serial, test varchar(16))");

        $record = [
            'id' => 123,
            'test' => "specific_id",
        ];
        $sth = $this->db->insert('test_insert', $record);
        self::assertFalse($sth->fetchRow());
        self::assertSame(1, $sth->rows());

        $std = $this->db->query("SELECT * FROM test_insert");
        self::assertSame(1, $std->rows());
        $row = $std->fetchRow();
        self::assertSame($record['id'], intval($row['id']));
        self::assertSame($record['test'], $row['test']);

        $record = [
            'test' => "serial_id",
        ];
        $sth = $this->db->insert('test_insert', $record, "*");
        self::assertSame(1, $sth->rows());
        $row = $sth->fetchRow();
        self::assertNotSame(false, $row);
        self::assertSame(1, intval($row['id']));
        self::assertSame($record['test'], $row['test']);
    }
}
