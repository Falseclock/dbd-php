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

use Exception;

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
	/** @var bool $PrepareExecute use real prepared and execute towards database */
	private $PrepareExecute = false;
	/** @var string $placeHolder */
	private $placeHolder = "?";

	public function __construct($OnDemand = null, $PrintError = null, $RaiseError = null, $ShowErrorStatement = null, $ConvertNumeric = null, $ConvertBoolean = null, $UseDebug = null, $PrepareExecute = null, $placeholder = null) {
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
	 * @param bool $ConvertBoolean
	 *
	 * @return DBDOptions
	 */
	public function setConvertBoolean($ConvertBoolean) {
		$this->ConvertBoolean = $ConvertBoolean;

		return $this;
	}

	/**
	 * @param bool $ConvertNumeric
	 *
	 * @return DBDOptions
	 */
	public function setConvertNumeric($ConvertNumeric) {
		$this->ConvertNumeric = $ConvertNumeric;

		return $this;
	}

	/**
	 * @param bool $OnDemand
	 *
	 * @return DBDOptions
	 */
	public function setOnDemand($OnDemand) {
		$this->OnDemand = $OnDemand;

		return $this;
	}

	/**
	 * @param string $placeHolder
	 */
	public function setPlaceHolder($placeHolder) {
		$this->placeHolder = $placeHolder;
	}

	/**
	 * @param bool $PrepareExecute
	 */
	public function setPrepareExecute($PrepareExecute) {
		$this->PrepareExecute = $PrepareExecute;
	}

	/**
	 * @param bool $PrintError
	 *
	 * @return DBDOptions
	 */
	public function setPrintError($PrintError) {
		$this->PrintError = $PrintError;

		return $this;
	}

	/**
	 * @param bool $RaiseError
	 *
	 * @return DBDOptions
	 */
	public function setRaiseError($RaiseError) {
		$this->RaiseError = $RaiseError;

		return $this;
	}

	/**
	 * @param bool $ShowErrorStatement
	 *
	 * @return DBDOptions
	 */
	public function setShowErrorStatement($ShowErrorStatement) {
		$this->ShowErrorStatement = $ShowErrorStatement;

		return $this;
	}

	/**
	 * @param bool $UseDebug
	 *
	 * @return DBDOptions
	 */
	public function setUseDebug($UseDebug) {
		$this->UseDebug = $UseDebug;

		return $this;
	}

	/**
	 * @param array|DBDOptions|null $options
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
