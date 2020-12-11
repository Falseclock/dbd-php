<?php
/**
 * Debug
 *
 * @author    Nurlan Mukhanov <nurike@gmail.com>
 * @copyright 2020 Nurlan Mukhanov
 * @license   https://en.wikipedia.org/wiki/MIT_License MIT License
 * @link      https://github.com/Falseclock/dbd-php
 */
declare(strict_types=1);

namespace DBD\Base;

use DBD\Common\Instantiatable;
use DBD\Common\Singleton;

/**
 * Class Debug
 * @package DBD\Base
 */
final class Debug extends Singleton
{
    /** @var float $maxExecutionTime in milliseconds */
    public static $maxExecutionTime = 20;
    /** @var Query[] $queries */
    private static $queries;
    /** @var int $totalQueries */
    private static $totalQueries = 0;
    /** @var float $totalCost */
    private static $totalCost = 0;
    /** @var float $startTime */
    private $startTime = null;

    /**
     * @param Query $queries
     */
    public static function addQueries($queries)
    {
        self::$queries[] = $queries;
    }

    /**
     * @param int|float $cost
     */
    public static function addTotalCost($cost)
    {
        self::$totalCost += $cost;
    }

    /**
     * @param $count
     */
    public static function addTotalQueries($count)
    {
        self::$totalQueries += $count;
    }

    /**
     * @return array
     */
    public static function getPerDriver()
    {
        $return = [];
        if (isset(self::$queries)) {
            foreach (self::$queries as $query) {
                $return[$query->driver][] = $query;
            }
        }

        return $return;
    }

    /**
     * @return Query[]
     */
    public static function getQueries()
    {
        return self::$queries;
    }

    /**
     * @return float
     */
    public static function getTotalCost()
    {
        return self::$totalCost;
    }

    /**
     * @return int
     */
    public static function getTotalQueries()
    {
        return self::$totalQueries;
    }

    /**
     * @return Debug
     */
    public static function me(): Instantiatable
    {
        return Singleton::getInstance(__CLASS__);
    }

    /**
     * @return float
     */
    public function endTimer(): float
    {
        return $this->difference($this->startTime);
    }

    /**
     * @param $start
     * @param null $end
     * @return float
     */
    private function difference($start, $end = null): float
    {
        if (!isset($start)) {
            $start = "0.0 0";
        }
        if (!isset($end)) {
            $end = microtime();
        }
        list($startBase, $startSec) = explode(" ", $start);
        list($endBase, $endSec) = explode(" ", $end);
        $diffSec = intval($endSec) - intval($startSec);
        $diffBase = floatval($endBase) - floatval($startBase);

        return round(((floatval($diffSec) + $diffBase) * 1000), 3);
    }

    /**
     * @return float|string
     */
    public function startTimer()
    {
        $this->startTime = microtime();

        return $this->startTime;
    }
}
