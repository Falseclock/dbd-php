<?php
/**
 * DBD package
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

use DBD\Base\DBDConfig;
use DBD\Base\DBDDebug as Debug;
use DBD\Base\DBDHelper;
use DBD\Base\DBDOptions;
use DBD\Base\DBDPHPException as Exception;
use DBD\Base\DBDQuery;

abstract class DBD
{
    const STORAGE_CACHE    = "Cache";
    const STORAGE_DATABASE = "database";
    const UNDEFINED        = "UNDEF";
    public    $rows  = 0;
    protected $cache = [
        'key'      => null,
        'result'   => null,
        'compress' => null,
        'expire'   => null,
    ];
    /** @var resource $dbResource Database cor curl connection resource */
    protected $dbResource;
    /** @var string $query SQL query */
    protected $query;
    /** @var resource|string|array $result Query result data */
    protected $result;
    /** @var \Psr\SimpleCache\CacheInterface|\DBD\Cache */
    protected $CacheDriver;
    /** @var DBDOptions $Options */
    protected $Options;
    /** @var DBDConfig $Config */
    protected $Config;
    /** @var mixed $fetch */
    private $fetch = self::UNDEFINED;
    /** @var string $storage This param is used for identifying where data taken from */
    private $storage;
    /** @var bool $inTransaction Stores current transaction state */
    private $inTransaction = false;

    /**
     * Copies object variables after extended class construction
     *
     * @param        $object
     * @param string $statement
     *
     * @return void
     */
    final protected function extendMe($object, $statement = "") {
        foreach(get_object_vars($object) as $key => $value) {
            $this->$key = $value;
        }
        $this->query = $statement;

        if(isset($this->CacheDriver)) {
            $this->cache['expire'] = $this->CacheDriver->defaultTtl;
        }
    }

    /**
     * @deprecated
     * @see affectedRows
     * @return int
     */
    public function affected() {
        return $this->affectedRows();
    }

    /**
     * Returns number of affected rows during update or delete
     *
     * ```
     * $sth = $db->prepare("DELETE FROM foo WHERE bar = ?");
     * $sth->execute($someVar);
     * if ($sth->affected()) {
     *      // Do something
     * }
     * ```
     *
     * @return int
     */
    public function affectedRows() {
        return $this->_affectedRows();
    }

    /**
     * Starts database transaction
     *
     * @return bool
     * @throws \DBD\Base\DBDPHPException
     */
    public function begin() {
        if($this->inTransaction == true) {
            throw new Exception("Already in transaction");
        }
        $this->connectionPreCheck();
        $this->result = $this->_begin();
        if($this->result === false) {
            throw new Exception("Can't start transaction: " . $this->_errorMessage());
        }
        $this->inTransaction = true;

        return true;
    }

    /**
     * Must be called after statement prepare
     *
     * ```
     * $sth = $db->prepare("SELECT bank_id AS id, bank_name AS name FROM banks ORDER BY bank_name ASC");
     * $sth->cache("AllBanks");
     * $sth->execute();
     * ```
     *
     * @param string                         $key
     * @param int|float|\DateInterval|string $ttl
     *
     * @throws \DBD\Base\DBDPHPException
     */
    public function cache($key, $ttl = null) {
        if(!isset($key) or !$key) {
            throw new Exception("caching failed: key is not set or empty");
        }
        if(!is_string($key)) {
            throw new Exception("key is not string type");
        }
        if(!isset($this->CacheDriver)) {
            throw new Exception("CacheDriver not initialized");
        }
        if(!isset($this->query)) {
            throw new Exception("SQL statement not prepared");
        }

        if(preg_match("/^[\s\t\r\n]*select/i", $this->query)) {
            // set hash key
            $this->cache['key'] = $key;

            if($ttl !== null)
                $this->cache['expire'] = $ttl;
        }
        else {
            throw new Exception("caching failed: current query is not of SELECT type");
        }

        return;
    }

    /**
     * Commits a transaction that was begun
     *
     * @return bool
     * @throws \DBD\Base\DBDPHPException
     */
    public function commit() {
        if(!$this->isConnected()) {
            throw new Exception("No connection established yet");
        }
        if($this->inTransaction) {
            $this->result = $this->_commit();
            if($this->result === false)
                throw new Exception("Can not commit transaction: " . $this->_errorMessage());
        }
        else {
            throw new Exception("No transaction to commit");
        }
        $this->inTransaction = false;

        return true;
    }

    /**
     * Base and main method to start. Returns new instance of DBD driver
     *
     * ```
     * $dbd = new DBD\Pg();
     * $dbh = $dbd->create($config, $options);
     * $db = $dbh->connect();
     *
     * @param DBDConfig  $config
     * @param DBDOptions $options
     *
     * @return $this
     * @throws \DBD\Base\DBDPHPException
     */
    public function create($config, $options = null) {
        $driver = get_class($this);

        /** @var \DBD\DBD $db */
        $db = new $driver;

        if($config instanceof DBDConfig) {
            $db->Config = $config;
        }
        else {
            throw new Exception("config is not instance of DBDConfig");
        }

        if($options instanceof DBDOptions) {
            $db->Options = $options;
        }
        else {
            if(!isset($options)) {
                $db->Options = new DBDOptions;
            }
            else {
                throw new Exception("options are not instance of DBDOptions");
            }
        }

        return $db;
    }

    /**
     * Closes a database connection
     *
     * @return $this
     * @throws \DBD\Base\DBDPHPException
     */
    public function disconnect() {
        if($this->isConnected()) {
            if($this->inTransaction) {
                throw new Exception("Uncommitted transaction state");
            }
            $this->_disconnect();
            $this->dbResource = null;
        }

        return $this;
    }

    /**
     * For simple SQL query, mostly delete or update, when you do not need to get results and only want to know affected rows
     *
     * Example 1:
     * ```
     * $affectedRows = $db->doit("UPDATE table SET column1 = ? WHERE column2 = ?", NULL, 'must be null');
     * ```
     * Example 2:
     * ```
     * $db->doit("DELETE FROM main_table);
     * ```
     *
     * @return int Number of affected tuples will be stored in $result variable
     * @throws \DBD\Base\DBDPHPException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \ReflectionException
     */
    public function doIt() {
        if(!func_num_args())
            throw new Exception("query failed: statement is not set or empty");

        list ($statement, $args) = DBDHelper::prepareArgs(func_get_args());

        $sth = $this->query($statement, $args);

        return $sth->rows;
    }

    /**
     * Sends a request to execute a prepared statement with given parameters, and waits for the result.
     *
     * @return mixed
     * @throws \DBD\Base\DBDPHPException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \ReflectionException
     */
    public function execute() {
        // Set result to false
        $this->result = false;
        $this->fetch = self::UNDEFINED;
        $this->storage = null;
        $exec = $this->getExec(func_get_args());

        //--------------------------------------
        // Is query uses cache?
        //--------------------------------------
        if(isset($this->CacheDriver)) {
            if($this->cache['key'] !== null) {
                // Get data from cache
                if($this->Options->isUseDebug()) {
                    Debug::me()->startTimer();
                }
                $this->cache['result'] = $this->CacheDriver->get($this->cache['key']);

                // Cache not empty?
                if($this->cache['result'] && $this->cache['result'] !== false) {
                    $cost = Debug::me()->endTimer();
                    // To avoid errors as result by default is NULL
                    $this->result = "cached";
                    $this->storage = self::STORAGE_CACHE;
                    $this->rows = count($this->cache['result']);
                }
            }
        }

        // If not found in cache, then let's get from DB
        if($this->result != "cached") {

            $this->connectionPreCheck();
            if($this->Options->isUseDebug()) {
                Debug::me()->startTimer();
            }
            // Execute query to the database
            $this->result = $this->_query($exec);
            $cost = Debug::me()->endTimer();

            if($this->result !== false) {
                $this->rows = $this->_numRows();
                $this->storage = self::STORAGE_DATABASE;
            }
            else {
                throw new Exception ($this->_errorMessage(), $exec);
            }

            // If query from cache
            if($this->cache['key'] !== null) {
                //  As we already queried database we have to set key to NULL
                //  because during internal method invoke (fetchRowSet below) this Driver
                //  will think we have data from cache

                $storedKey = $this->cache['key'];
                $this->cache['key'] = null;

                // If we have data from query
                if($this->rows()) {
                    $this->cache['result'] = $this->fetchRowSet();
                }
                else {
                    // select is empty
                    $this->cache['result'] = [];
                }

                // reverting all back, cause we stored data to cache
                $this->result = "cached";
                $this->cache['key'] = $storedKey;

                // Setting up our cache
                $this->CacheDriver->set($this->cache['key'], $this->cache['result'], $this->cache['expire']);
            }
        }

        if($this->result === false) {
            throw new Exception($this->_errorMessage(), $exec);
        }

        if($this->Options->isUseDebug()) {
            $cost = isset($cost) ? $cost : 0;

            $driver = $this->storage == self::STORAGE_CACHE ? self::STORAGE_CACHE : (new \ReflectionClass($this))->getParentClass()->getShortName();
            $caller = DBDHelper::caller($this);

            Debug::addQueries(new DBDQuery(DBDHelper::cleanSql($exec), $cost, $caller[0], DBDHelper::debugMark($cost), $driver));
            Debug::addTotalQueries(1);
            Debug::addTotalCost($cost);
        }

        return $this->result;
    }

    /**
     * @return bool|mixed
     */
    public function fetch() {
        if($this->fetch == self::UNDEFINED) {

            if($this->cache['key'] === null) {

                $return = $this->_fetchArray();

                if($this->Options->isConvertNumeric() || $this->Options->isConvertBoolean()) {
                    $return = $this->convertTypes($return, "row");
                }

                $this->fetch = $return;
            }
            else {
                $this->fetch = array_shift($this->cache['result']);
            }
        }
        if(!count($this->fetch)) {
            return false;
        }

        return array_shift($this->fetch);
    }

    public function fetchArraySet() {
        $array = [];

        if($this->cache['key'] === null) {
            while($row = $this->fetchRow()) {
                $entry = [];
                foreach($row as $key => $value) {
                    $entry[] = $value;
                }
                $array[] = $entry;
            }
        }
        else {
            $cache = $this->cache['result'];
            $this->cache['result'] = [];
            foreach($cache as $row) {
                $entry = [];
                foreach($row as $key => $value) {
                    $entry[] = $value;
                }
                $array[] = $entry;
            }
        }

        return $array;
    }

    public function fetchRow() {
        if($this->cache['key'] === null) {
            $return = $this->_fetchAssoc();

            if($this->Options->isConvertNumeric() || $this->Options->isConvertBoolean()) {
                return $this->convertTypes($return, "row");
            }

            return $return;
        }
        else {
            return array_shift($this->cache['result']);
        }
    }

    public function fetchRowSet($key = null) {
        $array = [];

        if($this->cache['key'] === null) {
            while($row = $this->fetchRow()) {
                if($key) {
                    $array[$row[$key]] = $row;
                }
                else {
                    $array[] = $row;
                }
            }
        }
        else {
            $cache = $this->cache['result'];
            $this->cache['result'] = [];

            if($key) {
                foreach($cache as $row) {
                    $array[$row[$key]] = $row;
                }
            }
            else {
                $array = $cache;
            }
        }

        return $array;
    }

    /**
     * Use if when you need to get DBD cache driver to handle stored cache outside of this class
     *
     * @return \DBD\Cache|\Psr\SimpleCache\CacheInterface
     */
    public function getCacheDriver() {
        return $this->CacheDriver;
    }

    /**
     * @param \DBD\Cache|\Psr\SimpleCache\CacheInterface $cache
     *
     * @return \DBD\DBD
     * @throws \DBD\Base\DBDPHPException
     */
    public function setCacheDriver($cache) {

        if($cache instanceof Cache || $cache instanceof \Psr\SimpleCache\CacheInterface) {
            $this->CacheDriver = $cache;

            return $this;
        }

        throw new Exception("Unsupported caching interface. Extend DBD\\Cache or use PSR-16 Common Interface for Caching");
    }

    public function getResult() {
        return $this->result;
    }

    public function getStorage() {
        return $this->storage;
    }

    /**
     * Easy insert operation
     *
     * @param string $table
     * @param array  $args
     * @param null   $return
     *
     * @return \DBD\DBD
     * @throws \DBD\Base\DBDPHPException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \ReflectionException
     */
    public function insert($table, $args, $return = null) {
        $params = DBDHelper::compileInsertArgs($args);

        $sth = $this->prepare($this->_compileInsert($table, $params, $return));
        $sth->execute($params['ARGS']);

        return $sth;
    }

    /**
     * Creates a prepared statement for later execution
     *
     * @param string $statement
     *
     * @return $this
     * @throws \DBD\Base\DBDPHPException
     */
    public function prepare($statement) {
        if(!isset($statement) or empty($statement))
            throw new Exception("prepare failed: statement is not set or empty");

        $className = get_class($this);

        return new $className($this, $statement);
    }

    /**
     * Like doit method, but return self instance
     *
     * Example 1:
     * ```
     * $sth = $db->query("SELECT * FROM invoices");
     * while ($row = $sth->fetchrow()) {
     *      //do something
     * }
     * ```
     *
     * Example 2:
     *
     * ```
     * $sth = $db->query("UPDATE invoices SET invoice_uuid=?",'550e8400-e29b-41d4-a716-446655440000');
     * echo($sth->affectedRows());
     * ```
     *
     * @return \DBD\DBD
     * @throws \DBD\Base\DBDPHPException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \ReflectionException
     */
    public function query() {
        if(!func_num_args())
            throw new Exception("query failed: statement is not set or empty");

        list ($statement, $args) = DBDHelper::prepareArgs(func_get_args());

        $sth = $this->prepare($statement);

        if(is_array($args)) {
            $sth->execute($args);
        }
        else {
            $sth->execute();
        }

        return $sth;
    }

    /**
     * Rolls back a transaction that was begun
     *
     * @return bool
     *
     * @throws \DBD\Base\DBDPHPException
     */
    public function rollback() {
        if($this->inTransaction) {
            $this->connectionPreCheck();
            $this->result = $this->_rollback();
            if($this->result === false) {
                throw new Exception("Can not end transaction " . pg_errormessage());
            }
        }
        else {
            throw new Exception("No transaction to rollback");
        }
        $this->inTransaction = false;

        return true;
    }

    /**
     * Returns the number of rows in a database result resource.
     *
     * @return int
     */
    public function rows() {
        if($this->cache['key'] === null) {
            if(preg_match("/^(\s*?)select\s*?.*?\s*?from/is", $this->query)) {
                return $this->_numRows();
            }

            return $this->_affectedRows();
        }
        else {
            return count($this->cache['result']);
        }
    }

    /**
     * @return bool|mixed
     * @throws \DBD\Base\DBDPHPException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \ReflectionException
     */
    public function select() {
        list ($statement, $args) = DBDHelper::prepareArgs(func_get_args());

        $sth = $this->query($statement, $args);

        if($sth->rows()) {
            return $sth->fetch();
        }

        return null;
    }

    /**
     * Simplifies update procedures. Method makes updates of the rows by giving parameters and prepared values. Returns self instance.
     *
     * Example 1:
     *
     * ```php
     * $update = [
     *     'invoice_date'   => $doc['Date'],
     *     'invoice_number' => [ $doc['Number'] ],
     *     'invoice_amount' => [ $doc['Amount'], 'numeric' ],
     * ];
     * // this will update all rows in a table
     * $sth = $db->update('invoices', $update);
     * echo($sth->rows);
     *
     * Example 2:
     *
     * ```php
     * $update = array(
     *     'invoice_date'   => [ $doc['Date'], 'date' ] ,
     *     'invoice_number' => [ $doc['Number'], 'int' ]
     *     'invoice_amount' => $doc['Amount']
     * );
     * // this will update all rows in a table where invoice_uuid equals to some value
     * $sth = $db->update('invoices', $update, "invoice_uuid=  ?", $doc['UUID']);
     * echo ($sth->rows);
     * ```
     *
     * Example 3:
     * ```php
     * $update = array(
     *     'invoice_date'   => [ $doc['Date'], 'timestamp' ],
     *     'invoice_number' => [ $doc['Number'] ],
     *     'invoice_amount' => [ $doc['Amount'] ]
     * );
     * // this will update all rows in a table where invoice_uuid is null
     * // query will return invoice_id
     * $sth = $db->update('invoices', $update, "invoice_uuid IS NULL", "invoice_id");
     * while ($row = $sth->fetchrow()) {
     *     printf("Updated invoice with ID=%d\n", $row['invoice_id']);
     * }
     * ```
     *
     * Example 4:
     * ```php
     * $update = [
     *     'invoice_date'   => $doc['Date'],
     *     'invoice_number' => $doc['Number'],
     *     'invoice_amount' => $doc['Amount'],
     * ];
     * // this will update all rows in a table where invoice_uuid equals to some value
     * // query will return invoice_id
     * $sth = $db->update('invoices', $update, "invoice_uuid = ?", $doc['UUID'], "invoice_id, invoice_uuid");
     * while($row = $sth->fetchrow()) {
     *     printf("Updated invoice with ID=%d and UUID=%s\n", $row['invoice_id'], $row['invoice_uuid']);
     * }
     * ```
     *
     * @return \DBD\DBD
     * @throws \DBD\Base\DBDPHPException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \ReflectionException
     */
    public function update() {
        $binds = 0;
        $where = null;
        $return = null;
        $ARGS = func_get_args();
        $table = $ARGS[0];
        $values = $ARGS[1];

        $params = DBDHelper::compileUpdateArgs($values, $this);

        if(func_num_args() > 2) {
            $where = $ARGS[2];
            $binds = substr_count($where, "?");
        }

        // If we set $where with placeholders or we set $return
        if(func_num_args() > 3) {
            for($i = 3; $i < $binds + 3; $i++) {
                $params['ARGS'][] = $ARGS[$i];
            }
            if(func_num_args() > $binds + 3) {
                $return = $ARGS[func_num_args() - 1];
            }
        }

        return $this->query($this->_compileUpdate($table, $params, $where, $return), $params['ARGS']);
    }

    /**
     * Check whether connection is established or not
     *
     * @return bool true if var is a resource, false otherwise
     */
    protected function isConnected() {
        return is_resource($this->dbResource);
    }

    /**
     * Check connection existence and do connection if not
     *
     * @return void
     */
    private function connectionPreCheck() {
        if(!$this->isConnected()) {
            $this->_connect();
        }
    }

    private function convertTypes(&$data, $type) {
        if($this->Options->isConvertNumeric()) {
            $this->_convertIntFloat($data, $type);
        }
        if($this->Options->isConvertBoolean()) {
            $this->_convertBoolean($data, $type);
        }

        return $data;
    }

    /**
     * @param $ARGS
     *
     * @return string
     * @throws \DBD\Base\DBDPHPException
     */
    private function getExec($ARGS) {
        $exec = $this->query;
        $binds = substr_count($this->query, "?");
        $args = DBDHelper::parseArgs($ARGS);

        $numberOfArgs = count($args);

        if($binds != $numberOfArgs) {
            throw new Exception("Execute failed: called with $numberOfArgs bind variables when $binds are needed");
        }

        if($numberOfArgs) {
            $query = str_split($this->query);

            foreach($query as $ind => $str) {
                if($str == '?') {
                    $query[$ind] = $this->_escape(array_shift($args));
                }
            }
            $exec = implode("", $query);
        }

        return $exec;
    }

    /**
     * @see affectedRows
     * @see rows
     * @see Pg::_affectedRows
     * @see MSSQL::_affectedRows
     * @see MySQL::_affectedRows
     * @see OData::_affectedRows
     * @return int number of updated or deleted rows
     */
    abstract protected function _affectedRows();

    /**
     * @see begin
     * @see Pg::_begin
     * @see MSSQL::_begin
     * @see MySQL::_begin
     * @see OData::_begin
     * @return bool true on success begin
     */
    abstract protected function _begin();

    /**
     * @see commit
     * @see Pg::_commit
     * @see MSSQL::_commit
     * @see MySQL::_commit
     * @see OData::_commit
     * @return bool true on success commit
     */
    abstract protected function _commit();

    /**
     * @see insert
     * @see Pg::_compileInsert
     * @see MSSQL::_compileInsert
     * @see MySQL::_compileInsert
     * @see OData::_compileInsert
     *
     * @param        $table
     * @param        $params
     * @param string $return
     *
     * @return mixed
     */
    abstract protected function _compileInsert($table, $params, $return = "");

    /**
     * @see update
     * @see Pg::_compileUpdate
     * @see MSSQL::_compileUpdate
     * @see MySQL::_compileUpdate
     * @see OData::_compileUpdate
     *
     * @param        $table
     * @param        $params
     * @param        $where
     * @param string $return
     *
     * @return mixed
     */
    abstract protected function _compileUpdate($table, $params, $where, $return = "");

    /**
     * @see connectionPreCheck
     * @see Pg::_connect
     * @see MSSQL::_connect
     * @see MySQL::_connect
     * @see OData::_connect
     * @return \DBD\DBD
     */
    abstract protected function _connect();

    /**
     * @see convertTypes
     * @see Pg::_convertBoolean
     * @see MSSQL::_convertBoolean
     * @see MySQL::_convertBoolean
     * @see OData::_convertBoolean
     *
     * @param $data
     * @param $type
     *
     * @return mixed
     */
    abstract protected function _convertBoolean(&$data, $type);

    /**
     * @see convertTypes
     * @see Pg::_convertIntFloat
     * @see MSSQL::_convertIntFloat
     * @see MySQL::_convertIntFloat
     * @see OData::_convertIntFloat
     *
     * @param $data
     * @param $type
     *
     * @return mixed
     */
    abstract protected function _convertIntFloat(&$data, $type);

    /**
     * @see disconnect
     * @see Pg::_disconnect
     * @see MSSQL::_disconnect
     * @see MySQL::_disconnect
     * @see OData::_disconnect
     * @return bool true on successful disconnection
     */
    abstract protected function _disconnect();

    /**
     * @see Pg::_errorMessage
     * @see MSSQL::_errorMessage
     * @see MySQL::_errorMessage
     * @see OData::_errorMessage
     * @return string
     */
    abstract protected function _errorMessage();

    /**
     * @see getExec
     * @see Pg::_escape
     * @see MSSQL::_escape
     * @see MySQL::_escape
     * @see OData::_escape
     *
     * @param $string
     *
     * @return mixed
     */
    abstract protected function _escape($string);

    /**
     * @see fetch
     * @see Pg::_fetchArray
     * @see MSSQL::_fetchArray
     * @see MySQL::_fetchArray
     * @see OData::_fetchArray
     * @return mixed
     */
    abstract protected function _fetchArray();

    /**
     * @see fetchRow
     * @see Pg::_fetchAssoc
     * @see MSSQL::_fetchAssoc
     * @see MySQL::_fetchAssoc
     * @see OData::_fetchAssoc
     * @return mixed
     */
    abstract protected function _fetchAssoc();

    /**
     * @see execute
     * @see rows
     * @see Pg::_numRows
     * @see MSSQL::_numRows
     * @see MySQL::_numRows
     * @see OData::_numRows
     * @return mixed
     */
    abstract protected function _numRows();

    /**
     * @see execute
     * @see Pg::_query
     * @see MSSQL::_query
     * @see MySQL::_query
     * @see OData::_query
     *
     * @param $statement
     *
     * @return mixed
     */
    abstract protected function _query($statement);

    /**
     * @see rollback
     * @see Pg::_rollback
     * @see MSSQL::_rollback
     * @see MySQL::_rollback
     * @see OData::_rollback
     * @return bool true on successful rollback
     */
    abstract protected function _rollback();

    /**
     * @see Pg::connect
     * @see MSSQL::connect
     * @see MySQL::connect
     * @see OData::connect
     * @return mixed
     */
    abstract protected function connect();
}
