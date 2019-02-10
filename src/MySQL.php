<?php
/**
 * Created by PhpStorm.
 * User: Infection
 * Date: 28.06.2017
 * Time: 20:52
 */

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

    protected function _convertTypes(&$data, $type) {
        // TODO: Implement _convertTypes() method.
        return $data;
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

    protected function _queryExplain($statement) {
        // TODO: Implement _queryExplain() method.
    }

    protected function _rollback() {
        return mysqli_rollback($this->dbResource);
    }

    protected function _convertIntFloat(&$data, $type) {
        // TODO: Implement _convertIntFloat() method.
    }

    protected function _convertBoolean(&$data, $type) {
        // TODO: Implement _convertBoolean() method.
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