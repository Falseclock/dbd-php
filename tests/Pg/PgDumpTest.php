<?php
/**
 * PgDumpTest
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

class PgDumpTest extends PgAbstractTest
{
    /**
     * @throws DBDException
     * @see Pg::_dump()
     * @see Pg::dump()
     * @noinspection SqlResolve
     */
    public function testDump()
    {
        $this->db->query("CREATE TEMPORARY TABLE test_dump AS select generate_series(1,10) as id, md5(random()::text) hash");
        $sth = $this->db->prepare("SELECT * FROM test_dump");
        $content = $sth->dump();

        self::assertNotNull($content);
        self::assertNotEmpty($content);

        $sth = $this->db->prepare("SELECT * FROM test_dump1");

        /** @var DBDException $exception */
        $exception = $this->assertException(DBDException::class, function () use ($sth) {
            $sth->dump();
        });

        self::assertSame("SELECT * FROM test_dump1", $exception->getQuery());
        self::assertNull($exception->getArguments());
        self::assertIsArray($exception->getShortTrace());
        self::assertIsArray($exception->getFullTrace());
    }
}
