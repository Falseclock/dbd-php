<?php
/**
 * UpdateArguments
 *
 * @author       Nurlan Mukhanov <nurike@gmail.com>
 * @copyright    2021 Nurlan Mukhanov
 * @license      https://en.wikipedia.org/wiki/MIT_License MIT License
 * @link         https://github.com/Falseclock/dbd-php
 */

declare(strict_types=1);

namespace DBD\Utils;

final class UpdateArguments
{
    /** @var string  */
    public $columns;
    /** @var array  */
    public $arguments;

    public function __construct(string $columns, array $arguments)
    {
        $this->columns = $columns;
        $this->arguments = $arguments;
    }
}
