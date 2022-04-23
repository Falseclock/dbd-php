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
use DBD\Tests\DBDTest;
use stdClass;

trait UpdateTest
{
    /**
     * @throws DBDException
     * @noinspection SqlResolve
     */
    public function testUpdate()
    {
        $this->db->query("CREATE TEMPORARY TABLE test_update (id serial, test varchar(16))");

        $record = [
            'id' => 123,
            'test' => "specific_id",
        ];
        $sth = $this->db->update('test_update', $record);
        self::assertFalse($sth->fetchRow());
        self::assertSame(0, $sth->rows());

        $this->db->insert('test_update', $record);

        $sth = $this->db->update('test_update', $record);
        self::assertFalse($sth->fetchRow());
        self::assertSame(1, $sth->rows());

        $sth = $this->db->update('test_update', $record, null, null, "*");
        self::assertSame(1, $sth->rows());
        $rows = $sth->fetchRowSet();
        self::assertNotSame(false, $rows);
        self::assertCount(1, $rows);
        foreach ($rows as $row) {
            self::assertSame($record['id'], intval($row['id']));
            self::assertSame($record['test'], $row['test']);
        }

        $sth = $this->db->update('test_update', $record, "id=1", null, "*");
        self::assertSame(0, $sth->rows());
        self::assertFalse($sth->fetchRow());


        $sth = $this->db->update('test_update', ['id' => 1], "id=?", $record['id'], "*");
        self::assertSame(1, $sth->rows());
        $rows = $sth->fetchRowSet();
        self::assertIsNotBool($rows);

        foreach ($rows as $row) {
            self::assertSame(1, intval($row['id']));
            self::assertSame($record['test'], $row['test']);
        }

        $sth = $this->db->update('test_update', ['id' => 1], "id=? or id=?", 1, 2);
        self::assertFalse($sth->fetchRow());

        $sth = $this->db->update('test_update', ['id' => 1], "id=? or id=?", [1, 2]);
        self::assertFalse($sth->fetchRow());

        $this->assertException(DBDException::class, function () {
            $this->db->update('test_update', ['id' => 1], "id=? or id=?", [1, new stdClass()]);
        });

        $sth = $this->db->update('test_update', ['id' => 1], "id=? or id=?", [1, 2], "*");
        self::assertIsNotBool($sth->fetchRow());

        $sth = $this->db->update('test_update', ['id' => 1], "id=? or id=?", 1, 2, "*");
        self::assertIsNotBool($sth->fetchRow());
    }
}
