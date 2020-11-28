<?php
/**
 * MSSQL database driver
 *
 * MIT License
 *
 * Copyright (C) 2009-2019 by Nurlan Mukhanov <nurike@gmail.com>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace DBD;

use DBD\Common\DBDException as Exception;

/**
 * Class MSSQL
 *
 * @package DBD
 */
class MSSQL extends DBD
{
	const SQLSRV_CURSOR_CLIENT_BUFFERED = 'buffered';
	const SQLSRV_CURSOR_DYNAMIC         = 'dynamic';
	const SQLSRV_CURSOR_FORWARD         = 'forward';
	const SQLSRV_CURSOR_KEYSET          = 'keyset';
	const SQLSRV_CURSOR_STATIC          = 'static';
	//
	protected $connectionInfo = [];
	protected $cursorType     = null;

	/**
	 *
	 * @return MSSQL
	 * @throws Exception
	 */
	public function connect() {

		if($this->Config->getDatabase())
			$this->connectionInfo['Database'] = $this->Config->getDatabase();

		if($this->Config->getUsername())
			$this->connectionInfo['UID'] = $this->Config->getUsername();

		if($this->Config->getPassword() != null)
			$this->connectionInfo['PWD'] = $this->Config->getPassword();

		if($this->Options->isOnDemand() == false) {
			$this->_connect();
		}

		return $this;
	}

	protected function _affectedRows() {
		$return = sqlsrv_rows_affected($this->result);

		if($return === false) {
			throw new Exception($this->_errorMessage(), $this->query);
		}
		if($return === -1) {
			return 0;
		}

		return $return;
	}

	protected function _begin() {
		return sqlsrv_begin_transaction($this->resourceLink);
	}

	protected function _commit() {
		return sqlsrv_commit($this->resourceLink);
	}

	protected function _compileInsert($table, $params, $return = "") {
		return "INSERT INTO $table ({$params['COLUMNS']}) VALUES ({$params['VALUES']})";
	}

	protected function _compileUpdate($table, $params, $where, $return = "") {
		return "UPDATE $table SET {$params['COLUMNS']}" . ($where ? " WHERE $where" : "");
	}

	/**
	 * Do real connection. Can be invoked if OnDemand is set to TRUE
	 *
	 * @return void
	 * @throws Exception
	 */
	protected function _connect() {
		$this->resourceLink = sqlsrv_connect($this->Config->getDsn(), $this->connectionInfo);

		if(!$this->resourceLink)
			throw new Exception($this->_errorMessage());
	}

	protected function _convertTypes(&$data): void {
		// TODO: Implement _convertTypes() method.
	}

	protected function _disconnect() {
		return sqlsrv_close($this->resourceLink);
	}

	protected function _errorMessage() {
		$errors = sqlsrv_errors();

		return preg_replace('/^(\[.*\])+?/', '', $errors[0]['message']) . " SQL State: " . $errors[0]['SQLSTATE'] . ". Code: " . $errors[0]['code'];
	}

	protected function _escape($str) {
		if(!isset($str) or $str === null) {
			return "NULL";
		}

		if(is_numeric($str))
			return $str;

		$nonDisplayAble = [
			'/%0[0-8bcef]/',
			// url encoded 00-08, 11, 12, 14, 15
			'/%1[0-9a-f]/',
			// url encoded 16-31
			'/[\x00-\x08]/',
			// 00-08
			'/\x0b/',
			// 11
			'/\x0c/',
			// 12
			'/[\x0e-\x1f]/'
			// 14-31
		];
		foreach($nonDisplayAble as $regex)
			$str = preg_replace($regex, '', $str);

		$str = str_replace("'", "''", $str);

		return "'$str'";
	}

	/**
	 * @param $uniqueName
	 * @param $arguments
	 *
	 * @return mixed
	 * @see MSSQL::_execute
	 * @see MySQL::_execute
	 * @see OData::_execute
	 * @see Pg::_execute
	 */
	protected function _execute($uniqueName, $arguments) {
		// TODO: Implement _execute() method.
	}

	protected function _fetchArray() {
		return sqlsrv_fetch_array($this->result, SQLSRV_FETCH_NUMERIC);
	}

	protected function _fetchAssoc() {
		return sqlsrv_fetch_array($this->result, SQLSRV_FETCH_ASSOC);
	}

	protected function _fieldName() {
		// TODO: Implement _fieldName() method.
	}

	protected function _fieldType() {
		// TODO: Implement _fieldType() method.
	}

	protected function _numFields() {
		return sqlsrv_num_fields($this->result);
	}

	protected function _numRows() {
		if(preg_match('/^(\s*?)(SELECT)\s*?.*?/i', $this->query)) {
			return sqlsrv_num_rows($this->result);
		}
		else {
			return $this->_affectedRows();
		}
	}

	/**
	 * @param $uniqueName
	 *
	 * @param $statement
	 *
	 * @return mixed
	 * @see MSSQL::_prepare
	 * @see MySQL::_prepare
	 * @see OData::_prepare
	 * @see Pg::_prepare
	 */
	protected function _prepare($uniqueName, $statement) {
		// TODO: Implement _prepare() method.
	}

	protected function _query($statement) {

		if($this->cursorType !== null) {
			return @sqlsrv_query($this->resourceLink, $statement, [], [ "Scrollable" => $this->cursorType ]);
		}
		else {
			if(preg_match('/^(\s*?)select\s*?.*?\s*?from/is', $this->query)) {
				// TODO: make as selectable option
				return @sqlsrv_query($this->resourceLink, $statement, [], [ "Scrollable" => MSSQL::SQLSRV_CURSOR_STATIC ]);
			}
		}

		return @sqlsrv_query($this->resourceLink, $statement);
	}

	protected function _rollback() {
		return sqlsrv_rollback($this->resourceLink);
	}

	/**
	 * @inheritDoc
	 */
	protected function _dump(string $preparedQuery, string $fileName, string $delimiter, string $nullString, bool $showHeader, string $tmpPath) {
		// TODO: Implement _dump() method.
	}

	/**
	 * @return void
	 */
	protected function _setApplicationName() {
		$this->applicationNameIsSet = true;
	}

	/**
	 * @param string|null $binaryString
	 *
	 * @return string|null
	 */
	protected function _binaryEscape(?string $binaryString): ?string {
		// TODO: Implement _binaryEscape() method.
	}
}
