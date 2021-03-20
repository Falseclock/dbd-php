<?php
/**
 * Bind
 *
 * @author    Nurlan Mukhanov <nurike@gmail.com>
 * @copyright 2021 Nurlan Mukhanov
 * @license   https://en.wikipedia.org/wiki/MIT_License MIT License
 * @link      https://github.com/Falseclock/dbd-php
 */

declare(strict_types=1);

namespace DBD\Base;

use DBD\Common\DBDException;
use DBD\Entity\Primitive;

class Bind
{
    /** @var string */
    public $name;
    /** @var mixed */
    public $value;
    /** @var string */
    public $type;

    /**
     * Bind constructor.
     * @param string $name
     * @param mixed $value
     * @param string $type
     * @throws DBDException
     */
    public function __construct(string $name, $value, string $type)
    {
        $this->name = $name;
        $this->value = $value;
        $this->type = $type;

        switch ($this->type) {
            case Primitive::Int16:
            case Primitive::Int32:
            case Primitive::Int64:
                if (!is_int($this->value) && !is_array($this->value))
                    throw new DBDException("Bound parameter '{$name}' is not integer type");

                if (is_array($this->value)) {
                    foreach ($this->value as $item)
                        if (!is_int($item))
                            throw new DBDException("One of value for bound parameter '{$name}' is not integer type");
                }
        }
    }
}
