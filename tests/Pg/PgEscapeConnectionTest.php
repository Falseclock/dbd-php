<?php
/**
 * @author    Nurlan Mukhanov <nurike@gmail.com>
 * @copyright 2020 Nurlan Mukhanov
 * @license   https://en.wikipedia.org/wiki/MIT_License MIT License
 * @link      https://github.com/Falseclock/dbd-php
 * @noinspection PhpComposerExtensionStubsInspection
 * @noinspection SqlNoDataSourceInspection
 */

declare(strict_types=1);

namespace DBD\Tests\Pg;

use DBD\Common\DBDException;
use DBD\Common\Options;

/**
 * Pg::_escape() and Pg::_escapeBinary() must escape through the connection of the current Pg instance,
 * opening it on demand exactly like DBD::connectionPreCheck() does for queries. They must never fall back
 * to the implicit PostgreSQL connection (the last one opened in the process), which is what
 * pg_escape_string()/pg_escape_bytea() do when called without a connection (deprecated since PHP 8.1).
 *
 * The expected literals are what PostgreSQL 9.1+ produces with standard_conforming_strings = on (the default):
 * backslashes are not doubled and bytea uses the hex format. With standard_conforming_strings = off the same
 * functions double every backslash, which is how the tests tell two connections apart.
 *
 * @see Pg::_escape()
 * @see Pg::_escapeBinary()
 * @see Pg::_connect()
 */
class PgEscapeConnectionTest extends PgAbstractTest
{
    /** a\b */
    private const BACKSLASH_VALUE = "a\\b";
    /** 'a\b' */
    private const BACKSLASH_ESCAPED = "'a\\b'";
    /** 'a\\b' - produced only through a connection with standard_conforming_strings = off */
    private const BACKSLASH_ESCAPED_NON_STANDARD = "'a\\\\b'";
    /** bytes 00 01 5c */
    private const BINARY_VALUE = "\x00\x01\\";
    /** \x00015c */
    private const BINARY_ESCAPED = "\\x00015c";
    /** \\x00015c - produced only through a connection with standard_conforming_strings = off */
    private const BINARY_ESCAPED_NON_STANDARD = "\\\\x00015c";

    /**
     * @throws DBDException
     */
    public function testEscapeWithOpenConnectionKeepsResults(): void
    {
        $pg = $this->probe();
        $pg->do("SELECT 1");
        self::assertTrue($pg->isConnected());

        self::assertSame(self::BACKSLASH_ESCAPED, $pg->escape(self::BACKSLASH_VALUE));
        self::assertSame("''''", $pg->escape("'"));
        self::assertSame("'12345'", $pg->escape(12345));
        self::assertSame("''", $pg->escape(''));
        self::assertSame(self::BINARY_ESCAPED, $pg->escapeBinary(self::BINARY_VALUE));
        self::assertSame("\\x", $pg->escapeBinary(''));
        self::assertNull($pg->escapeBinary(null));

        $pg->disconnect();
    }

    /**
     * @throws DBDException
     */
    public function testOnDemandConnectionIsOpenedByFirstEscape(): void
    {
        $pg = $this->probe();
        self::assertTrue($pg->getOptions()->isOnDemand());
        self::assertFalse($pg->isConnected(), 'connect() with onDemand must not open the backend connection');

        // values that do not need a connection must not open one
        self::assertSame("NULL", $pg->escape(null));
        self::assertSame("TRUE", $pg->escape(true));
        self::assertSame("FALSE", $pg->escape(false));
        self::assertNull($pg->escapeBinary(null));
        self::assertFalse($pg->isConnected(), 'NULL/TRUE/FALSE and escapeBinary(null) must not connect');

        self::assertSame(self::BACKSLASH_ESCAPED, $pg->escape(self::BACKSLASH_VALUE));
        self::assertTrue($pg->isConnected(), 'the first escape(string) must open the connection of this instance');
        $pg->disconnect();

        $pg = $this->probe();
        self::assertFalse($pg->isConnected());
        self::assertSame(self::BINARY_ESCAPED, $pg->escapeBinary(self::BINARY_VALUE));
        self::assertTrue($pg->isConnected(), 'the first escapeBinary(non-null) must open the connection of this instance');
        $pg->disconnect();
    }

    /**
     * @throws DBDException
     */
    public function testEscapeAfterDisconnectReconnectsLikeQueryPath(): void
    {
        $pg = $this->probe();
        $pg->do("SELECT 1");
        self::assertTrue($pg->disconnect());
        self::assertFalse($pg->isConnected());

        // query path after disconnect(): connectionPreCheck() silently reopens the connection of this instance
        $pg->do("SELECT 1");
        self::assertTrue($pg->isConnected(), 'the query path reconnects after disconnect()');

        self::assertTrue($pg->disconnect());
        self::assertFalse($pg->isConnected());
        self::assertSame(self::BACKSLASH_ESCAPED, $pg->escape(self::BACKSLASH_VALUE));
        self::assertTrue($pg->isConnected(), 'escape() must follow the query path and reconnect this instance');

        self::assertTrue($pg->disconnect());
        self::assertFalse($pg->isConnected());
        self::assertSame(self::BINARY_ESCAPED, $pg->escapeBinary(self::BINARY_VALUE));
        self::assertTrue($pg->isConnected(), 'escapeBinary() must follow the query path and reconnect this instance');

        $pg->disconnect();
    }

    /**
     * @throws DBDException
     */
    public function testEscapeUsesConnectionOfCurrentInstanceNotLastOpened(): void
    {
        // different application_name -> different DSN -> ext-pgsql opens a distinct backend connection
        $first = $this->probe('dbd-php-escape-first');
        $second = $this->probe('dbd-php-escape-second');

        try {
            $first->do("SELECT 1");
            // opened last: this is the implicit connection pg_escape_string()/pg_escape_bytea() fall back to
            $second->do("SET standard_conforming_strings = off");

            self::assertNotSame(
                pg_get_pid($first->getResourceLink()),
                pg_get_pid($second->getResourceLink()),
                'the test needs two distinct backend connections'
            );

            self::assertSame(self::BACKSLASH_ESCAPED, $first->escape(self::BACKSLASH_VALUE));
            self::assertSame(self::BACKSLASH_ESCAPED_NON_STANDARD, $second->escape(self::BACKSLASH_VALUE));
            self::assertSame(self::BINARY_ESCAPED, $first->escapeBinary(self::BINARY_VALUE));
            self::assertSame(self::BINARY_ESCAPED_NON_STANDARD, $second->escapeBinary(self::BINARY_VALUE));
        } finally {
            $second->disconnect();
            $first->disconnect();
        }
    }

    /**
     * A fresh Pg with default Options (onDemand = true). Every instance gets its own Config because
     * Pg::connect() stores the DSN in it.
     *
     * @throws DBDException
     */
    private function probe(string $applicationName = 'DBD-PHP'): PgConnectionProbe
    {
        $options = new Options();
        $options->setApplicationName($applicationName);

        $pg = new PgConnectionProbe(clone $this->config, $options);
        $pg->connect();

        return $pg;
    }
}
