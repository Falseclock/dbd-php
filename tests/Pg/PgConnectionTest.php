<?php
/**
 * PgConnectionTest
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
use DBD\Pg;

class PgConnectionTest extends PgAbstractTest
{
    /**
     * @throws DBDException
     * @see Pg::connect()
     * @see Pg::_connect()
     */
    public function testConnect()
    {
        $this->db->disconnect();
        self::assertInstanceOf(Pg::class, $this->db->connect());
        $this->db->disconnect();

        $this->db->getOptions()->setOnDemand(!$this->db->getOptions()->isOnDemand());
        self::assertInstanceOf(Pg::class, $this->db->connect());

        $this->db->disconnect();
        $this->db->getConfig()->setPort(1);
        self::expectException(DBDException::class);
        $this->db->connect();
    }

    /**
     * @throws DBDException
     * @see Pg::disconnect()
     * @see Pg::_disconnect()
     */
    public function testDisconnect()
    {
        $this->db->do("SELECT 1");
        self::assertTrue($this->db->disconnect());
        $this->db->begin();
        $this->assertException(DBDException::class, function () {
            $this->db->disconnect();
        }, "Uncommitted transaction state");
        $this->db->rollback();

        $this->assertException(DBDException::class, function () {
            /** @noinspection SqlResolve */
            $this->db->query("SELECT FROM disconnest_test");
        });

        self::assertTrue($this->db->disconnect());
    }
}
