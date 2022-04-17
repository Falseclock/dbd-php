<?php
/**
 * Caller
 *
 * @author       Nurlan Mukhanov <nurike@gmail.com>
 * @copyright    2020 Nurlan Mukhanov
 * @license      https://en.wikipedia.org/wiki/MIT_License MIT License
 * @link         https://github.com/Falseclock/dbd-php
 * @noinspection PhpComposerExtensionStubsInspection
 */

declare(strict_types=1);

namespace DBD\Utils;

class Caller
{
    /** @var string */
    public $file;
    /** @var int */
    public $line;
    /** @var string */
    public $function;

    public function __construct(string $file, int $line, string $function)
    {
        $this->file = $file;
        $this->line = $line;
        $this->function = $function;
    }
}
