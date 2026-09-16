<?php
/**
 * @author    Nurlan Mukhanov <nurike@gmail.com>
 * @copyright 2020 Nurlan Mukhanov
 * @license   https://en.wikipedia.org/wiki/MIT_License MIT License
 * @link      https://github.com/Falseclock/dbd-php
 */

declare(strict_types=1);

namespace DBD\Tests\Cache;

use PHPUnit\Framework\TestCase;

/**
 * Regression test for the psr/simple-cache ^3.0 requirement introduced in 3.1.4.
 *
 * PSR-16 v3 declares typed signatures on Psr\SimpleCache\CacheInterface. If DBD\Cache\MemCache does not
 * match them, PHP raises a compile-time fatal error the moment the class is autoloaded, which cannot be
 * caught inside the test process. The check is therefore executed in a separate PHP process.
 */
class MemCacheCompatibilityTest extends TestCase
{
    public function testMemCacheIsLoadableAgainstInstalledCacheInterface(): void
    {
        $autoload = dirname(__DIR__, 2) . '/vendor/autoload.php';
        $code = 'require $argv[1]; $cache = new \DBD\Cache\MemCache([]); echo $cache instanceof \Psr\SimpleCache\CacheInterface ? "loaded" : "wrong-interface";';

        $command = sprintf(
            '%s -d display_errors=1 -r %s %s 2>&1',
            escapeshellarg(PHP_BINARY),
            escapeshellarg($code),
            escapeshellarg($autoload)
        );

        exec($command, $output, $exitCode);
        $output = trim(implode("\n", $output));

        self::assertSame(0, $exitCode, "DBD\\Cache\\MemCache could not be loaded:\n" . $output);
        self::assertSame('loaded', $output);
    }
}
