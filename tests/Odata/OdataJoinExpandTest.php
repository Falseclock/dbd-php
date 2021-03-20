<?php
/**
 * OdataJoinExpandTest
 *
 * @author    Nurlan Mukhanov <nurike@gmail.com>
 * @copyright 2021 Nurlan Mukhanov
 * @license   https://en.wikipedia.org/wiki/MIT_License MIT License
 * @link      https://github.com/Falseclock/dbd-php
 */

declare(strict_types=1);

namespace DBD\Tests\Odata;

use DBD\Common\DBDException;

class OdataJoinExpandTest extends OdataTest
{
    /**
     * @throws DBDException
     * @noinspection SqlResolve
     * @noinspection SqlRedundantOrderingDirection
     */
    public function testJoinExpand()
    {
        $sth = $this->db->prepare("
            SELECT 
                   * 
            FROM 
                 Document_ПлатежноеПоручениеВходящее 
            JOIN Контрагент ON TRUE
            EXPAND СчетКонтрагента, ВалютаДокумента
            WHERE
                DeletionMark = false
            ORDER BY 
                Date desc, 
                Number asc
            LIMIT 2
        ");
        $sth->execute();
        self::assertCount(2, $sth->fetchRowSet());

        $sth->execute();

        $row = $sth->fetchRow();

        self::assertIsArray($row);
        self::assertSame(2, $sth->rows());
    }

    /**
     * @throws DBDException
     * @noinspection SqlResolve
     * @noinspection SqlRedundantOrderingDirection
     */
    public function testExpandJoin()
    {
        $sth = $this->db->prepare("
            SELECT 
                   * 
            FROM 
                 Document_ПлатежноеПоручениеВходящее 
            EXPAND СчетКонтрагента, ВалютаДокумента
            JOIN Контрагент ON TRUE
            WHERE
                DeletionMark = false
            ORDER BY 
                Date desc, 
                Number asc
            LIMIT 2
        ");
        $sth->execute();
        self::assertCount(2, $sth->fetchRowSet());

        $sth->execute();

        $row = $sth->fetchRow();

        self::assertIsArray($row);
        self::assertSame(2, $sth->rows());
    }
}