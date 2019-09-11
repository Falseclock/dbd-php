<?php
/**
 * <description should be written here>
 *
 * @package      DBD\Base
 * @copyright    Copyright © Real Time Engineering, LLP - All Rights Reserved
 * @license      Proprietary and confidential
 * Unauthorized copying or using of this file, via any medium is strictly prohibited.
 * Content can not be copied and/or distributed without the express permission of Real Time Engineering, LLP
 *
 * @author       Written by Nurlan Mukhanov <nmukhanov@mp.kz>, сентябрь 2019
 */

namespace DBD\Base;

namespace DBD\Base;

final class Query
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