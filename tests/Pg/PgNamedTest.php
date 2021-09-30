<?php
/**
 * PgNamedTest
 *
 * @author    Nurlan Mukhanov <nurike@gmail.com>
 * @copyright 2021 Nurlan Mukhanov
 * @license   https://en.wikipedia.org/wiki/MIT_License MIT License
 * @link      https://github.com/Falseclock/dbd-php
 * @noinspection PhpComposerExtensionStubsInspection
 */

declare(strict_types=1);

namespace DBD\Tests\Pg;

use DBD\Common\DBDException;
use DBD\Entity\Primitives\NumericPrimitives;

/**
 * @see Pg::_prepareNamed()
 * @see Pg::_executeNamed()
 */
class PgNamedTest extends PgAbstractTest
{
    /**
     * @param string|null $name
     * @param array $data
     * @param string $dataName
     * @throws DBDException
     */
    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->db->getOptions()->setPrepareExecute(true)->setConvertNumeric(true);
    }

    /**
     * @throws DBDException
     */
    public function testWithParameters()
    {
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
        $count = 10;

        $sth = $this->db->prepare("SELECT * FROM generate_series(:start, :end) id");
        $sth->bind(":start", 1, NumericPrimitives::Int32)->bind(":end", $count, NumericPrimitives::Int32);
        $sth->cache(__METHOD__ . __LINE__);
        $sth->execute();
        $this->checkResult($sth, $count, []);
    }
}
