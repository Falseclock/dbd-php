<?php
/**
 * @author    Nurlan Mukhanov <nurike@gmail.com>
 * @copyright 2020 Nurlan Mukhanov
 * @license   https://en.wikipedia.org/wiki/MIT_License MIT License
 * @link      https://github.com/Falseclock/dbd-php
 */

declare(strict_types=1);

namespace DBD\Helpers;

use DBD\DBD;
use DBD\Entity\Constraint;
use DBD\Entity\Table;

interface UtilsInterface
{
    /**
     * DBDUtils constructor.
     *
     * @param DBD $dbDriver
     */
    public function __construct(DBD $dbDriver);

    /**
     * @param Table $table
     *
     * @return Constraint[]
     */
    function getTableConstraints(Table $table): array;
}
