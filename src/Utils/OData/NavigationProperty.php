<?php
/**
 * NavigationProperty
 *
 * @author       Nurlan Mukhanov <nurike@gmail.com>
 * @copyright    2020 Nurlan Mukhanov
 * @license      https://en.wikipedia.org/wiki/MIT_License MIT License
 * @link         https://github.com/Falseclock/dbd-php
 */
declare(strict_types=1);

namespace DBD\Utils\OData;

use stdClass;

class NavigationProperty
{
    /** @var string */
    public $name;
    /** @var string */
    public $type;
    /** @var ReferentialConstraint[] */
    public $ReferentialConstraints = [];

    /**
     * NavigationProperty constructor.
     * @param stdClass $property
     */
    public function __construct(stdClass $property)
    {
        $this->name = $property->{'@attributes'}->Name;

        // support v3
        if (isset($property->{'@attributes'}->{'Relationship'}))
            $this->type = $property->{'@attributes'}->{'Relationship'};

        // support v4
        if (isset($property->{'@attributes'}->{'Type'}))
            $this->type = $property->{'@attributes'}->{'Type'};

        if (isset($property->{'ReferentialConstraint'}))
            if (is_array($property->ReferentialConstraint))
                foreach ($property->ReferentialConstraint as $constraint)
                    $this->ReferentialConstraints[] = new ReferentialConstraint($constraint);
            else
                $this->ReferentialConstraints[] = new ReferentialConstraint($property->ReferentialConstraint);
    }
}
