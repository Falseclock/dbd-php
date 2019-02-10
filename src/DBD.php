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
use DBD\Base\DBDOptions;
use DBD\Base\DBDPHPDebug as Debug;
use DBD\Base\DBDPHPException as Exception;

/**
 * Class DBD
 *
 * @package DBD
 */
abstract class DBD
{
    const UNDEFINED = "UNDEF";
    //private $affected = 0;
    public static $debug = [ 'total_queries' => 0, 'total_cost' => 0, 'per_driver' => [] ];
    public        $rows  = 0;
    /** @var \Psr\SimpleCache\CacheInterface|\DBD\Cache */
    protected $CacheDriver = null;
    /** @var DBDOptions $Options */
    protected $Options;
    /** @var DBDConfig $Config */
    protected $Config;
    protected $cache         = [
        'key'      => null,
        'result'   => null,
        'compress' => null,
        'expire'   => null,
    ];
    protected $dbResource    = null;
    protected $fetch         = self::UNDEFINED;
    protected $query         = "";
    protected $result        = null;
    protected $storage       = false;
    protected $inTransaction = false;

    /**
     * @return \DBD\Cache|\Psr\SimpleCache\CacheInterface
     */
    public function getCache() {
        return $this->CacheDriver;
    }

    /**
     * @param \DBD\Cache|\Psr\SimpleCache\CacheInterface $cache
     *
     * @return \DBD\DBD
     * @throws \DBD\Base\DBDPHPException
     */
    public function setCache($cache) {

        if($cache instanceof Cache || $cache instanceof \Psr\SimpleCache\CacheInterface) {
            $this->CacheDriver = $cache;

            return $this;
        }

        throw new Exception('Unsupported caching interface. Extend DBD\\Cache or use PSR-16 Common Interface for Caching');
    }

    public function affected() {
        return $this->_affectedRows();
    }

    abstract protected function _affectedRows();

    /**
     * Starts database transaction
     *
     * @return $this
     * @throws \DBD\Base\DBDPHPException
     */
    public function begin() {
        $this->connectionPreCheck();
        $this->result = $this->_begin();
        if($this->result === false)
            throw new Exception("Can not start transaction: " . $this->_errorMessage());

        $this->inTransaction = true;

        return $this;
    }

    /**
     * Check connection existence and do connection if not
     *
     * @return $this
     */
    private function connectionPreCheck() {
        if(!$this->isConnected()) {
            $this->_connect();
        }

        return $this;
    }

    abstract protected function _begin();

    abstract protected function _errorMessage();

    protected function isConnected() {
        return is_resource($this->dbResource);
    }

    abstract protected function _connect();

    public function cache($key, $expire = null, $compress = null) {
        if(!isset($key) or !$key) {
            throw new Exception("caching failed: key is not set or empty");
        }
        if(!isset($this->CacheDriver)) {
            //return;
            throw new Exception("CacheDriver not initialized");
        }
        if(preg_match("/^[\s\t\r\n]*select/i", $this->query)) {
            // set hash key
            $this->cache['key'] = $key;

            if($compress !== null)
                $this->cache['compress'] = $compress;

            if($expire !== null)
                $this->cache['expire'] = $expire;
        }
        else {
            throw new Exception("caching failed: current query is not of SELECT type");
        }

        return;
    }

    /**
     * Commits a transaction that was begun
     *
     * @return $this
     * @throws \DBD\Base\DBDPHPException
     */
    public function commit() {
        if($this->inTransaction) {
            $this->connectionPreCheck();
            $this->result = $this->_commit();
            if($this->result === false)
                throw new Exception("Can not commit transaction: " . $this->_errorMessage());
        }
        else {
            throw new Exception("No transaction to commit");
        }
        $this->inTransaction = false;

        return $this;
    }

    abstract protected function _commit();

    /**
     * @param DBDConfig        $config
     * @param array|DBDOptions $options
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

        if(!isset($options)) {
            $db->Options = new DBDOptions;
        }
        if($options instanceof DBDOptions) {
            $db->Options = $options;
        }
        else {
            throw new Exception("options are not instance of DBDOptions");
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
                $this->rollback();
            }
            $this->_disconnect();
            $this->dbResource = null;
        }

        return $this;
    }

    /**
     * Rolls back a transaction that was begun
     *
     * @return $this
     * @throws \DBD\Base\DBDPHPException
     */
    public function rollback() {
        if($this->inTransaction) {
            $this->connectionPreCheck();
            $this->result = $this->_rollback();
            if($this->result === false)
                throw new Exception("Can not end transaction " . pg_errormessage());
        }
        else {
            throw new Exception("No transaction to rollback");
        }
        $this->inTransaction = false;

        return $this;
    }

    abstract protected function _disconnect();

    abstract protected function _rollback();

    /**
     * @deprecated
     * @return int
     * @throws \DBD\Base\DBDPHPException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \ReflectionException
     */
    public function du() {
        return $this->doit(func_get_args());
    }

    /**
     * @return int
     * @throws \DBD\Base\DBDPHPException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \ReflectionException
     */
    public function doit() {
        if(!func_num_args())
            throw new Exception("query failed: statement is not set or empty");

        list ($statement, $args) = $this->prepareArgs(func_get_args());

        $sth = $this->query($statement, $args);

        return $sth->rows;
    }

    private function prepareArgs($ARGS) {
        $statement = array_shift($ARGS);
        $args = $this->parseArgs($ARGS);

        return [
            $statement,
            $args,
        ];
    }

    /**
     * @return \DBD\DBD
     * @throws \DBD\Base\DBDPHPException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \ReflectionException
     */
    public function query() {
        if(!func_num_args())
            throw new Exception("query failed: statement is not set or empty");

        list ($statement, $args) = $this->prepareArgs(func_get_args());

        $sth = $this->prepare($statement);

        if(is_array($args)) {
            $sth->execute($args);
        }
        else {
            $sth->execute();
        }

        return $sth;
    }

    protected function parseArgs($ARGS) {
        $args = [];

        foreach($ARGS as $arg) {
            if(is_array($arg)) {
                foreach($arg as $subArg) {
                    $args[] = $subArg;
                }
            }
            else {
                $args[] = $arg;
            }
        }

        return $args;
    }

    /**
     * Creates a prepared statement for later execution
     *
     * @param string $statement
     *
     * @return DBD
     * @throws \DBD\Base\DBDPHPException
     */
    public function prepare($statement) {
        if(!isset($statement) or empty($statement))
            throw new Exception("prepare failed: statement is not set or empty");

        $className = get_class($this);

        return new $className($this, $statement);
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
                    $this->result = 'cached';
                    $this->storage = 'cache';
                    $this->rows = count($this->cache['result']);
                }
            }
        }

        // If not found in cache, then let's get from DB
        if($this->result != 'cached') {

            $this->connectionPreCheck();
            if($this->Options->isUseDebug()) {
                Debug::me()->startTimer();
            }
            // Execute query to the database
            $this->result = $this->_query($exec);
            $cost = Debug::me()->endTimer();

            if($this->result !== false) {
                $this->rows = $this->_numRows();
                $this->storage = 'database';
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
                    $this->cache['result'] = $this->fetchrowset();
                }
                else {
                    // select is empty
                    $this->cache['result'] = [];
                }

                // reverting all back, cause we stored data to cache
                $this->result = 'cached';
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

            $index = $this->storage == 'cache' ? 'Cache' : $this->getDriver();

            $caller = $this->caller();

            @self::$debug['queries'][$index][] = [
                'query'   => $this->cleanSql($exec),
                'cost'    => $cost,
                'caller'  => $caller[0],
                'explain' => null,
                'mark'    => $this->debugMark($cost),
            ];
            @self::$debug['total_queries'] += 1;
            @self::$debug['total_cost'] += $cost;
            if(!isset($debug['per_driver'][$index])) {
                self::$debug['per_driver'] = [
                    $index => [ 'total' => 0, 'cost' => 0 ],
                ];
            }
            @self::$debug['per_driver'][$index]['total'] += 1;
            @self::$debug['per_driver'][$index]['cost'] += $cost;
        }

        return $this->result;
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
        $args = $this->parseArgs($ARGS);

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

    abstract protected function _query($statement);

    abstract protected function _numRows();

    /**
     * Will return the number of rows in a database result resource.
     *
     * @return int
     */
    public function rows() {
        if($this->cache['key'] === null) {
            if(preg_match('/^(\s*?)select\s*?.*?\s*?from/is', $this->query)) {
                return $this->_numRows();
            }

            return $this->_affectedRows();
        }
        else {
            return count($this->cache['result']);
        }
    }

    public function fetchrowset($key = null) {
        $array = [];

        if($this->cache['key'] === null) {
            while($row = $this->fetchrow()) {
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
     * @return string
     * @throws \ReflectionException
     */
    private function getDriver() {
        return (new \ReflectionClass($this))->getParentClass()->getShortName();
    }

    /**
     * @return array
     * @throws \ReflectionException
     */
    protected function caller() {
        $return = [];
        $debug = debug_backtrace();

        // working directory
        $wd = is_link($_SERVER["DOCUMENT_ROOT"]) ? readlink($_SERVER["DOCUMENT_ROOT"]) : $_SERVER["DOCUMENT_ROOT"];
        $wd = str_replace(DIRECTORY_SEPARATOR, "/", $wd);

        $myFilename = $debug[0]['file'];
        $myFilename = str_replace(DIRECTORY_SEPARATOR, "/", $myFilename);
        $myFilename = str_replace($wd, '', $myFilename);

        $child = (new \ReflectionClass($this))->getShortName();

        foreach($debug as $ind => $call) {
            // our filename
            if(isset($call['file'])) {
                $call['file'] = str_replace(DIRECTORY_SEPARATOR, "/", $call['file']);
                $call['file'] = str_replace($wd, '', $call['file']);

                if($myFilename != $call['file'] && !preg_match('/' . $child . '\.\w+$/', $call['file'])) {
                    $return[] = [
                        'file'     => $call['file'],
                        'line'     => $call['line'],
                        'function' => $call['function'],
                    ];
                }
            }
        }

        return $return;
    }

    private function cleanSql($exec) {
        $array = preg_split('/\R/u', $exec);

        foreach($array as $idx => $line) {
            //$array[$idx] = trim($array[$idx], "\s\t\n\r");
            if(!$array[$idx] || preg_match('/^[\s\R\t]*?$/u', $array[$idx])) {
                unset($array[$idx]);
                continue;
            }
            if(preg_match('/^\s*?(UNION|CREATE|DELETE|UPDATE|SELECT|FROM|WHERE|JOIN|LIMIT|OFFSET|ORDER|GROUP)/i', $array[$idx])) {
                $array[$idx] = ltrim($array[$idx]);
            }
            else {
                $array[$idx] = "    " . ltrim($array[$idx]);
            }
        }

        return implode("\n", $array);
    }

    private function debugMark($cost) {
        switch(true) {
            case ($cost >= 0 && $cost <= 20):
                return 1;
            case ($cost >= 21 && $cost <= 50):
                return 2;
            case ($cost >= 51 && $cost <= 90):
                return 3;
            case ($cost >= 91 && $cost <= 140):
                return 4;
            case ($cost >= 141 && $cost <= 200):
                return 5;
            default:
                return 6;
        }
    }

    abstract protected function _escape($string);

    public function fetchrow() {
        if($this->cache['key'] === null) {
            $return = $this->_fetchAssoc();

            if($this->Options->isConvertNumeric() || $this->Options->isConvertBoolean()) {
                return $this->_convertTypes($return, 'row');
            }

            return $return;
        }
        else {
            return array_shift($this->cache['result']);
        }
    }

    abstract protected function _fetchAssoc();

    private function _convertTypes(&$data, $type) {
        if($this->Options->isConvertNumeric()) {
            $this->_convertIntFloat($data, $type);
        }
        if($this->Options->isConvertBoolean()) {
            $this->_convertBoolean($data, $type);
        }

        return $data;
    }

    abstract protected function _convertIntFloat(&$data, $type);

    abstract protected function _convertBoolean(&$data, $type);

    public function fetcharrayset() {
        $array = [];

        if($this->cache['key'] === null) {
            while($row = $this->fetchrow()) {
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
        $params = $this->compileInsertArgs($args);

        $sth = $this->prepare($this->_compileInsert($table, $params, $return));
        $sth->execute($params['ARGS']);

        return $sth;
    }

    private function compileInsertArgs($data) {

        $columns = "";
        $values = "";
        $args = [];

        foreach($data as $c => $v) {
            $pattern = "/[^\"a-zA-Z0-9_-]/";
            $c = preg_replace($pattern, "", $c);
            $columns .= "$c, ";
            $values .= "?,";
            if($v === true) {
                $v = 'true';
            }
            if($v === false) {
                $v = 'false';
            }
            $args[] = $v;
        }

        $columns = preg_replace("/, $/", "", $columns);
        $values = preg_replace("/,$/", "", $values);

        return [
            'COLUMNS' => $columns,
            'VALUES'  => $values,
            'ARGS'    => $args,
        ];
    }

    abstract protected function _compileInsert($table, $params, $return = "");

    /**
     * @deprecated
     * @return string
     */
    public function printDebug() {
        return null;
    }

    public function getDebug() {
        $debug = self::$debug;
        if(count($debug['per_driver'])) {
            foreach($debug['per_driver'] as $key => $row) {
                $debug['per_driver'][$key]['mark'] = $this->debugMark($row['cost'] / $row['total']);
            }
        }

        return $debug;
    }

    /**
     * @return bool|mixed
     * @throws \DBD\Base\DBDPHPException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \ReflectionException
     */
    public function select() {
        list ($statement, $args) = $this->prepareArgs(func_get_args());

        $sth = $this->query($statement, $args);

        if($sth->rows()) {
            return $sth->fetch();
        }

        return null;
    }

    public function fetch() {
        if($this->fetch == self::UNDEFINED) {

            if($this->cache['key'] === null) {

                $return = $this->_fetchArray();

                if($this->Options->isConvertNumeric() || $this->Options->isConvertBoolean()) {
                    $return = $this->_convertTypes($return, 'row');
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

    abstract protected function _fetchArray();

    /**
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

        $params = $this->compileUpdateArgs($values);

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

    private function compileUpdateArgs($data) {

        $columns = "";
        $args = [];

        $pattern = "/[^\"a-zA-Z0-9_-]/";
        foreach($data as $k => $v) {
            $k = preg_replace($pattern, "", $k);
            $columns .= "$k = ?, ";
            $args[] = $v;
        }

        $columns = preg_replace("/, $/", "", $columns);

        return [
            'COLUMNS' => $columns,
            'ARGS'    => $args,
        ];
    }

    abstract protected function _compileUpdate($table, $params, $where, $return = "");

    abstract protected function _queryExplain($statement);

    abstract protected function connect();

    /**
     * Copies object variables after extended class construction
     *
     * @param        $object
     * @param string $statement
     *
     * @return void
     */
    protected function extendMe($object, $statement = "") {
        foreach(get_object_vars($object) as $key => $value) {
            $this->$key = $value;
        }
        $this->query = $statement;

        if(isset($this->CacheDriver)) {
            $this->cache['compress'] = $this->CacheDriver->useCompression;
            $this->cache['expire'] = $this->CacheDriver->defaultTtl;
        }
    }
}