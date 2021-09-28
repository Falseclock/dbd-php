<?php
/**
 * PrepareArguments
 *
 * @author       Nurlan Mukhanov <nurike@gmail.com>
 * @copyright    2021 Nurlan Mukhanov
 * @license      https://en.wikipedia.org/wiki/MIT_License MIT License
 * @link         https://github.com/Falseclock/dbd-php
 */

declare(strict_types=1);

namespace DBD\Utils;

final class PrepareArguments
{
    /** @var string  */
    public $statement;
    /** @var array  */
    public $arguments;

    public function __construct(string $statement, array $arguments)
    {
        $this->statement = $statement;
        $this->arguments = $arguments;
    }
}
