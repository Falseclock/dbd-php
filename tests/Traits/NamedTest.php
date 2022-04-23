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
use DBD\Entity\Primitives\NumericPrimitives;

trait NamedTest
{
    /**
     * @throws DBDException
     */
    public function testWithParameters()
    {
        $this->db->getOptions()->setPrepareExecute(true)->setConvertNumeric(true);
        $count = 10;

        $sth = $this->db->prepare("SELECT * FROM generate_series(?::int, ?::int) id");
        $sth->cache(__METHOD__ . __LINE__);
        $sth->execute(1, $count);
        $this->checkResult($sth, $count, [1, $count]);
    }

    /**
     * @param $sth
     * @param int $expectedRows
     * @param array|null $arguments
     */
    protected function checkResult($sth, int $expectedRows, ?array $arguments = null)
    {
        $this->db->getOptions()->setPrepareExecute(true)->setConvertNumeric(true);
        self::assertSame($expectedRows, $sth->rows());
        $noCacheRows = $sth->fetchRowSet();
        self::assertCount($expectedRows, $noCacheRows);
        $i = 1;
        foreach ($noCacheRows as $row) {
            self::assertIsArray($row);
            self::assertSame($i, $row['id']);
            $i++;
        }
        $preparedCount = count($sth::$preparedStatements);
        $executedCount = count($sth::$executedStatements);

        $n = 5;
        while (--$n >= 0)
            $sth->execute($arguments);

        $cachedRows = $sth->fetchRowSet();

        self::assertCount($executedCount, $sth::$executedStatements);
        self::assertCount($preparedCount, $sth::$preparedStatements);
        self::assertSame($noCacheRows, $cachedRows);
    }

    /**
     * @throws DBDException
     */
    public function testWithoutParameters()
    {
        $this->db->getOptions()->setPrepareExecute(true)->setConvertNumeric(true);
        $count = 10;

        $sth = $this->db->prepare("SELECT * FROM generate_series(1, $count) id");
        $sth->cache(__METHOD__ . __LINE__);
        $sth->execute();
        $this->checkResult($sth, $count, []);
    }

    /**
     * @throws DBDException
     */
    public function testWithBinds()
    {
        $this->db->getOptions()->setPrepareExecute(true)->setConvertNumeric(true);
        $count = 10;

        $sth = $this->db->prepare("SELECT * FROM generate_series(:start, :end) id");
        $sth->bind(":start", 1, NumericPrimitives::Int32)->bind(":end", $count, NumericPrimitives::Int32);
        $sth->cache(__METHOD__ . __LINE__);
        $sth->execute();
        $this->checkResult($sth, $count, []);
    }
}
