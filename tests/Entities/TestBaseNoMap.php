<?php
/**
 * @author    Nurlan Mukhanov <nurike@gmail.com>
 * @copyright 2020 Nurlan Mukhanov
 * @license   https://en.wikipedia.org/wiki/MIT_License MIT License
 * @link      https://github.com/Falseclock/dbd-php
 */

declare(strict_types = 1);

namespace DBD\Tests\Entities;

use DBD\Entity\Column;
use DBD\Entity\Entity;
use DBD\Entity\Interfaces\FullEntity;
use DBD\Entity\Interfaces\FullMapper;
use DBD\Entity\Mapper;
use DBD\Entity\Primitive;

class TestBaseNoMap extends Entity implements FullEntity
{
	const SCHEME = "public";
	const TABLE  = "test_base_entity";

	public $id;

	public $name;
}
