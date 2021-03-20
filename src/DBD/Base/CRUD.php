<?php
/**
 * CRUD
 *
 * @author       Nurlan Mukhanov <nurike@gmail.com>
 * @copyright    2020 Nurlan Mukhanov
 * @license      https://en.wikipedia.org/wiki/MIT_License MIT License
 * @link         https://github.com/Falseclock/dbd-php
 * @noinspection PhpComposerExtensionStubsInspection
 */

declare(strict_types=1);

namespace DBD\Base;

interface CRUD
{
    const CREATE = "INSERT";
    const READ = "SELECT";
    const UPDATE = "UPDATE";
    const DELETE = "DELETE";
}
