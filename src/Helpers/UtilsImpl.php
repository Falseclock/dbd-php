<?php
/**
 * @author    Nurlan Mukhanov <nurike@gmail.com>
 * @copyright 2020 Nurlan Mukhanov
 * @license   https://en.wikipedia.org/wiki/MIT_License MIT License
 * @link      https://github.com/Falseclock/dbd-php
 */

declare(strict_types=1);

namespace DBD\Helpers;

use DBD\Common\DBDException;
use DBD\Entity\Table;

abstract class UtilsImpl implements UtilsInterface
{
    /**
     * Returns structure of table
     *
     * @param string $tableName
     * @param string $schemeName
     *
     * @return Table
     * @throws DBDException
     */
    public abstract function tableStructure(string $tableName, string $schemeName): Table;
}
