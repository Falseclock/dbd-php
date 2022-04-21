<?php
/**
 * EntityType
 *
 * @author       Nurlan Mukhanov <nurike@gmail.com>
 * @copyright    2020 Nurlan Mukhanov
 * @license      https://en.wikipedia.org/wiki/MIT_License MIT License
 * @link         https://github.com/Falseclock/dbd-php
 */
declare(strict_types=1);

namespace DBD\Helpers\OData;

use stdClass;

class EntityType
{
    public $name;
    /** @var Key[] */
    public $Keys = [];
    /** @var Property */
    public $Properties = [];
    /** @var Annotation */
    public $Annotation;
    /** @var NavigationProperty[] */
    public $NavigationProperties;

    public function __construct(stdClass $entityType)
    {
        $this->name = $entityType->{'@attributes'}->Name;

        if (isset($entityType->{'Key'}))
            if (is_array($entityType->Key->PropertyRef))
                foreach ($entityType->Key->PropertyRef as $propertyRef)
                    $this->Keys[] = new Key($propertyRef->{'@attributes'}->Name);
            else
                $this->Keys[] = new Key($entityType->Key->PropertyRef->{'@attributes'}->Name);

        if (isset($entityType->{'Property'}))
            if (is_array($entityType->Property))
                foreach ($entityType->Property as $property)
                    $this->Properties[] = new Property($property);
            else
                $this->Properties[] = new Property($entityType->Property);

        if (isset($entityType->{'NavigationProperty'}))
            if (is_array($entityType->NavigationProperty))
                foreach ($entityType->NavigationProperty as $property)
                    $this->NavigationProperties[] = new NavigationProperty($property);
            else
                $this->NavigationProperties[] = new NavigationProperty($entityType->NavigationProperty);
    }
}
