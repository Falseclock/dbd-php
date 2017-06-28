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
        return new MySQLExtend($this);
    }

    protected function _affectedRows() {
        // TODO: Implement _affectedRows() method.
    }

    protected function _begin() {
        // TODO: Implement _begin() method.
    }

    protected function _commit() {
        // TODO: Implement _commit() method.
    }

    protected function _compileInsert($table, $params, $return = "") {
        // TODO: Implement _compileInsert() method.
    }

    protected function _compileUpdate($table, $params, $where, $return = "") {
        // TODO: Implement _compileUpdate() method.
    }

    protected function _connect() {
        // TODO: Implement _connect() method.
    }

    protected function _convertIntFloat(&$data, $type) {
        // TODO: Implement _convertIntFloat() method.
    }

    protected function _disconnect() {
        // TODO: Implement _disconnect() method.
    }

    protected function _errorMessage() {
        // TODO: Implement _errorMessage() method.
    }

    protected function _escape($string) {
        // TODO: Implement _escape() method.
    }

    protected function _fetchArray() {
        // TODO: Implement _fetchArray() method.
    }

    protected function _fetchAssoc() {
        // TODO: Implement _fetchAssoc() method.
    }

    protected function _numRows() {
        // TODO: Implement _numRows() method.
    }

    protected function _query($statement) {
        // TODO: Implement _query() method.
    }

    protected function _queryExplain($statement) {
        // TODO: Implement _queryExplain() method.
    }

    protected function _rollback() {
        // TODO: Implement _rollback() method.
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