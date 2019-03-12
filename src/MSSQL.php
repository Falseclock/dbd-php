<?php
/**
 * MSSQL database driver
 *
 * MIT License
 *
 * Copyright (C) 2009-2017 by Nurlan Mukhanov <nurike@gmail.com>
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

use DBD\Base\DBDPHPException as Exception;

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
     * @throws \DBD\Base\DBDPHPException
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
        return sqlsrv_begin_transaction($this->dbResource);
    }

    protected function _commit() {
        return sqlsrv_commit($this->dbResource);
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
     */
    protected function _connect() {
        $this->dbResource = sqlsrv_connect($this->Config->getDsn(), $this->connectionInfo);

        if(!$this->dbResource)
            throw new Exception($this->_errorMessage());
    }

    protected function _convertBoolean(&$data, $type) {
        // TODO: Implement _convertBoolean() method.
    }

    protected function _convertIntFloat(&$data, $type) {
        // TODO: Implement _convertIntFloat() method.
    }

    protected function _disconnect() {
        return sqlsrv_close($this->dbResource);
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

    protected function _fetchArray() {
        return sqlsrv_fetch_array($this->result, SQLSRV_FETCH_NUMERIC);
    }

    protected function _fetchAssoc() {
        return sqlsrv_fetch_array($this->result, SQLSRV_FETCH_ASSOC);
    }

    protected function _numRows() {
        if(preg_match('/^(\s*?)(SELECT)\s*?.*?/i', $this->query)) {
            return sqlsrv_num_rows($this->result);
        }
        else {
            return $this->_affectedRows();
        }
    }

    protected function _query($statement) {

        if($this->cursorType !== null) {
            return @sqlsrv_query($this->dbResource, $statement, [], [ "Scrollable" => $this->cursorType ]);
        }
        else {
            if(preg_match('/^(\s*?)select\s*?.*?\s*?from/is', $this->query)) {
                // TODO: make as selectable option
                return @sqlsrv_query($this->dbResource, $statement, [], [ "Scrollable" => MSSQL::SQLSRV_CURSOR_STATIC ]);
            }
        }

        return @sqlsrv_query($this->dbResource, $statement);
    }

    protected function _rollback() {
        return sqlsrv_rollback($this->dbResource);
    }

    protected function _convertTypes(&$data, $type) {
        // TODO: Implement _convertTypes() method.
        return $data;
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
}