<?php
/**
 * ConversionMap
 *
 * @author    Nurlan Mukhanov <nurike@gmail.com>
 * @copyright 2021 Nurlan Mukhanov
 * @license   https://en.wikipedia.org/wiki/MIT_License MIT License
 * @link      https://github.com/Falseclock/dbd-php
 * @noinspection PhpComposerExtensionStubsInspection
 */

declare(strict_types=1);

namespace DBD\Helpers;

class ConversionMap
{
    /** @var string[] */
    public $booleans = [];
    /** @var string[] */
    public $floats = [];
    /** @var string[] */
    public $integers = [];

    /**
     * @param string $field
     * @return $this
     */
    public function addBoolean(string $field): ConversionMap
    {
        $this->booleans[] = $field;
        return $this;
    }

    /**
     * @param string $field
     * @return $this
     */
    public function addFloat(string $field): ConversionMap
    {
        $this->floats[] = $field;
        return $this;
    }

    /**
     * @param string $field
     * @return $this
     */
    public function addInteger(string $field): ConversionMap
    {
        $this->integers[] = $field;
        return $this;
    }
}
