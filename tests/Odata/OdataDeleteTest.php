<?php
/**
 * OdataDeleteTest
 *
 * @author    Nurlan Mukhanov <nurike@gmail.com>
 * @copyright 2021 Nurlan Mukhanov
 * @license   https://en.wikipedia.org/wiki/MIT_License MIT License
 * @link      https://github.com/Falseclock/dbd-php
 */

declare(strict_types=1);

namespace DBD\Tests\Odata;

use DBD\Common\DBDException;
use DBD\Tests\Entities\Odata\Currency;

class OdataDeleteTest extends OdataTest
{
    /**
     * @throws DBDException
     */
    public function testEntityDelete()
    {
        $currency = new Currency();
        $currency->isDeleted = false;
        $currency->description = 'THB';
        $currency->letterCode = 'THB';
        $currency->fullName = 'Тайский бат';
        $this->db->entityInsert($currency);

        self::assertNotNull($currency->key);

        $currencyDelete = new Currency();
        $currencyDelete->key = $currency->key;

        self::assertTrue($this->db->entityDelete($currencyDelete));

        self::assertFalse($this->db->entityDelete($currencyDelete));
    }
}
