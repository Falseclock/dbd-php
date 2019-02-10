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
    /** @var bool $HTMLError */
    private $HTMLError = false;
    /** @var bool $ConvertNumeric */
    private $ConvertNumeric = false;
    /** @var bool $ConvertBoolean */
    private $ConvertBoolean = false;
    /** @var bool $UseDebug */
    private $UseDebug = false;

    /**
     * DBDOptions constructor.
     *
     * @param array|\DBD\Base\DBDOptions|null $options
     *
     * @throws \Exception
     */
    public function __construct($options = null) {
        if(isset($options)) {
            if (is_array($options)) {
                foreach($options as $key => $value) {
                    if(property_exists($this, $key)) {
                        $this->$key = $value;
                    }
                    else {
                        throw new \Exception("Unknown option '{$key}' provided");
                    }
                }
            } else {
                throw new \Exception("DBDOptions should be constructed with array");
            }
        }
    }

    /**
     * @return bool
     */
    public function isOnDemand() {
        return $this->OnDemand;
    }

    /**
     * @param bool $OnDemand
     */
    public function setOnDemand($OnDemand) {
        $this->OnDemand = $OnDemand;
    }

    /**
     * @return bool
     */
    public function isPrintError() {
        return $this->PrintError;
    }

    /**
     * @param bool $PrintError
     */
    public function setPrintError($PrintError) {
        $this->PrintError = $PrintError;
    }

    /**
     * @return bool
     */
    public function isRaiseError() {
        return $this->RaiseError;
    }

    /**
     * @param bool $RaiseError
     */
    public function setRaiseError($RaiseError) {
        $this->RaiseError = $RaiseError;
    }

    /**
     * @return bool
     */
    public function isShowErrorStatement() {
        return $this->ShowErrorStatement;
    }

    /**
     * @param bool $ShowErrorStatement
     */
    public function setShowErrorStatement($ShowErrorStatement) {
        $this->ShowErrorStatement = $ShowErrorStatement;
    }

    /**
     * @return bool
     */
    public function isHTMLError() {
        return $this->HTMLError;
    }

    /**
     * @param bool $HTMLError
     */
    public function setHTMLError($HTMLError) {
        $this->HTMLError = $HTMLError;
    }

    /**
     * @return bool
     */
    public function isConvertNumeric() {
        return $this->ConvertNumeric;
    }

    /**
     * @param bool $ConvertNumeric
     */
    public function setConvertNumeric($ConvertNumeric) {
        $this->ConvertNumeric = $ConvertNumeric;
    }

    /**
     * @return bool
     */
    public function isConvertBoolean() {
        return $this->ConvertBoolean;
    }

    /**
     * @param bool $ConvertBoolean
     */
    public function setConvertBoolean($ConvertBoolean) {
        $this->ConvertBoolean = $ConvertBoolean;
    }

    /**
     * @return bool
     */
    public function isUseDebug() {
        return $this->UseDebug;
    }

    /**
     * @param bool $UseDebug
     */
    public function setUseDebug($UseDebug) {
        $this->UseDebug = $UseDebug;
    }
}