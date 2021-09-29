<?php
/**
 * Options
 *
 * @author    Nurlan Mukhanov <nurike@gmail.com>
 * @copyright 2020 Nurlan Mukhanov
 * @license   https://en.wikipedia.org/wiki/MIT_License MIT License
 * @link      https://github.com/Falseclock/dbd-php
 */

declare(strict_types=1);

namespace DBD\Base;

final class Options
{
    /** @var null $applicationName connection identity */
    private $applicationName = "DBD-PHP";
    /** @var bool $convertBoolean */
    private $convertBoolean = false;
    /** @var bool $convertNumeric */
    private $convertNumeric = false;
    /** @var bool $onDemand */
    private $onDemand = true;
    /** @var string $placeHolder */
    private $placeHolder = "?";
    /** @var bool $prepareExecute use real prepared and execute towards database */
    private $prepareExecute = false;
    /** @var bool $printError */
    private $printError = true;
    /** @var bool $raiseError */
    private $raiseError = true;
    /** @var bool $showErrorStatement */
    private $showErrorStatement = false;
    /** @var bool $useDebug */
    private $useDebug = false;

    /**
     * Options constructor.
     *
     * @param bool|null $onDemand
     * @param bool|null $printError
     * @param bool|null $raiseError
     * @param bool|null $showErrorStatement
     * @param bool|null $convertNumeric
     * @param bool|null $convertBoolean
     * @param bool|null $useDebug
     * @param bool|null $prepareExecute
     * @param string|null $placeholder
     */
    public function __construct(?bool $onDemand = null, ?bool $printError = null, ?bool $raiseError = null, ?bool $showErrorStatement = null, ?bool $convertNumeric = null, ?bool $convertBoolean = null, ?bool $useDebug = null, ?bool $prepareExecute = null, ?string $placeholder = null)
    {
        if (isset($onDemand))
            $this->onDemand = $onDemand;

        if (isset($printError))
            $this->printError = $printError;

        if (isset($raiseError))
            $this->raiseError = $raiseError;

        if (isset($showErrorStatement))
            $this->showErrorStatement = $showErrorStatement;

        if (isset($convertNumeric))
            $this->convertNumeric = $convertNumeric;

        if (isset($convertBoolean))
            $this->convertBoolean = $convertBoolean;

        if (isset($useDebug))
            $this->useDebug = $useDebug;

        if (isset($prepareExecute))
            $this->prepareExecute = $prepareExecute;

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
        return $this->convertBoolean;
    }

    /**
     * @param bool $convertBoolean
     *
     * @return Options
     */
    public function setConvertBoolean(bool $convertBoolean): Options
    {
        $this->convertBoolean = $convertBoolean;

        return $this;
    }

    /**
     * @return bool
     */
    public function isConvertNumeric(): bool
    {
        return $this->convertNumeric;
    }

    /**
     * @param bool $convertNumeric
     *
     * @return Options
     */
    public function setConvertNumeric(bool $convertNumeric): Options
    {
        $this->convertNumeric = $convertNumeric;

        return $this;
    }

    /**
     * @return bool
     */
    public function isOnDemand(): bool
    {
        return $this->onDemand;
    }

    /**
     * @param bool $onDemand
     *
     * @return Options
     */
    public function setOnDemand(bool $onDemand): Options
    {
        $this->onDemand = $onDemand;

        return $this;
    }

    /**
     * @return bool
     */
    public function isPrepareExecute(): bool
    {
        return $this->prepareExecute;
    }

    /**
     * @param bool $prepareExecute
     *
     * @return Options
     */
    public function setPrepareExecute(bool $prepareExecute): Options
    {
        $this->prepareExecute = $prepareExecute;

        return $this;
    }

    /**
     * @return bool
     */
    public function isPrintError(): bool
    {
        return $this->printError;
    }

    /**
     * @param bool $printError
     *
     * @return Options
     */
    public function setPrintError(bool $printError): Options
    {
        $this->printError = $printError;

        return $this;
    }

    /**
     * @return bool
     */
    public function isRaiseError(): bool
    {
        return $this->raiseError;
    }

    /**
     * @param bool $raiseError
     *
     * @return Options
     */
    public function setRaiseError(bool $raiseError): Options
    {
        $this->raiseError = $raiseError;

        return $this;
    }

    /**
     * @return bool
     */
    public function isShowErrorStatement(): bool
    {
        return $this->showErrorStatement;
    }

    /**
     * @param bool $showErrorStatement
     *
     * @return Options
     */
    public function setShowErrorStatement(bool $showErrorStatement): Options
    {
        $this->showErrorStatement = $showErrorStatement;

        return $this;
    }

    /**
     * @return bool
     */
    public function isUseDebug(): bool
    {
        return $this->useDebug;
    }

    /**
     * @param bool $useDebug
     *
     * @return Options
     */
    public function setUseDebug(bool $useDebug): Options
    {
        $this->useDebug = $useDebug;

        return $this;
    }
}
