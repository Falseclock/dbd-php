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

namespace DBD;

/**
 * Class MySQL
 *
 * @package DBD
 */
class MySQL extends DBD
{
    public function connect() {
        if($this->Options->isOnDemand() == false) {
            $this->_connect();
        }

        return new MySQLExtend($this);
    }

    protected function _affectedRows() {
        return mysqli_affected_rows($this->result);
    }

    protected function _begin() {
        return mysqli_begin_transaction($this->dbResource);
    }

    protected function _commit() {
        return mysqli_commit($this->dbResource);
    }

    protected function _compileInsert($table, $params, $return = "") {
        return "INSERT INTO $table ({$params['COLUMNS']}) VALUES ({$params['VALUES']})";
    }

    protected function _compileUpdate($table, $params, $where, $return = "") {
        return "UPDATE $table SET {$params['COLUMNS']}" . ($where ? " WHERE $where" : "");
    }

    protected function _connect() {
        $this->dbResource = mysqli_connect($this->Config->getDsn(), $this->Config->getUsername(), $this->Config->getPassword(), $this->Config->getDatabase(), $this->Config->getPort());

        if(!$this->dbResource)
            trigger_error("Can not connect to MySQL server: " . mysqli_connect_error(), E_USER_ERROR);

        mysqli_autocommit($this->dbResource, false);
    }

    protected function _convertBoolean(&$data, $type) {
        // TODO: Implement _convertBoolean() method.
    }

    protected function _convertIntFloat(&$data, $type) {
        // TODO: Implement _convertIntFloat() method.
    }

    protected function _disconnect() {
        return mysqli_close($this->dbResource);
    }

    protected function _errorMessage() {
        return mysqli_error($this->dbResource);
    }

    protected function _escape($string) {
        if(!isset($string) or $string === null) {
            return "NULL";
        }
        $str = mysqli_real_escape_string($this->dbResource, $string);

        return "'$str'";
    }

    protected function _fetchArray() {
        return mysqli_fetch_array($this->dbResource);
    }

    protected function _fetchAssoc() {
        return mysqli_fetch_assoc($this->dbResource);
    }

    protected function _numRows() {
        if(preg_match('/\s*(SELECT|UPDATE|DELETE|INSERT)\s+/', $this->query)) {
            return mysqli_num_rows($this->result);
        }
        else {
            return 0;
        }
    }

    protected function _query($statement) {
        return mysqli_query($this->dbResource, $statement);
    }

    protected function _rollback() {
        return mysqli_rollback($this->dbResource);
    }

    protected function _convertTypes(&$data, $type) {
        // TODO: Implement _convertTypes() method.
        return $data;
    }
}

/**
 * Class MySQLExtend
 *
 * @package DBD
 */
final class MySQLExtend extends MySQL implements DBI
{
    public function __construct($object, $statement = "") {
        parent::extendMe($object, $statement);
    }
}