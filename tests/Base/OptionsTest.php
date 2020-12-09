<?php
/*************************************************************************************
 *   MIT License                                                                     *
 *                                                                                   *
 *   Copyright (C) 2009-2019 by Nurlan Mukhanov <nurike@gmail.com>                   *
 *                                                                                   *
 *   Permission is hereby granted, free of charge, to any person obtaining a copy    *
 *   of this software and associated documentation files (the "Software"), to deal   *
 *   in the Software without restriction, including without limitation the rights    *
 *   to use, copy, modify, merge, publish, distribute, sublicense, and/or sell       *
 *   copies of the Software, and to permit persons to whom the Software is           *
 *   furnished to do so, subject to the following conditions:                        *
 *                                                                                   *
 *   The above copyright notice and this permission notice shall be included in all  *
 *   copies or substantial portions of the Software.                                 *
 *                                                                                   *
 *   THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR      *
 *   IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,        *
 *   FITNESS FOR A PARTICULAR PURPOSE AND NON-INFRINGEMENT. IN NO EVENT SHALL THE    *
 *   AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER          *
 *   LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,   *
 *   OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE   *
 *   SOFTWARE.                                                                       *
 ************************************************************************************/

namespace DBD\Test\Base;

use DBD\Base\Options;
use PHPUnit\Framework\TestCase;

class OptionsTest extends TestCase
{
    public function testConstruct()
    {
        $options = new Options();
        self::assertFalse($options->isConvertBoolean());
        self::assertFalse($options->isConvertNumeric());
        self::assertTrue($options->isOnDemand());
        self::assertTrue($options->isPrintError());
        self::assertTrue($options->isRaiseError());
        self::assertFalse($options->isShowErrorStatement());
        self::assertFalse($options->isUseDebug());
        self::assertNull($options->getApplicationName());
        self::assertNotNull($options->getPlaceHolder());
        self::assertFalse($options->isSetApplicationOnDelete());
        self::assertFalse($options->isSetApplicationOnInsert());
        self::assertFalse($options->isSetApplicationOnUpdate());
    }

    public function testApplicationName()
    {
        $options = new Options();
        $options->setApplicationName("name");
        self::assertEquals("name", $options->getApplicationName());
    }

    public function testPlaceHolder()
    {
        $options = new Options();
        $options->setPlaceHolder("!");
        self::assertEquals("!", $options->getPlaceHolder());
    }

    public function testConvertBoolean()
    {
        $options = new Options();
        $options->setConvertBoolean(true);
        self::assertTrue($options->isConvertBoolean());

        $options->setConvertBoolean(false);
        self::assertFalse($options->isConvertBoolean());

    }

    public function testConvertNumeric()
    {
        $options = new Options();
        $options->setConvertNumeric(true);
        self::assertTrue($options->isConvertNumeric());

        $options->setConvertNumeric(false);
        self::assertFalse($options->isConvertNumeric());
    }

    public function testOnDemand()
    {
        $options = new Options();
        $options->setOnDemand(true);
        self::assertTrue($options->isOnDemand());

        $options->setOnDemand(false);
        self::assertFalse($options->isOnDemand());
    }

    public function testPrepareExecute()
    {
        $options = new Options();
        $options->setPrepareExecute(true);
        self::assertTrue($options->isPrepareExecute());

        $options->setPrepareExecute(false);
        self::assertFalse($options->isPrepareExecute());
    }

    public function testPrintError()
    {
        $options = new Options();
        $options->setPrintError(true);
        self::assertTrue($options->isPrintError());

        $options->setPrintError(false);
        self::assertFalse($options->isPrintError());
    }

    public function testRaiseError()
    {
        $options = new Options();
        $options->setRaiseError(true);
        self::assertTrue($options->isRaiseError());

        $options->setRaiseError(false);
        self::assertFalse($options->isRaiseError());
    }

    public function testSetApplicationOnDelete()
    {
        $options = new Options();
        $options->setSetApplicationOnDelete(true);
        self::assertTrue($options->isSetApplicationOnDelete());

        $options->setSetApplicationOnDelete(false);
        self::assertFalse($options->isSetApplicationOnDelete());
    }

    public function testSetApplicationOnInsert()
    {
        $options = new Options();
        $options->setSetApplicationOnInsert(true);
        self::assertTrue($options->isSetApplicationOnInsert());

        $options->setSetApplicationOnInsert(false);
        self::assertFalse($options->isSetApplicationOnInsert());
    }

    public function testSetApplicationOnUpdate()
    {
        $options = new Options();
        $options->setSetApplicationOnUpdate(true);
        self::assertTrue($options->isSetApplicationOnUpdate());

        $options->setSetApplicationOnUpdate(false);
        self::assertFalse($options->isSetApplicationOnUpdate());
    }

    public function testShowErrorStatement()
    {
        $options = new Options();
        $options->setShowErrorStatement(true);
        self::assertTrue($options->isShowErrorStatement());

        $options->setShowErrorStatement(false);
        self::assertFalse($options->isShowErrorStatement());
    }

    public function testUseDebug()
    {
        $options = new Options();
        $options->setUseDebug(true);
        self::assertTrue($options->isUseDebug());

        $options->setUseDebug(false);
        self::assertFalse($options->isUseDebug());
    }
}