<?php
/**
 * Query
 *
 * @author    Nurlan Mukhanov <nurike@gmail.com>
 * @copyright 2020 Nurlan Mukhanov
 * @license   https://en.wikipedia.org/wiki/MIT_License MIT License
 * @link      https://github.com/Falseclock/dbd-php
 */

declare(strict_types=1);

namespace DBD\Common;

use DBD\Helpers\Caller;
use DBD\Helpers\Helper;

final class Query
{
    /** @var Caller */
    public $caller;
    /** @var float */
    public $cost;
    /** @var */
    public $driver;
    /** @var */
    public $mark;
    /** @var */
    public $query;

    /**
     * Query constructor.
     *
     * @param string $query
     * @param float $cost
     * @param Caller $caller
     * @param string $driver
     */
    public function __construct(string $query, float $cost, Caller $caller, string $driver)
    {
        $this->query = $query;
        $this->cost = $cost;
        $this->caller = $caller;
        $this->mark = Helper::debugMark($cost);
        $this->driver = $driver;
    }
}
