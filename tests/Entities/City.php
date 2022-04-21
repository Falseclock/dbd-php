<?php
/**
 * @author    Nurlan Mukhanov <nurike@gmail.com>
 * @copyright 2009-2022 Nurlan Mukhanov
 * @license   https://en.wikipedia.org/wiki/MIT_License MIT License
 * @link      https://github.com/Falseclock/dbd-php
 */

declare(strict_types=1);

namespace DBD\Tests\Entities;

use DBD\Entity\Column;
use DBD\Entity\Constraint;
use DBD\Entity\Entity;
use DBD\Entity\Interfaces\FullEntity;
use DBD\Entity\Interfaces\FullMapper;
use DBD\Entity\Mapper;
use DBD\Entity\Primitive;
use DBD\Entity\Primitives\NumericPrimitives;
use DBD\Entity\Primitives\StringPrimitives;

/**
 * @see CityMap
 */
class City extends Entity implements FullEntity
{
    const SCHEME = "public";
    const TABLE = "city";

    public $id;
    public $name;
    /** @var Country */
    public $Country;
}

class CityMap extends Mapper implements FullMapper
{
    const ANNOTATION = "Table description";
    /** @var Column */
    public $id = [
        Column::NAME => "city_id",
        Column::PRIMITIVE_TYPE => NumericPrimitives::Int32,
        Column::IS_AUTO => false,
        Column::NULLABLE => false,
        Column::ANNOTATION => "Unique ID",
        Column::KEY => true,
        Column::ORIGIN_TYPE => "int4",
    ];
    /** @var Column */
    public $name = [
        Column::NAME => "city_name",
        Column::PRIMITIVE_TYPE => StringPrimitives::String,
        Column::NULLABLE => true,
        Column::ANNOTATION => "Just name",
        Column::ORIGIN_TYPE => "text",
    ];
    /** @var Column */
    public $countryId = [
        Column::NAME           => "country_id",
        Column::PRIMITIVE_TYPE => Primitive::Int32,
        Column::NULLABLE       => true,
        Column::ORIGIN_TYPE    => "int4",
    ];

    /** @var Constraint */
    protected $Country = [
        Constraint::LOCAL_COLUMN => "country_id",
        Constraint::FOREIGN_SCHEME => Country::SCHEME,
        Constraint::FOREIGN_TABLE => Country::TABLE,
        Constraint::FOREIGN_COLUMN => "country_id",
        Constraint::JOIN_TYPE => null,
        Constraint::BASE_CLASS => Country::class,
    ];
}
