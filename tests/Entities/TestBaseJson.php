<?php
/**
 * Test
 *
 * @author    Nurlan Mukhanov <nurike@gmail.com>
 * @copyright 2020 Nurlan Mukhanov
 * @license   https://en.wikipedia.org/wiki/MIT_License MIT License
 * @link      https://github.com/Falseclock/dbd-php
 */

declare(strict_types=1);

namespace DBD\Tests\Entities;

use DBD\Entity\Column;
use DBD\Entity\Entity;
use DBD\Entity\Interfaces\FullEntity;
use DBD\Entity\Interfaces\FullMapper;
use DBD\Entity\Mapper;
use DBD\Entity\Primitives\NumericPrimitives;
use DBD\Entity\Primitives\StringPrimitives;

class TestBaseJson extends Entity implements FullEntity
{
    const SCHEME = "public";
    const TABLE = "test_base_json";

    public $id;
    /** @see TestBaseJsonMap::$value */
    public $value;
}

class TestBaseJsonMap extends Mapper implements FullMapper
{
    const ANNOTATION = "Table description";
    /** @var Column */
    public $id = [
        Column::NAME => "id",
        Column::PRIMITIVE_TYPE => NumericPrimitives::Int32,
        Column::IS_AUTO => true,
        Column::NULLABLE => false,
        Column::ANNOTATION => "Unique ID",
        Column::KEY => true,
        Column::ORIGIN_TYPE => "int4",
    ];
    /** @var Column */
    public $value = [
        Column::NAME => "value",
        Column::PRIMITIVE_TYPE => StringPrimitives::String,
        Column::NULLABLE => true,
        Column::ANNOTATION => "Just value",
        Column::ORIGIN_TYPE => "json",
    ];
}
