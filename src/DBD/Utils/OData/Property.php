<?php
/**
 * Property
 *
 * @author       Nurlan Mukhanov <nurike@gmail.com>
 * @copyright    2020 Nurlan Mukhanov
 * @license      https://en.wikipedia.org/wiki/MIT_License MIT License
 * @link         https://github.com/Falseclock/dbd-php
 */

declare(strict_types=1);

namespace DBD\Utils\OData;

use stdClass;

class Property
{
    public $name;
    public $type;
    public $nullable = true;

    public function __construct(stdClass $property) {

        $this->name = $property->{'@attributes'}->Name;
        $this->type = $property->{'@attributes'}->Type;
        if (isset($property->{'@attributes'}->{'Nullable'}))
            $this->nullable = filter_var($property->{'@attributes'}->{'Nullable'}, FILTER_VALIDATE_BOOLEAN);
    }
}
