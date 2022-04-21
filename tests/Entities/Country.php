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
use DBD\Entity\Entity;
use DBD\Entity\Interfaces\FullEntity;
use DBD\Entity\Interfaces\FullMapper;
use DBD\Entity\Mapper;
use DBD\Entity\Primitive;

class Country extends Entity implements FullEntity
{
    const SCHEME = "public";
    const TABLE  = "country";

    public $id;
    public $name;
}

class CountryMap extends Mapper implements FullMapper
{
    const ANNOTATION = "Table description";
    /** @var Column */
    public $id = [
        Column::NAME           => "country_id",
        Column::PRIMITIVE_TYPE => Primitive::Int32,
        Column::IS_AUTO        => false,
        Column::NULLABLE       => false,
        Column::ANNOTATION     => "Unique ID",
        Column::KEY            => true,
        Column::ORIGIN_TYPE    => "int4",
    ];
    /** @var Column */
    public $name = [
        Column::NAME           => "country_name",
        Column::PRIMITIVE_TYPE => Primitive::String,
        Column::NULLABLE       => true,
        Column::ANNOTATION     => "Just name",
        Column::ORIGIN_TYPE    => "text",
    ];
}
