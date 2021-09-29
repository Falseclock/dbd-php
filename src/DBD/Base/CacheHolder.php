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
    /** @var mixed */
    public $expire = null;
    /** @var string */
    public $key = null;
    /** @var mixed */
    public $result = null;

    /**
     * CacheHolder constructor.
     *
     * @param string $key
     */
    public function __construct(string $key)
    {
        $this->key = $key;
    }
}
