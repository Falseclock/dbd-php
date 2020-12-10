<?php
/**
 * CacheHolder
 *
 * @author    Nurlan Mukhanov <nurike@gmail.com>
 * @copyright 2020 Nurlan Mukhanov
 * @license   https://en.wikipedia.org/wiki/MIT_License MIT License
 * @link      https://github.com/Falseclock/dbd-php
 */

declare(strict_types=1);

namespace DBD\Base;

class CacheHolder
{
    public $key = null;
    public $result = null;
    public $compress = null;
    public $expire = null;

    public function __construct(string $key)
    {
        $this->key = $key;
    }
}
