<?php
/**
 * @author    Nurlan Mukhanov <nurike@gmail.com>
 * @copyright 2009-2022 Nurlan Mukhanov
 * @license   https://en.wikipedia.org/wiki/MIT_License MIT License
 * @link      https://github.com/Falseclock/dbd-php
 */

declare(strict_types=1);

namespace DBD\Common;

final class Options
{
    /** @var string connection identity */
    private $applicationName = "DBD-PHP";
    /** @var bool */
    private $convertBoolean = false;
    /** @var bool */
    private $convertNumeric = false;
    /** @var bool */
    private $onDemand = true;
    /** @var string */
    private $placeHolder = "?";
    /** @var bool use real prepared and execute towards database */
    private $prepareExecute = false;
    /** @var bool */
    private $printError = true;
    /** @var bool */
    private $raiseError = true;
    /** @var bool */
    private $showErrorStatement = false;
    /** @var bool */
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
        if (!is_null($onDemand)) {
            $this->onDemand = $onDemand;
        }

        if (!is_null($printError)) {
            $this->printError = $printError;
        }

        if (!is_null($raiseError)) {
            $this->raiseError = $raiseError;
        }

        if (!is_null($showErrorStatement)) {
            $this->showErrorStatement = $showErrorStatement;
        }

        if (!is_null($convertNumeric)) {
            $this->convertNumeric = $convertNumeric;
        }

        if (!is_null($convertBoolean)) {
            $this->convertBoolean = $convertBoolean;
        }

        if (!is_null($useDebug)) {
            $this->useDebug = $useDebug;
        }

        if (!is_null($prepareExecute)) {
            $this->prepareExecute = $prepareExecute;
        }

        if (!is_null($placeholder)) {
            $this->placeHolder = $placeholder;
        }
    }

    /**
     * @return string
     */
    public function getApplicationName(): ?string
    {
        return $this->applicationName;
    }

    /**
     * @param string $applicationName
     *
     * @return Options
     */
    public function setApplicationName(string $applicationName): Options
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
