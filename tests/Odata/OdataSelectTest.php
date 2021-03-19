<?php
/**
 * OdataMetadataTest
 *
 * @author    Nurlan Mukhanov <nurike@gmail.com>
 * @copyright 2021 Nurlan Mukhanov
 * @license   https://en.wikipedia.org/wiki/MIT_License MIT License
 * @link      https://github.com/Falseclock/dbd-php
 */

declare(strict_types=1);

namespace DBD\Tests\Odata;

use DBD\Common\DBDException;
use Exception;

class OdataSelectTest extends OdataTest
{
    /**
     * Just selecting and checking fetchRow
     * @throws Exception
     * @noinspection SqlResolve
     * @noinspection SqlRedundantOrderingDirection
     */
    public function testFetchRowWithoutCache()
    {
        $sth = $this->db->prepare("
            SELECT 
                   * 
            FROM 
                 Document_ПлатежноеПоручениеВходящее 
            WHERE
                DeletionMark = false
            ORDER BY 
                Date desc, 
                Number asc
            LIMIT 1
        ");
        $sth->execute();

        self::assertSame(1, $sth->rows());
        $withoutCache1 = $sth->fetchrow();
        self::assertIsArray($withoutCache1);
        self::assertFalse($sth->fetchrow());

        self::assertSame(1, $sth->rows());

        $sth = $this->db->prepare("
            SELECT 
                   * 
            FROM 
                 Document_ПлатежноеПоручениеВходящее 
            WHERE
                DeletionMark = false
            ORDER BY 
                Date desc, 
                Number asc
            LIMIT 2
        ");
        $sth->execute();

        self::assertSame(2, $sth->rows());
        $withoutCache2 = $sth->fetchrow();
        self::assertIsArray($withoutCache2);
        $withoutCache3 = $sth->fetchrow();
        self::assertIsArray($withoutCache3);

        self::assertFalse($sth->fetchrow());
        self::assertSame(2, $sth->rows());

        self::assertNotEquals($withoutCache2, $withoutCache3);
        self::assertSame($withoutCache1, $withoutCache2);
    }

    /**
     * @throws DBDException
     * @noinspection SqlResolve
     * @noinspection SqlRedundantOrderingDirection
     */
    public function testFetchRowWithCache()
    {
        $cacheString1 = $this->randomCacheString();

        $sth = $this->db->prepare("
            SELECT 
                   * 
            FROM 
                 Document_ПлатежноеПоручениеВходящее 
            WHERE
                DeletionMark = false
            ORDER BY 
                Date desc, 
                Number asc
            LIMIT 1
        ");
        $sth->cache($cacheString1);

        $sth->execute();
        self::assertSame(1, $sth->rows());
        $fromDatabase = $sth->fetchrow();

        $sth->execute();
        self::assertSame(1, $sth->rows());
        $fromCache = $sth->fetchrow();

        self::assertSame($fromDatabase, $fromCache);
    }

    /**
     * @throws DBDException
     * @noinspection SqlResolve
     * @noinspection SqlRedundantOrderingDirection
     */
    public function testFetchRowsSetWithoutCache()
    {
        $sth = $this->db->prepare("
            SELECT 
                   * 
            FROM 
                 Document_ПлатежноеПоручениеВходящее 
            WHERE
                DeletionMark = false
            ORDER BY 
                Date desc, 
                Number asc
            LIMIT 10
        ");
        $sth->execute();
        self::assertSame(10, $sth->rows());

        $rows = $sth->fetchRowSet();

        self::assertIsArray($rows);
        self::assertCount(10, $rows);

        // Result should be null, as we fetched everything
        self::assertFalse($sth->fetchRow());
        // But counter still should return actual rows
        self::assertSame(10, $sth->rows());

        // Execute again
        $sth->execute();
        // shift first result
        $row = $sth->fetchRow();
        self::assertIsArray($row);

        // fetch remaining rows
        $rows = $sth->fetchRowSet();
        self::assertIsArray($rows);
        self::assertCount(9, $rows);
    }

    /**
     * @throws DBDException
     * @noinspection SqlResolve
     * @noinspection SqlRedundantOrderingDirection
     */
    public function testFetchWithoutCache()
    {
        $sth = $this->db->prepare("
            SELECT 
                   * 
            FROM 
                 Document_ПлатежноеПоручениеВходящее 
            WHERE
                DeletionMark = false
            ORDER BY 
                Date desc, 
                Number asc
            LIMIT 10
        ");
        $sth->execute();
        self::assertSame(10, $sth->rows());

        $rows = $sth->fetchRowSet();
        self::assertCount(10, $rows);

        // Nothing should be returned as we already fetched everything
        self::assertNull($sth->fetch());

        // Get first row for comparison
        $sth->execute();
        $row = $sth->fetchRow();

        $sth->execute();
        foreach ($row as $key => $value) {
            self::assertSame($value, $sth->fetch());
        }

        self::assertNull($sth->fetch());

        // fetch all remaining rows
        $rows = $sth->fetchRowSet();
        self::assertCount(9, $rows);
    }

    /**
     * @throws DBDException
     * @noinspection SqlResolve
     * @noinspection SqlRedundantOrderingDirection
     */
    public function testWithSpecialChars()
    {
        $sth = $this->db->prepare("
            SELECT 
                   * 
            FROM 
                 Document_ПлатежноеПоручениеВходящее 
            WHERE
                DeletionMark = false and (
                substringof('#',Комментарий) = true or 
                substringof('&',Комментарий) = true or
                substringof('$',Комментарий) = true
                )
            ORDER BY 
                Date desc, 
                Number asc
            LIMIT 10
        ");
        $sth->execute();

        self::assertGreaterThan(0, $sth->rows());
    }

    /**
     * @throws DBDException
     * @noinspection SqlResolve
     * @noinspection SqlRedundantOrderingDirection
     */
    public function testPlaceHolders()
    {
        $sth = $this->db->prepare("
            SELECT 
                   * 
            FROM 
                 Document_ПлатежноеПоручениеВходящее 
            WHERE
                DeletionMark = false and
                substringof('?',Комментарий) = false and
                substringof('?',Комментарий) = false and
                substringof('?',Комментарий) = false
            
            ORDER BY 
                Date desc, 
                Number asc
            LIMIT 10
        ");
        $sth->execute('?', '$', '&');

        self::assertGreaterThan(0, $sth->rows());
    }
}
