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
use DBD\Entity\Primitives\NumericPrimitives;

class Bind
{
    /** @var string */
    public $name;
    /** @var mixed */
    public $value;
    /** @var string */
    public $type;
    /** @var string */
    public $column;

    /**
     * Bind constructor.
     * @param string $name
     * @param mixed $value
     * @param string|null $type
     * @param string|null $originalColumn
     * @throws DBDException
     */
    public function __construct(string $name, $value, ?string $type = null, ?string $originalColumn = null)
    {
        $this->name = $name;
        $this->value = $value;
        $this->type = $type;
        $this->column = $originalColumn;

        switch ($this->type) {
            case NumericPrimitives::Int16:
            case NumericPrimitives::Int32:
            case NumericPrimitives::Int64:
                if (!is_int($this->value) && !is_array($this->value) && !is_null($this->value)) {
                    throw new DBDException("Bound parameter '$name' is not integer type");
                }
                if (is_array($this->value)) {
                    foreach ($this->value as $item) {
                        // check is integer
                        if (!is_int($item) && !is_null($item)) {
                            throw new DBDException("One of value for bound parameter '$name' is not integer type");
                        }
                    }
                }
                break;
            case NumericPrimitives::FLOAT;
            case NumericPrimitives::Double;
                if (!is_float($this->value) && !is_array($this->value) && !is_null($this->value)) {
                    throw new DBDException("Bound parameter '$name' is not float type");
                }
                if (is_array($this->value)) {
                    foreach ($this->value as $item) {
                        // check is float
                        if (!is_float($item) && !is_null($item)) {
                            throw new DBDException("One of value for bound parameter '$name' is not float type");
                        }
                    }
                }
                break;
        }
    }
}
