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

namespace DBD\Base;

final class Options
{
    /** @var bool $ConvertBoolean */
    private $ConvertBoolean = false;
    /** @var bool $ConvertNumeric */
    private $ConvertNumeric = false;
    /** @var bool $OnDemand */
    private $OnDemand = true;
    /** @var bool $PrepareExecute use real prepared and execute towards database */
    private $PrepareExecute = false;
    /** @var bool $PrintError */
    private $PrintError = true;
    /** @var bool $RaiseError */
    private $RaiseError = true;
    /** @var bool $ShowErrorStatement */
    private $ShowErrorStatement = false;
    /** @var bool $UseDebug */
    private $UseDebug = false;
    /** @var null $applicationName connection identity */
    private $applicationName = null;
    /** @var string $placeHolder */
    private $placeHolder = "?";
    /** @var bool $setApplicationOnDelete if true, then before each update driver will execute 'set application_name to my_application;' */
    private $setApplicationOnDelete = false;
    /** @var bool $setApplicationOnInsert if true, then before each update driver will execute 'set application_name to my_application;' */
    private $setApplicationOnInsert = false;
    /** @var bool $setApplicationOnUpdate if true, then before each update driver will execute 'set application_name to my_application;' */
    private $setApplicationOnUpdate = false;

    /**
     * Options constructor.
     *
     * @param bool|null $OnDemand
     * @param bool|null $PrintError
     * @param bool|null $RaiseError
     * @param bool|null $ShowErrorStatement
     * @param bool|null $ConvertNumeric
     * @param bool|null $ConvertBoolean
     * @param bool|null $UseDebug
     * @param bool|null $PrepareExecute
     * @param string|null $placeholder
     */
    public function __construct(?bool $OnDemand = null, ?bool $PrintError = null, ?bool $RaiseError = null, ?bool $ShowErrorStatement = null, ?bool $ConvertNumeric = null, ?bool $ConvertBoolean = null, ?bool $UseDebug = null, ?bool $PrepareExecute = null, ?string $placeholder = null)
    {
        if (isset($OnDemand))
            $this->OnDemand = $OnDemand;

        if (isset($PrintError))
            $this->PrintError = $PrintError;

        if (isset($RaiseError))
            $this->RaiseError = $RaiseError;

        if (isset($ShowErrorStatement))
            $this->ShowErrorStatement = $ShowErrorStatement;

        if (isset($ConvertNumeric))
            $this->ConvertNumeric = $ConvertNumeric;

        if (isset($ConvertBoolean))
            $this->ConvertBoolean = $ConvertBoolean;

        if (isset($UseDebug))
            $this->UseDebug = $UseDebug;

        if (isset($PrepareExecute))
            $this->PrepareExecute = $PrepareExecute;

        if (isset($placeholder))
            $this->placeHolder = $placeholder;
    }

    /**
     * @return string
     */
    public function getApplicationName(): ?string
    {
        return $this->applicationName;
    }

    /**
     * @param null $applicationName
     *
     * @return Options
     */
    public function setApplicationName($applicationName): Options
    {
        $this->applicationName = $applicationName;

        return $this;
    }

    /**
     * @return string
     */
    public function getPlaceHolder(): string
    {
        return $this->placeHolder;
    }

    /**
     * @param string $placeHolder
     *
     * @return Options
     */
    public function setPlaceHolder(string $placeHolder): Options
    {
        $this->placeHolder = $placeHolder;

        return $this;
    }

    /**
     * @return bool
     */
    public function isConvertBoolean(): bool
    {
        return $this->ConvertBoolean;
    }

    /**
     * @param bool $ConvertBoolean
     *
     * @return Options
     */
    public function setConvertBoolean(bool $ConvertBoolean): Options
    {
        $this->ConvertBoolean = $ConvertBoolean;

        return $this;
    }

    /**
     * @return bool
     */
    public function isConvertNumeric(): bool
    {
        return $this->ConvertNumeric;
    }

    /**
     * @param bool $ConvertNumeric
     *
     * @return Options
     */
    public function setConvertNumeric(bool $ConvertNumeric): Options
    {
        $this->ConvertNumeric = $ConvertNumeric;

        return $this;
    }

    /**
     * @return bool
     */
    public function isOnDemand(): bool
    {
        return $this->OnDemand;
    }

    /**
     * @param bool $OnDemand
     *
     * @return Options
     */
    public function setOnDemand(bool $OnDemand): Options
    {
        $this->OnDemand = $OnDemand;

        return $this;
    }

    /**
     * @return bool
     */
    public function isPrepareExecute(): bool
    {
        return $this->PrepareExecute;
    }

    /**
     * @param bool $PrepareExecute
     *
     * @return Options
     */
    public function setPrepareExecute(bool $PrepareExecute): Options
    {
        $this->PrepareExecute = $PrepareExecute;

        return $this;
    }

    /**
     * @return bool
     */
    public function isPrintError(): bool
    {
        return $this->PrintError;
    }

    /**
     * @param bool $PrintError
     *
     * @return Options
     */
    public function setPrintError(bool $PrintError): Options
    {
        $this->PrintError = $PrintError;

        return $this;
    }

    /**
     * @return bool
     */
    public function isRaiseError(): bool
    {
        return $this->RaiseError;
    }

    /**
     * @param bool $RaiseError
     *
     * @return Options
     */
    public function setRaiseError(bool $RaiseError): Options
    {
        $this->RaiseError = $RaiseError;

        return $this;
    }

    /**
     * @return bool
     */
    public function isSetApplicationOnDelete(): bool
    {
        return $this->setApplicationOnDelete;
    }

    /**
     * @param bool $onDelete
     *
     * @return Options
     */
    public function setSetApplicationOnDelete(bool $onDelete): Options
    {
        $this->setApplicationOnDelete = $onDelete;

        return $this;
    }

    /**
     * @return bool
     */
    public function isSetApplicationOnInsert(): bool
    {
        return $this->setApplicationOnInsert;
    }

    /**
     * @param bool $onInsert
     *
     * @return Options
     */
    public function setSetApplicationOnInsert(bool $onInsert): Options
    {
        $this->setApplicationOnInsert = $onInsert;

        return $this;
    }

    /**
     * @return bool
     */
    public function isSetApplicationOnUpdate(): bool
    {
        return $this->setApplicationOnUpdate;
    }

    /**
     * @param bool $onUpdate
     *
     * @return Options
     */
    public function setSetApplicationOnUpdate(bool $onUpdate): Options
    {
        $this->setApplicationOnUpdate = $onUpdate;

        return $this;
    }

    /**
     * @return bool
     */
    public function isShowErrorStatement(): bool
    {
        return $this->ShowErrorStatement;
    }

    /**
     * @param bool $ShowErrorStatement
     *
     * @return Options
     */
    public function setShowErrorStatement(bool $ShowErrorStatement): Options
    {
        $this->ShowErrorStatement = $ShowErrorStatement;

        return $this;
    }

    /**
     * @return bool
     */
    public function isUseDebug(): bool
    {
        return $this->UseDebug;
    }

    /**
     * @param bool $UseDebug
     *
     * @return Options
     */
    public function setUseDebug(bool $UseDebug): Options
    {
        $this->UseDebug = $UseDebug;

        return $this;
    }
}
