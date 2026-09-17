<?php
/**
 * @author    Nurlan Mukhanov <nurike@gmail.com>
 * @copyright 2020 Nurlan Mukhanov
 * @license   https://en.wikipedia.org/wiki/MIT_License MIT License
 * @link      https://github.com/Falseclock/dbd-php
 */

declare(strict_types=1);

namespace DBD\Tests\Pg;

use DBD\Pg;

/**
 * Exposes the connection state of a Pg instance to characterization tests. No behavior is changed.
 *
 * @see PgEscapeConnectionTest
 */
class PgConnectionProbe extends Pg
{
    public function isConnected(): bool
    {
        return parent::isConnected();
    }

    /**
     * @return resource|\PgSql\Connection|null
     */
    public function getResourceLink()
    {
        return $this->resourceLink;
    }
}
