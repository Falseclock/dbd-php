<?php
/**
 * @author    Nurlan Mukhanov <nurike@gmail.com>
 * @copyright 2020 Nurlan Mukhanov
 * @license   https://en.wikipedia.org/wiki/MIT_License MIT License
 * @link      https://github.com/Falseclock/dbd-php
 */

declare(strict_types=1);

namespace DBD\Tests;

use DBD\Common\Config;
use DBD\Common\Options;
use DBD\DBD;
use Exception;
use PHPUnit\Framework\TestCase;
use Throwable;

abstract class CommonTest extends TestCase
{
    /** @var DBD */
    protected $db;
    /** @var Options */
    protected $options;
    /** @var Config */
    protected $config;

    /**
     * Asserts that the given callback throws the given exception.
     *
     * @param string $expectClass
     * @param callable $callback
     * @param string|null $expectMessage
     * @return Throwable
     */
    protected function assertException(string $expectClass, callable $callback, ?string $expectMessage = null): Throwable
    {
        $thrown = null;

        self::captureDriverWarnings(function () use ($callback, &$thrown) {
            try {
                $callback();
            } catch (Throwable $exception) {
                $thrown = $exception;
            }
        });

        if (is_null($thrown)) {
            $this->fail('No exception was thrown');
        }

        self::assertInstanceOf($expectClass, $thrown, 'An invalid exception was thrown');
        if (!is_null($expectMessage))
            self::assertSame($expectMessage, $thrown->getMessage());

        return $thrown;
    }

    /**
     * Runs the callback with ext-pgsql warnings captured instead of reported to PHPUnit.
     *
     * ext-pgsql raises E_WARNING for a failed query, prepare or connection attempt in addition to returning false,
     * and DBD turns that failure into a DBDException. Tests that intentionally execute invalid SQL expect the
     * exception; the accompanying driver warning is an implementation detail of the extension. Only warnings coming
     * from pg_* functions are captured, everything else is passed to the previously installed error handler.
     *
     * @param callable $callback
     * @return string[] captured driver warnings
     */
    protected static function captureDriverWarnings(callable $callback): array
    {
        $warnings = [];
        $previous = null;
        $previous = set_error_handler(static function (int $severity, string $message, string $file, int $line) use (&$warnings, &$previous): bool {
            if (str_starts_with($message, 'pg_')) {
                $warnings[] = $message;

                return true;
            }

            return is_callable($previous) ? (bool)$previous($severity, $message, $file, $line) : false;
        }, E_WARNING | E_NOTICE);

        try {
            $callback();
        } finally {
            restore_error_handler();
        }

        return $warnings;
    }
}
