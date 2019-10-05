<?php

namespace Falseclock\DBD\Base;

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