<?php
/**
 * @note         <Description>
 * @copyright    Copyright © Real Time Engineering, LLP - All Rights Reserved
 * @license      Proprietary and confidential
 * Unauthorized copying or using of this file, via any medium is strictly prohibited.
 * Content can not be copied and/or distributed without the express permission of Real Time Engineering, LLP
 * @author       Written by Nurlan Mukhanov <nmukhanov@mp.kz>, сентябрь 2021
 */

declare(strict_types=1);

namespace DBD\Tests;

use PHPUnit\Framework\TestCase;
use Throwable;

class CommonTest extends TestCase
{
    /**
     * Asserts that the given callback throws the given exception.
     *
     * @param string $expectClass The name of the expected exception class
     * @param callable $callback A callback which should throw the exception
     */
    protected function assertException(string $expectClass, callable $callback)
    {
        try {
            $callback();
        } catch (Throwable $exception) {
            $this->assertInstanceOf($expectClass, $exception, 'An invalid exception was thrown');
            return;
        }

        $this->fail('No exception was thrown');
    }
}
