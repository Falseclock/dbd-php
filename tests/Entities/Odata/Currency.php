<?php
/**
 * Currency
 *
 * @author    Nurlan Mukhanov <nurike@gmail.com>
 * @copyright 2021 Nurlan Mukhanov
 * @license   https://en.wikipedia.org/wiki/MIT_License MIT License
 * @link      https://github.com/Falseclock/dbd-php
 */

declare(strict_types=1);

namespace DBD\Tests\Entities\Odata;

use DBD\Entity\Column;
use DBD\Entity\Entity;
use DBD\Entity\Interfaces\FullEntity;
use DBD\Entity\Interfaces\FullMapper;
use DBD\Entity\Mapper;
use DBD\Entity\Primitive;

class Currency extends Entity implements FullEntity
{
    const SCHEME = "StandardODATA";
    const TABLE = "Catalog_Валюты";

    /** @var string */
    public $code;
    /** @var string */
    public $description;
    /** @var string */
    public $fullName;
    /** @var string */
    public $key;
    /** @var string */
    public $letterCode;
    /** @var boolean */
    public $isPredefined;
    /** @var boolean */
    public $downloadFromInternet;
    /** @var string */
    public $russianParameters;
    /** @var boolean */
    public $isDeleted;
    /** @var string */
    public $dataVersion;
    /** @var string */
    public $predefinedDataName;
}

class CurrencyMap extends Mapper implements FullMapper
{
    const ANNOTATION = "Catalog_Валюты";
    /** @var Column */
    public $code = [
        Column::NAME => "Code",
        Column::PRIMITIVE_TYPE => Primitive::String,
        Column::NULLABLE => true,
        Column::ORIGIN_TYPE => "String",
    ];
    /** @var Column */
    public $description = [
        Column::NAME => "Description",
        Column::PRIMITIVE_TYPE => Primitive::String,
        Column::NULLABLE => true,
        Column::ORIGIN_TYPE => "String",
    ];
    /** @var Column */
    public $fullName = [
        Column::NAME => "НаименованиеПолное",
        Column::PRIMITIVE_TYPE => Primitive::String,
        Column::NULLABLE => true,
        Column::ORIGIN_TYPE => "String",
    ];
    /** @var Column */
    public $key = [
        Column::NAME => "Ref_Key",
        Column::PRIMITIVE_TYPE => Primitive::Guid,
        Column::NULLABLE => false,
        Column::ORIGIN_TYPE => "Guid",
        Column::KEY => true,
        Column::IS_AUTO => true,
    ];
    /** @var Column */
    public $letterCode = [
        Column::NAME => "БуквенныйКод",
        Column::PRIMITIVE_TYPE => Primitive::String,
        Column::NULLABLE => true,
        Column::ORIGIN_TYPE => "String",
    ];
    /** @var Column */
    public $downloadFromInternet = [
        Column::NAME => "ЗагружатьКурсИзИнтернета",
        Column::PRIMITIVE_TYPE => Primitive::Boolean,
        Column::NULLABLE => true,
        Column::ORIGIN_TYPE => "Boolean",
    ];
    /** @var Column */
    public $russianParameters = [
        Column::NAME => "ПараметрыПрописиНаРусском",
        Column::PRIMITIVE_TYPE => Primitive::String,
        Column::NULLABLE => true,
        Column::ORIGIN_TYPE => "String",
    ];
    /** @var Column */
    public $isDeleted = [
        Column::NAME => "DeletionMark",
        Column::PRIMITIVE_TYPE => Primitive::Boolean,
        Column::NULLABLE => true,
        Column::ORIGIN_TYPE => "Boolean",
    ];
    /** @var Column */
    public $dataVersion = [
        Column::NAME => "DataVersion",
        Column::PRIMITIVE_TYPE => Primitive::String,
        Column::NULLABLE => true,
        Column::ORIGIN_TYPE => "String",
    ];
    /** @var Column */
    public $predefinedDataName = [
        Column::NAME => "PredefinedDataName",
        Column::PRIMITIVE_TYPE => Primitive::String,
        Column::NULLABLE => true,
        Column::ORIGIN_TYPE => "String",
    ];
    /** @var Column */
    public $isPredefined = [
        Column::NAME => "Predefined",
        Column::PRIMITIVE_TYPE => Primitive::Boolean,
        Column::NULLABLE => true,
        Column::ORIGIN_TYPE => "Boolean",
    ];
}

/*

<EntityType Name="Catalog_Валюты">
<Key>
<PropertyRef Name="Ref_Key" />
</Key>
<Property Name="Ref_Key" Type="Edm.Guid" Nullable="false" />
<Property Name="Predefined" Type="Edm.Boolean" Nullable="true" />
<Property Name="PredefinedDataName" Type="Edm.String" Nullable="true" />
<Property Name="DataVersion" Type="Edm.String" Nullable="true" />
<Property Name="Description" Type="Edm.String" Nullable="true" />
<Property Name="Code" Type="Edm.String" Nullable="true" />
<Property Name="DeletionMark" Type="Edm.Boolean" Nullable="true" />
<Property Name="БуквенныйКод" Type="Edm.String" Nullable="true" />
<Property Name="НаименованиеПолное" Type="Edm.String" Nullable="true" />
<Property Name="ПараметрыПрописиНаРусском" Type="Edm.String" Nullable="true" />
<Property Name="ЗагружатьКурсИзИнтернета" Type="Edm.Boolean" Nullable="true" />
</EntityType>

 */
