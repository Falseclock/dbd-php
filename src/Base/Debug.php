<?php
/*************************************************************************************
 *   MIT License                                                                     *
 *                                                                                   *
 *   Copyright (C) 2009-2019 by Nurlan Mukhanov <nurike@gmail.com>                   *
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
 *   FITNESS FOR A PARTICULAR PURPOSE AND NON-INFRINGEMENT. IN NO EVENT SHALL THE    *
 *   AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER          *
 *   LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,   *
 *   OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE   *
 *   SOFTWARE.                                                                       *
 ************************************************************************************/

namespace DBD\Base;

use DBD\Common\Singleton;

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
	public static function addQueries($queries) {
		self::$queries[] = $queries;
	}

	/**
	 * @param int|float $cost
	 */
	public static function addTotalCost($cost) {
		self::$totalCost += $cost;
	}

	/**
	 * @param $count
	 */
	public static function addTotalQueries($count) {
		self::$totalQueries += $count;
	}

	public function endTimer() {
		return $this->difference($this->startTime);
	}

	/**
	 * @return array
	 */
	public static function getPerDriver() {
		$return = [];
		if(isset(self::$queries)) {
			foreach(self::$queries as $query) {
				$return[$query->driver][] = $query;
			}
		}

		return $return;
	}

	/**
	 * @return Query[]
	 */
	public static function getQueries() {
		return self::$queries;
	}

	/**
	 * @return float
	 */
	public static function getTotalCost() {
		return self::$totalCost;
	}

	/**
	 * @return int
	 */
	public static function getTotalQueries() {
		return self::$totalQueries;
	}

	/**
	 * @return Debug
	 */
	public static function me() {
		return Singleton::getInstance(__CLASS__);
	}

	public function startTimer() {
		$this->startTime = microtime();

		return $this->startTime;
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
}