<?php
/**
 * @author    Nurlan Mukhanov <nurike@gmail.com>
 * @copyright 2020 Nurlan Mukhanov
 * @license   https://en.wikipedia.org/wiki/MIT_License MIT License
 * @link      https://github.com/Falseclock/dbd-php
 */

declare(strict_types=1);

namespace DBD\Base;

use DBD\Common\Singleton;

final class Debug extends Singleton
{
    /** @var float Average query execution time in milliseconds as a standard for comparison */
    public static $maxExecutionTime = 20;
    /** @var Query[] All executed queries */
    private static $queries = [];
    /** @var string */
    private $startTime = null;

    /**
     * @param Query $query
     */
    public static function storeQuery(Query $query): void
    {
        self::$queries[] = $query;
    }

    /**
     * @return array
     */
    public static function getPerDriver(): array
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
     * All executed queries
     * @return Query[]
     */
    public static function getQueries(): array
    {
        return self::$queries;
    }

    /**
     * Time total for all queries
     * @return float
     */
    public static function getTotalCost(): float
    {
        $total = 0;
        foreach (self::$queries as $query)
            $total += $query->cost;

        return $total;
    }

    /**
     * @return int
     */
    public static function getTotalQueries(): int
    {
        return count(self::$queries);
    }

    /**
     * @return float
     */
    public function endTimer(): float
    {
        return $this->difference($this->startTime);
    }

    /**
     * @param      $start
     * @return float
     */
    private function difference($start): float
    {
        $end = null;

        if (!isset($end)) {
            $end = microtime();
        }
        [$startBase, $startSec] = explode(" ", $start);
        [$endBase, $endSec] = explode(" ", (string)$end);
        $diffSec = intval($endSec) - intval($startSec);
        $diffBase = floatval($endBase) - floatval($startBase);

        return round(((floatval($diffSec) + $diffBase) * 1000), 3);
    }

    /**
     * @return string
     */
    public function startTimer(): string
    {
        $this->startTime = microtime();

        return $this->startTime;
    }
}
