<?php
/**
 * OdataBindTest
 *
 * @author    Nurlan Mukhanov <nurike@gmail.com>
 * @copyright 2021 Nurlan Mukhanov
 * @license   https://en.wikipedia.org/wiki/MIT_License MIT License
 * @link      https://github.com/Falseclock/dbd-php
 */

declare(strict_types=1);

namespace DBD\Tests\Odata;

use DateTime;
use DBD\Common\DBDException;
use DBD\Entity\Primitive;

class OdataBindTest extends OdataTest
{
    /**
     * @throws DBDException
     * @noinspection SqlResolve
     * @noinspection SqlRedundantOrderingDirection
     */
    public function testSimpleBind() {
        $sth = $this->db->prepare("
            SELECT 
                   * 
            FROM 
                 Document_ПлатежноеПоручениеВходящее 
            JOIN Контрагент ON TRUE
            JOIN СчетКонтрагента  ON TRUE
            JOIN ВалютаДокумента  ON TRUE
            WHERE
                Контрагент_Key = :agent and
                substringof(:comment, Комментарий) = true  and
                Date < ?
            ORDER BY 
                Date desc, 
                Number asc
            LIMIT 2
        ");
        $sth->bind(':agent', 'f2bc8c7c-1295-11e2-ab18-20cf30f431ff', Primitive::Guid);
        $sth->bind(':comment', 'M&M');
        $sth->execute(date(DateTime::ISO8601));

        $rows = $sth->fetchRowSet();

        self::assertIsArray($rows);
    }
}