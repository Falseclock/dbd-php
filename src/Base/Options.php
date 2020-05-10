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

use DBD\Common\DBDException as Exception;

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
	 * @param bool|null   $OnDemand
	 * @param bool|null   $PrintError
	 * @param bool|null   $RaiseError
	 * @param bool|null   $ShowErrorStatement
	 * @param bool|null   $ConvertNumeric
	 * @param bool|null   $ConvertBoolean
	 * @param bool|null   $UseDebug
	 * @param bool|null   $PrepareExecute
	 * @param string|null $placeholder
	 */
	public function __construct(bool $OnDemand = null, bool $PrintError = null, bool $RaiseError = null, bool $ShowErrorStatement = null, bool $ConvertNumeric = null, bool $ConvertBoolean = null, bool $UseDebug = null, bool $PrepareExecute = null, string $placeholder = null) {
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

		if(isset($PrepareExecute))
			$this->PrepareExecute = $PrepareExecute;

		if(isset($placeholder))
			$this->placeHolder = $placeholder;
	}

	/**
	 * @return string
	 */
	public function getApplicationName() {
		return $this->applicationName;
	}

	/**
	 * @return string
	 */
	public function getPlaceHolder() {
		return $this->placeHolder;
	}

	/**
	 * @return bool
	 */
	public function isConvertBoolean() {
		return $this->ConvertBoolean;
	}

	/**
	 * @return bool
	 */
	public function isConvertNumeric() {
		return $this->ConvertNumeric;
	}

	/**
	 * @return bool
	 */
	public function isOnDemand() {
		return $this->OnDemand;
	}

	/**
	 * @return bool
	 */
	public function isPrepareExecute() {
		return $this->PrepareExecute;
	}

	/**
	 * @return bool
	 */
	public function isPrintError() {
		return $this->PrintError;
	}

	/**
	 * @return bool
	 */
	public function isRaiseError() {
		return $this->RaiseError;
	}

	/**
	 * @return bool
	 */
	public function isSetApplicationOnDelete() {
		return $this->setApplicationOnDelete;
	}

	/**
	 * @return bool
	 */
	public function isSetApplicationOnInsert() {
		return $this->setApplicationOnInsert;
	}

	/**
	 * @return bool
	 */
	public function isSetApplicationOnUpdate() {
		return $this->setApplicationOnUpdate;
	}

	/**
	 * @return bool
	 */
	public function isShowErrorStatement() {
		return $this->ShowErrorStatement;
	}

	/**
	 * @return bool
	 */
	public function isUseDebug() {
		return $this->UseDebug;
	}

	/**
	 * @param null $applicationName
	 *
	 * @return Options
	 */
	public function setApplicationName($applicationName): Options {
		$this->applicationName = $applicationName;

		return $this;
	}

	/**
	 * @param bool $ConvertBoolean
	 *
	 * @return Options
	 */
	public function setConvertBoolean($ConvertBoolean): Options {
		$this->ConvertBoolean = $ConvertBoolean;

		return $this;
	}

	/**
	 * @param bool $ConvertNumeric
	 *
	 * @return Options
	 */
	public function setConvertNumeric($ConvertNumeric): Options {
		$this->ConvertNumeric = $ConvertNumeric;

		return $this;
	}

	/**
	 * @param bool $OnDemand
	 *
	 * @return Options
	 */
	public function setOnDemand($OnDemand): Options {
		$this->OnDemand = $OnDemand;

		return $this;
	}

	/**
	 * @param string $placeHolder
	 *
	 * @return Options
	 */
	public function setPlaceHolder($placeHolder): Options {
		$this->placeHolder = $placeHolder;

		return $this;
	}

	/**
	 * @param bool $PrepareExecute
	 *
	 * @return Options
	 */
	public function setPrepareExecute($PrepareExecute): Options {
		$this->PrepareExecute = $PrepareExecute;

		return $this;
	}

	/**
	 * @param bool $PrintError
	 *
	 * @return Options
	 */
	public function setPrintError($PrintError): Options {
		$this->PrintError = $PrintError;

		return $this;
	}

	/**
	 * @param bool $RaiseError
	 *
	 * @return Options
	 */
	public function setRaiseError($RaiseError): Options {
		$this->RaiseError = $RaiseError;

		return $this;
	}

	/**
	 * @param bool $onDelete
	 *
	 * @return Options
	 */
	public function setSetApplicationOnDelete(bool $onDelete): Options {
		$this->setApplicationOnDelete = $onDelete;

		return $this;
	}

	/**
	 * @param bool $onInsert
	 *
	 * @return Options
	 */
	public function setSetApplicationOnInsert(bool $onInsert): Options {
		$this->setApplicationOnInsert = $onInsert;

		return $this;
	}

	/**
	 * @param bool $onUpdate
	 *
	 * @return Options
	 */
	public function setSetApplicationOnUpdate(bool $onUpdate): Options {
		$this->setApplicationOnUpdate = $onUpdate;

		return $this;
	}

	/**
	 * @param bool $ShowErrorStatement
	 *
	 * @return Options
	 */
	public function setShowErrorStatement($ShowErrorStatement): Options {
		$this->ShowErrorStatement = $ShowErrorStatement;

		return $this;
	}

	/**
	 * @param bool $UseDebug
	 *
	 * @return Options
	 */
	public function setUseDebug($UseDebug): Options {
		$this->UseDebug = $UseDebug;

		return $this;
	}

	/**
	 * @param array|Options|null $options
	 *
	 * @throws Exception
	 */
	public function setup($options = null) {
		if(isset($options)) {
			if(is_array($options)) {
				foreach($options as $key => $value) {
					if(property_exists($this, $key)) {
						$this->$key = $value;
					}
					else {
						throw new Exception("Unknown option '{$key}' provided");
					}
				}
			}
			else {
				throw new Exception("DBDOptions should be constructed with array");
			}
		}
	}
}
