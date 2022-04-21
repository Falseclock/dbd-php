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
}
