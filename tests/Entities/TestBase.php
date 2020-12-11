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
use DBD\Entity\Primitive;

class TestBase extends Entity implements FullEntity
{
    const SCHEME = "public";
    const TABLE = "test_base_entity";
    /**
     * @var int $id
     * @see TestBaseNoAutoMap::$id
     */
    public $id;
    /**
     * @var string $name
     * @see TestBaseNoAutoMap::$name
     */
    public $name;

}

class TestBaseMap extends Mapper implements FullMapper
{
    const ANNOTATION = "Table description";
    /**
     * @var Column
     * @see TestBaseNoAuto::$id
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
     * @see TestBaseNoAuto::$name
     */
    public $name = [
        Column::NAME => "name",
        Column::PRIMITIVE_TYPE => Primitive::String,
        Column::NULLABLE => true,
        Column::ANNOTATION => "Just name",
        Column::ORIGIN_TYPE => "text",
    ];
}
