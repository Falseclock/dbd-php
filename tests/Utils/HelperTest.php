<?php
/**
 * HelperTest
 *
 * @author       Nurlan Mukhanov <nurike@gmail.com>
 * @copyright    2021 Nurlan Mukhanov
 * @license      https://en.wikipedia.org/wiki/MIT_License MIT License
 * @link         https://github.com/Falseclock/dbd-php
 */

declare(strict_types=1);

namespace DBD\Tests\Utils;

use DBD\Base\CRUD;
use DBD\Base\Helper;
use DBD\Common\DBDException;
use DBD\Tests\CommonTest;

class HelperTest extends CommonTest
{
    public function testParseArguments()
    {
        self::assertSame([1, 2, 3], Helper::parseArguments([1, 2, 3]));
        self::assertSame([1, 1, 2, 3, 3], Helper::parseArguments([1, [1, 2, 3], 3]));
        self::assertSame([1, 1, 1, 2, 3, 3, 3], Helper::parseArguments([1, [1, [1, 2, 3], 3], 3]));
        self::assertSame([1, 1, 1, 2, 3, 3], Helper::parseArguments([1, [1, [1, 2], 3], 3]));
        self::assertSame([1, 1, 2, 3, 3], Helper::parseArguments([1, [1, [2], 3], 3]));
    }

    /**
     * @throws DBDException
     * @see Helper::getQueryType()
     */
    public function testGetQueryType()
    {
        foreach (['SELECT' => CRUD::READ, 'UPDATE' => CRUD::UPDATE, 'DELETE' => CRUD::DELETE, 'INSERT' => CRUD::CREATE] as $type => $crud) {

            self::assertSame($crud, Helper::getQueryType("$type some_text"));
            self::assertSame($crud, Helper::getQueryType("
        $type some_text"));
            self::assertSame($crud, Helper::getQueryType("/* comment */$type some_text"));
            self::assertSame($crud, Helper::getQueryType("
        -- comment
        $type some_text"));
            self::assertSame($crud, Helper::getQueryType("
        -- comment
        
        -- comment2
        
        $type some_text"));
            self::assertSame($crud, Helper::getQueryType("
        -- comment
        /* comment */
        $type some_text"));
            self::assertSame($crud, Helper::getQueryType("
        /* comment */
        -- comment
        $type some_text"));
            self::assertSame($crud, Helper::getQueryType("
        /* comment */
        /* comment */
        $type some_text"));

            self::assertSame($crud, Helper::getQueryType("
        /* 
        new line comment
        */
        $type 1"));

        }

        $this->assertException(DBDException::class, function () {
            Helper::getQueryType("WITH RECURSIVE t(n) AS");
        });

        $this->assertException(DBDException::class, function () {
            Helper::getQueryType("FOO bar");
        });
    }
}
