<?php
/**
 * Query
 *
 * @author    Nurlan Mukhanov <nurike@gmail.com>
 * @copyright 2020 Nurlan Mukhanov
 * @license   https://en.wikipedia.org/wiki/MIT_License MIT License
 * @link      https://github.com/Falseclock/dbd-php
 */

declare(strict_types = 1);

namespace DBD\Base;

/**
 * Class Query
 *
 * @package DBD\Base
 */
final class Query
{
	/**
	 * @var
	 */
	public $caller;
	/**
	 * @var
	 */
	public $cost;
	/**
	 * @var
	 */
	public $driver;
	/**
	 * @var
	 */
	public $mark;
	/**
	 * @var
	 */
	public $query;

	/**
	 * Query constructor.
	 *
	 * @param $query
	 * @param $cost
	 * @param $caller
	 * @param $mark
	 * @param $driver
	 */
	public function __construct($query, $cost, $caller, $mark, $driver) {
		$this->query = $query;
		$this->cost = $cost;
		$this->caller = $caller;
		$this->mark = $mark;
		$this->driver = $driver;
	}
}
