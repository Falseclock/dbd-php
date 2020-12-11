<?php
/**
 * TestBaseNullable
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
use DBD\Entity\Primitive;

class TestBaseNullable2 extends Entity implements FullEntity
{
    const SCHEME = "public";
    const TABLE = "TestBaseDefaultValue";
    /**
     * @var int $id
     * @see TestBaseNullable2Map::$id
     */
    public $id;
    /**
     * @var string $name
     * @see TestBaseNullable2Map::$name
     */
    public $name;

    /**
     * @var string $name2
     * @see TestBaseNullable2Map::$name2
     */
    public $name2;
}

class TestBaseNullable2Map extends Mapper implements FullMapper
{
    const ANNOTATION = "Table description";
    /**
     * @var Column
     * @see TestBaseNullable2::$id
     */
    public $id = [
        Column::NAME => "id",
        Column::PRIMITIVE_TYPE => Primitive::Int32,
        Column::IS_AUTO => true,
        Column::NULLABLE => false,
        Column::ANNOTATION => "Unique ID",
        Column::KEY => true,
        Column::ORIGIN_TYPE => "int4",
    ];
    /**
     * @var Column
     * @see TestBaseNullable2::$name
     */
    public $name = [
        Column::NAME => "name",
        Column::PRIMITIVE_TYPE => Primitive::String,
        Column::NULLABLE => true,
        Column::ANNOTATION => "Just name",
        Column::ORIGIN_TYPE => "text",
    ];
    /**
     * @var Column
     * @see TestBaseNullable2::$name2
     */
    public $name2 = [
        Column::NAME => "name2",
        Column::PRIMITIVE_TYPE => Primitive::String,
        Column::NULLABLE => false,
        Column::DEFAULT => "test2",
        Column::ANNOTATION => "Just name",
        Column::ORIGIN_TYPE => "text",
    ];
}
