<?php
/**
 * @author    Nurlan Mukhanov <nurike@gmail.com>
 * @copyright 2020 Nurlan Mukhanov
 * @license   https://en.wikipedia.org/wiki/MIT_License MIT License
 * @link      https://github.com/Falseclock/dbd-php
 * @noinspection SqlResolve
 */

declare(strict_types=1);

namespace DBD\Tests;

use PHPUnit\Framework\TestCase;
use Throwable;

abstract class CommonTest extends TestCase
{
    /**
     * Asserts that the given callback throws the given exception.
     *
     * @param string $expectClass
     * @param callable $callback
     * @param string|null $expectMessage
     * @return Throwable|void
     */
    protected function assertException(string $expectClass, callable $callback, string $expectMessage = null): Throwable
    {
        try {
            $callback();
        } catch (Throwable $exception) {
            self::assertInstanceOf($expectClass, $exception, 'An invalid exception was thrown');
            if (!is_null($expectMessage))
                self::assertSame($expectMessage, $exception->getMessage());

            return $exception;
        }

        $this->fail('No exception was thrown');
    }
}
