<?php
/*************************************************************************************
 *   MIT License                                                                     *
 *                                                                                   *
 *   Copyright (C) 2009-2017 by Nurlan Mukhanov <nurike@gmail.com>                   *
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
 *   FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE     *
 *   AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER          *
 *   LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,   *
 *   OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE   *
 *   SOFTWARE.                                                                       *
 ************************************************************************************/

namespace DBD\Base;

final class DBDOptions
{
    /** @var bool $OnDemand */
    private $OnDemand = false;
    /** @var bool $PrintError */
    private $PrintError = true;
    /** @var bool $RaiseError */
    private $RaiseError = true;
    /** @var bool $ShowErrorStatement */
    private $ShowErrorStatement = false;
    /** @var bool $ConvertNumeric */
    private $ConvertNumeric = false;
    /** @var bool $ConvertBoolean */
    private $ConvertBoolean = false;
    /** @var bool $UseDebug */
    private $UseDebug = false;

    public function __construct(
        $OnDemand = null,
        $PrintError = null,
        $RaiseError = null,
        $ShowErrorStatement = null,
        $ConvertNumeric = null,
        $ConvertBoolean = null,
        $UseDebug = null
    ) {
        if(isset($OnDemand))
            $this->OnDemand = $OnDemand;

        if(isset($PrintError))
            $this->PrintError = $PrintError;

        if(isset($RaiseError))
            $this->RaiseError = $RaiseError;

        if(isset($ShowErrorStatement))
            $this->ShowErrorStatement = $ShowErrorStatement;

        if(isset($ConvertNumeric))
            $this->ConvertNumeric = $ConvertNumeric;

        if(isset($ConvertBoolean))
            $this->ConvertBoolean = $ConvertBoolean;

        if(isset($UseDebug))
            $this->UseDebug = $UseDebug;
    }

    /**
     * @return bool
     */
    public function isConvertBoolean() {
        return $this->ConvertBoolean;
    }

    /**
     * @param bool $ConvertBoolean
     *
     * @return \DBD\Base\DBDOptions
     */
    public function setConvertBoolean($ConvertBoolean) {
        $this->ConvertBoolean = $ConvertBoolean;

        return $this;
    }

    /**
     * @return bool
     */
    public function isConvertNumeric() {
        return $this->ConvertNumeric;
    }

    /**
     * @param bool $ConvertNumeric
     *
     * @return \DBD\Base\DBDOptions
     */
    public function setConvertNumeric($ConvertNumeric) {
        $this->ConvertNumeric = $ConvertNumeric;

        return $this;
    }

    /**
     * @return bool
     */
    public function isOnDemand() {
        return $this->OnDemand;
    }

    /**
     * @param bool $OnDemand
     *
     * @return \DBD\Base\DBDOptions
     */
    public function setOnDemand($OnDemand) {
        $this->OnDemand = $OnDemand;

        return $this;
    }

    /**
     * @return bool
     */
    public function isPrintError() {
        return $this->PrintError;
    }

    /**
     * @param bool $PrintError
     *
     * @return \DBD\Base\DBDOptions
     */
    public function setPrintError($PrintError) {
        $this->PrintError = $PrintError;

        return $this;
    }

    /**
     * @return bool
     */
    public function isRaiseError() {
        return $this->RaiseError;
    }

    /**
     * @param bool $RaiseError
     *
     * @return \DBD\Base\DBDOptions
     */
    public function setRaiseError($RaiseError) {
        $this->RaiseError = $RaiseError;

        return $this;
    }

    /**
     * @return bool
     */
    public function isShowErrorStatement() {
        return $this->ShowErrorStatement;
    }

    /**
     * @param bool $ShowErrorStatement
     *
     * @return \DBD\Base\DBDOptions
     */
    public function setShowErrorStatement($ShowErrorStatement) {
        $this->ShowErrorStatement = $ShowErrorStatement;

        return $this;
    }

    /**
     * @return bool
     */
    public function isUseDebug() {
        return $this->UseDebug;
    }

    /**
     * @param bool $UseDebug
     *
     * @return \DBD\Base\DBDOptions
     */
    public function setUseDebug($UseDebug) {
        $this->UseDebug = $UseDebug;

        return $this;
    }

    /**
     * @param array|\DBD\Base\DBDOptions|null $options
     *
     * @throws \Exception
     */
    public function setup($options = null) {
        if(isset($options)) {
            if(is_array($options)) {
                foreach($options as $key => $value) {
                    if(property_exists($this, $key)) {
                        $this->$key = $value;
                    }
                    else {
                        throw new \Exception("Unknown option '{$key}' provided");
                    }
                }
            }
            else {
                throw new \Exception("DBDOptions should be constructed with array");
            }
        }
    }
}