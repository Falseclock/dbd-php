<?php
/**
 * OdataUnsupportedTest
 *
 * @author    Nurlan Mukhanov <nurike@gmail.com>
 * @copyright 2020 Nurlan Mukhanov
 * @license   https://en.wikipedia.org/wiki/MIT_License MIT License
 * @link      https://github.com/Falseclock/dbd-php
 */

declare(strict_types=1);

namespace DBD\Tests\Odata;

use DBD\Common\DBDException;

class OdataUnsupportedTestCase extends OdataTestCase
{
    /**
     * @throws DBDException
     * @covers \DBD\Odata::_begin
     */
    public function testBegin()
    {
        self::expectException(DBDException::class);
        $this->db->begin();
    }

    /**
     * @throws DBDException
     * @covers \DBD\Odata::_commit
     */
    public function testCommit()
    {
        self::expectException(DBDException::class);
        $this->db->commit();
    }

    /**
     * @throws DBDException
     * @covers \DBD\Odata::_rollback
     */
    public function testRollback()
    {
        self::expectException(DBDException::class);
        $this->db->rollback();
    }
}
