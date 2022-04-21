<?php
/**
 * Key
 *
 * @author       Nurlan Mukhanov <nurike@gmail.com>
 * @copyright    2020 Nurlan Mukhanov
 * @license      https://en.wikipedia.org/wiki/MIT_License MIT License
 * @link         https://github.com/Falseclock/dbd-php
 */
declare(strict_types=1);

namespace DBD\Helpers\OData;

class Key
{
    /** @var string */
    public $name;

    /**
     * Key constructor.
     * @param $name
     */
    public function __construct($name)
    {
        $this->name = $name;
    }
}
