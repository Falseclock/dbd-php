<?php
/**
 * ReferentialConstraint
 *
 * @author       Nurlan Mukhanov <nurike@gmail.com>
 * @copyright    2020 Nurlan Mukhanov
 * @license      https://en.wikipedia.org/wiki/MIT_License MIT License
 * @link         https://github.com/Falseclock/dbd-php
 */
declare(strict_types=1);

namespace DBD\Helpers\OData;

use stdClass;

class ReferentialConstraint
{
    /** @var string */
    public $property;
    /** @var string */
    public $referencedProperty;

    /**
     * ReferentialConstraint constructor.
     * @param stdClass $constraint
     */
    public function __construct(stdClass $constraint)
    {
        $this->property = $constraint->{'@attributes'}->Property;
        $this->referencedProperty = $constraint->{'@attributes'}->ReferencedProperty;
    }
}
