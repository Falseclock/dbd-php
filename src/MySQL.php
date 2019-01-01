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
        if($this->options['OnDemand'] == false) {
            $this->_connect();
        }

        return new MySQLExtend($this);
    }

    protected function _affectedRows() {
        return mysqli_affected_rows($this->result);
    }

    protected function _begin() {
        return mysqli_begin_transaction($this->dbh);
    }

    protected function _commit() {
        return mysqli_commit($this->dbh);
    }

    protected function _compileInsert($table, $params, $return = "") {
        return "INSERT INTO $table ({$params['COLUMNS']}) VALUES ({$params['VALUES']})";
    }

    protected function _compileUpdate($table, $params, $where, $return = "") {
        return "UPDATE $table SET {$params['COLUMNS']}" . ($where ? " WHERE $where" : "");
    }

    protected function _connect() {
        $this->dbh = mysqli_connect($this->dsn, $this->username, $this->password, $this->database, $this->port);

        if(!$this->dbh)
            trigger_error("Can not connect to MySQL server: " . mysqli_connect_error(), E_USER_ERROR);

        mysqli_autocommit($this->dbh, false);
    }

    protected function _convertTypes(&$data, $type) {
        // TODO: Implement _convertTypes() method.
        return $data;
    }

    protected function _disconnect() {
        return mysqli_close($this->dbh);
    }

    protected function _errorMessage() {
        return mysqli_error($this->dbh);
    }

    protected function _escape($string) {
        if(!isset($string) or $string === null) {
            return "NULL";
        }
        $str = mysqli_real_escape_string($this->dbh, $string);

        return "'$str'";
    }

    protected function _fetchArray() {
        return mysqli_fetch_array($this->dbh);
    }

    protected function _fetchAssoc() {
        return mysqli_fetch_assoc($this->dbh);
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
        return mysqli_query($this->dbh, $statement);
    }

    protected function _queryExplain($statement) {
        // TODO: Implement _queryExplain() method.
    }

    protected function _rollback() {
        return mysqli_rollback($this->dbh);
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