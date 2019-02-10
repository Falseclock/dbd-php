<?php
/*************************************************************************************
 *   MIT License                                                                     *
 *                                                                                   *
 *   Copyright (C) 2009-2017 by Nurlan Mukhanov <nurike@gmail.com>                   *
 *                                                                                   *
 *   Permission is hereby granted, free of charge, to any person obtaining a copy    *
 *   of this software and associated documentation files (the "Software"), to deal   *
 *   in the Software without restriction, including without limitation the rights    *
 *   to use, copy, modify, merge, publish, distribute, sublicense, and/or sell       *
 *   copies of the Software, and to permit persons to whom the Software is           *
 *   furnished to do so, subject to the following conditions:                        *
 *                                                                                   *
 *   The above copyright notice and this permission notice shall be included in all  *
 *   copies or substantial portions of the Software.                                 *
 *                                                                                   *
 *   THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR      *
 *   IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,        *
 *   FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE     *
 *   AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER          *
 *   LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,   *
 *   OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE   *
 *   SOFTWARE.                                                                       *
 ************************************************************************************/

namespace DBD\Base;

use DBD\Base\DBDPHPInstantiatable as Instantiatable;
use DBD\Base\DBDPHPSingleton as Singleton;

final class DBDQuery
{
    public $query;
    public $cost;
    public $caller;
    public $mark;
    public $driver;

    public function __construct($query, $cost, $caller, $mark, $driver) {
        $this->query = $query;
        $this->cost = $cost;
        $this->caller = $caller;
        $this->mark = $mark;
        $this->driver = $driver;
    }
}

final class DBDDebug extends Singleton implements Instantiatable
{
    /** @var DBDQuery[] $queries */
    private static $queries;
    /** @var int $totalQueries */
    private static $totalQueries = 0;
    /** @var float $totalCost */
    private static $totalCost = 0;
    /** @var float $startTime */
    private $startTime = null;

    /**
     * @return DBDDebug
     * @throws \Exception
     */
    public static function me() {
        return Singleton::getInstance(__CLASS__);
    }

    /**
     * @return DBDQuery[]
     */
    public static function getQueries() {
        return self::$queries;
    }

    /**
     * @param DBDQuery $queries
     */
    public static function addQueries($queries) {
        self::$queries[] = $queries;
    }

    /**
     * @return int
     */
    public static function getTotalQueries() {
        return self::$totalQueries;
    }

    /**
     * @param $count
     */
    public static function addTotalQueries($count) {
        self::$totalQueries += $count;
    }

    /**
     * @return float
     */
    public static function getTotalCost() {
        return self::$totalCost;
    }

    /**
     * @param int|float $cost
     */
    public static function addTotalCost($cost) {
        self::$totalCost += $cost;
    }

    /**
     * @return array
     */
    public static function getPerDriver() {
        $return = [];
        foreach(self::$queries as $query) {
            $return[$query->driver][] = $query;
        }

        return $return;
    }

    public function endTimer() {
        return $this->difference($this->startTime);
    }

    private function difference($start, $end = null) {
        if(!isset($start)) {
            $start = "0.0 0";
        }
        if(!isset($end)) {
            $end = microtime();
        }
        list($startBase, $startSec) = explode(" ", $start);
        list($endBase, $endSec) = explode(" ", $end);
        $diffSec = intval($endSec) - intval($startSec);
        $diffBase = floatval($endBase) - floatval($startBase);

        return round(((floatval($diffSec) + $diffBase) * 1000), 3);
    }

    public function startTimer() {
        $this->startTime = microtime();

        return $this->startTime;
    }
}