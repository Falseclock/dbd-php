<?php
/**
 * @note         <Description>
 * @copyright    Copyright © Real Time Engineering, LLP - All Rights Reserved
 * @license      Proprietary and confidential
 * Unauthorized copying or using of this file, via any medium is strictly prohibited.
 * Content can not be copied and/or distributed without the express permission of Real Time Engineering, LLP
 * @author       Written by Nurlan Mukhanov <nmukhanov@mp.kz>, март 2021
 */

declare(strict_types=1);


namespace DBD\Tests\Odata;


use DBD\Common\DBDException;

class OdataUnsupportedTest extends OdataTest
{
    /**
     * @throws DBDException
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
