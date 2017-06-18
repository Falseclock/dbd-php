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

use DBD\Base\Debug;
use DBD\Base\ErrorHandler;
use Exception;

/**
 * Class DBD
 *
 * @package DBD
 */
abstract class DBD
{
    public $rows = 0;
    //private $affected = 0;
    protected $fetch       = "UNDEF";
    protected $myDebug     = true;
    protected $dsn         = null;
    protected $database    = null;
    protected $username    = null;
    protected $password    = null;
    protected $dbh         = null;
    protected $query       = "";
    protected $result      = null;
    protected $debug       = null;
    protected $cache       = [
        'key'      => null,
        'result'   => null,
        'compress' => null,
        'expire'   => null,
    ];
    protected $options     = [
        'OnDemand'           => false,
        'PrintError'         => true,
        'RaiseError'         => true,
        'ShowErrorStatement' => false,
        'HTMLError'          => false,
        'ConvertNumeric'     => false,
        'UseDebug'           => false,
        'ErrorHandler'       => null,
        /** @var \DBD\Cache CacheDriver */
        'CacheDriver'        => null,
    ];
    protected $transaction = false;

    // ----------- PUBLIC ------------------
 
    /**
     * @param string $dsn
     * @param string $database
     * @param string $username
     * @param string $password
     * @param array  $options
     *
     * @return $this
     */
    public function create($dsn, $database, $username, $password, $options = []) {
        $driver = get_class($this);

        /** @var \DBD\DBD $db */
        $db = new $driver;

        return $db->setDsn($dsn)
                  ->setDatabase($database)
                  ->setUsername($username)
                  ->setPassword($password)
                  ->setOptions($options)
            ;
    }

    /**
     * Closes a database connection
     *
     * @return $this
     */
    public function disconnect() {
        if($this->isConnected()) {
            if($this->transaction) {
                $this->rollback();
            }
            if(!$this->options['Persistent']) {
                $this->_disconnect();
            }
        }
        if(is_resource($this->cacheDriver())) {
            $this->cacheDriver()
                 ->close()
            ;
        }

        return $this;
    }

    /**
     * Rolls back a transaction that was begun
     *
     * @return $this
     */
    public function rollback() {
        if($this->transaction) {
            $this->connectionPreCheck();
            $this->result = $this->_rollback();
            if($this->result === false)
                trigger_error("Can not end transaction " . pg_errormessage(), E_USER_ERROR);
        }
        else {
            trigger_error("No transaction to rollback", E_USER_ERROR);
        }
        $this->transaction = false;

        return $this;
    }

    /**
     * Starts database transaction
     *
     * @return $this
     */
    public function begin() {
        $this->connectionPreCheck();
        $this->result = $this->_begin();
        if($this->result === false)
            trigger_error("Can not start transaction " . $this->_errorMessage(), E_USER_ERROR);

        $this->transaction = true;

        return $this;
    }

    /**
     * Commits a transaction that was begun
     *
     * @return $this
     */
    public function commit() {
        if($this->transaction) {
            $this->connectionPreCheck();
            $this->result = $this->_commit();
            if($this->result === false)
                trigger_error("Can not end transaction " . $this->_errorMessage(), E_USER_ERROR);
        }
        else {
            trigger_error("No transaction to commit", E_USER_ERROR);
        }
        $this->transaction = false;

        return $this;
    }

    /**
     * @deprecated
     */
    public function du() {
        return $this->do(func_get_args());
    }

    public function do() {
        if(!func_num_args())
            trigger_error("query failed: statement is not set or empty", E_USER_ERROR);

        list ($statement, $args) = $this->prepareArgs(func_get_args());

        $sth = $this->query($statement, $args);

        return $sth->rows;
    }

    public function query() {
        if(!func_num_args())
            trigger_error("query failed: statement is not set or empty", E_USER_ERROR);

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

    /**
     * Creates a prepared statement for later execution
     *
     * @param string $statement
     *
     * @return DBD
     */
    public function prepare($statement) {
        if(!isset($statement) or empty($statement))
            trigger_error("prepare failed: statement is not set or empty", E_USER_ERROR);

        $className = get_class($this);

        return new $className($this, $statement);
    }

    /**
     * Sends a request to execute a prepared statement with given parameters, and waits for the result.
     *
     * @return bool|mixed|null|string
     */
    public function execute() {
        // Set result to false
        $this->result = false;
        $this->fetch = "UNDEF";

        //--------------------------------------
        // Is query uses cache?
        //--------------------------------------
        if($this->cacheDriver()) {
            if($this->cache['key'] !== null) {
                // Get data from cache
                $this->cache['result'] = $this->cacheDriver()
                                              ->get($this->cache['key'])
                ;

                // Cache not empty?
                if($this->cache['result'] && $this->cache['result'] !== false) {
                    // To avoid errors as result by default is NULL
                    $this->result = 'cached';
                    $this->rows = count($this->cache['result']);
                    // Do not show in debug, cause data taken from cache
                    //$storeDebug = 0;
                }
            }
        }

        $exec = $this->query;

        // If not found in cache, then let's get from DB
        if($this->result != 'cached') {
            // Store debug
            $storeDebug = 1;

            $binds = substr_count($this->query, "?");
            $ARGS = func_get_args();
            $args = $this->parseArgs($ARGS);

            $numargs = count($args);

            if($binds != $numargs) {
                $caller = $this->caller();

                trigger_error("Execute failed: called with 
					$numargs bind variables when $binds are needed at 
					{$caller[0]['file']} line {$caller[0]['line']}", E_USER_ERROR);
            }

            if($numargs) {
                $query = str_split($this->query);

                foreach($query as $ind => $str) {
                    if($str == '?') {
                        $query[$ind] = $this->_escape(array_shift($args));
                    }
                }
                $exec = implode("", $query);
            }
            // FIXME: what for I did this?
            // Print query to window for debug purposes
            //if ($this->obj['print']) {
            //	print ($exec);
            //}

            //--------------------------------------
            // Debug?
            //--------------------------------------

            if($storeDebug and $this->options['UseDebug']) {
                $Debug = new Debug;
                $Debug->startTimer();
            }
            $this->connectionPreCheck();
            // Execute query to the database
            $this->result = $this->_query($exec);

            if($this->result !== false) {
                $this->rows = $this->_affectedRows();
            }

            // If query from cache
            if($this->cache['key'] !== null) {
                //  As we already queried database we have to set key to NULL
                //  because during internal method invoke (fetchrowset below) this Driver
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
                $this->cacheDriver()
                     ->set($this->cache['key'], $this->cache['result'], $this->cache['expire'])
                ;
            }
        }
        if($this->result === false) {
            new ErrorHandler ($exec, $this->_errorMessage(), $this->caller(), $this->options);
        }

        /*
        if($storeDebug)
        {
            //--------------------------------------
            // Debug?
            //--------------------------------------

                        if ($DB->obj['debug'])
                        {
                            $caller = $this->caller();

                            $endtime  = $Debug->endTimer();
                            $site->debug['RuntimeDB'] += $endtime;
                            $current = count($site->debug['db']);

                            $site->debug['db'][$current]['caller_file'] = $caller['file'];
                            $site->debug['db'][$current]['caller_line'] = $caller['line'];
                            $site->debug['db'][$current]['time'] = $endtime;

                            $site->debug['db'][$current]['query'] = $exec;

                            if ( preg_match( "/^[\s\t\r\n]*select/i", $exec ) )
                            {
                                $this->connectionPreCheck();
                                $explain = $this->_queryExplain($exec);

                                while ($row = pg_fetch_row($explain))
                                {
                                    $site->debug['db'][$current]['explain'] .= $row[0]."\n";
                                }
                            }
                        }
                        $DB->obj['cache'][] = $exec;

        }
        */

        return $this->result;
    }

    /**
     * Will return the number of rows in a database result resource.
     *
     * @return int
     */
    public function rows() {
        if($this->cache['key'] === null) {
            if(preg_match('/^(\s*?)select\s*?.*?\s*?from/i', $this->query)) {
                return $this->_numRows();
            }

            return $this->_affectedRows();
        }
        else {
            return count($this->cache['result']);
        }
    }

    public function affected() {
        return $this->_affectedRows();
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

    public function fetchrow() {
        if($this->cache['key'] === null) {
            $return = $this->_fetchAssoc();

            if($this->options['ConvertNumeric']) {
                return $this->_convertIntFloat($return, 'row');
            }

            return $return;
        }
        else {
            return array_shift($this->cache['result']);
        }
    }

    public function select() {
        list ($statement, $args) = $this->prepareArgs(func_get_args());

        $sth = $this->query($statement, $args);

        return $sth->fetch();
    }

    public function fetch() {
        if($this->fetch == "UNDEF") {

            if($this->cache['key'] === null) {

                $return = $this->_fetchArray();

                if($this->options['ConvertNumeric']) {

                    $return = $this->_convertIntFloat($return, 'row');
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

    /**
     * Easy insert operation
     *
     * @param string $table
     * @param array  $args
     * @param null   $return
     *
     * @return \DBD\DBD
     */
    public function insert($table, $args, $return = null) {
        $params = $this->compileInsertArgs($args);

        $sth = $this->prepare($this->_compileInsert($table, $params, $return));
        $sth->execute($params['ARGS']);

        return $sth;
    }

    public function setOption($key, $value) {
        if(array_key_exists($key, $this->options)) {
            $this->options[$key] = $value;

            return $value;
        }
        else {
            throw new Exception("Unknown option provided : '{$key}'");
        }
    }

    public function getOption($key) {
        if(array_key_exists($key, $this->options)) {
            return $this->options[$key];
        }
        else {
            throw new Exception("Unknown option provided");
        }
    }

    public function cache($key, $expire = null, $compress = null) {
        if(!isset($key) or !$key) {
            trigger_error("caching failed: key is not set or empty", E_USER_ERROR);
        }
        if($this->cacheDriver() == null) {
            return;
            //trigger_error("CacheDriver not initialized", E_USER_ERROR);
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
            trigger_error("caching failed: current query is not of SELECT type", E_USER_ERROR);
        }

        return;
    }

    public function drop($key) {
        if($this->cacheDriver() != null) {
            $this->cacheDriver()
                 ->delete($key)
            ;
        }
        else {
            trigger_error("CacheDriver not initialized", E_USER_ERROR);
        }

        return;
    }

    //region PROTECTED methods

    /*
        public function replace($key)
        {
            if($this->cacheDriver() != null)
            {
                $this->cacheDriver()->replace($key);
            }
            else
            {
                trigger_error("CacheDriver not initialized", E_USER_ERROR);
            }

            return;
        }
    */

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

        if($this->cacheDriver()) {
            $this->cache['compress'] = $this->cacheDriver()->COMPRESS;
            $this->cache['expire'] = $this->cacheDriver()->EXPIRE;
        }
    }

    protected function parseArgs($ARGS) {
        $args = [];

        foreach($ARGS as $arg) {
            if(is_array($arg)) {
                foreach($arg as $subarg) {
                    $args[] = $subarg;
                }
            }
            else {
                $args[] = $arg;
            }
        }

        return $args;
    }

    protected function isConnected() {
        return is_resource($this->dbh);
    }

    /**
     * @return \DBD\Cache
     */
    protected function cacheDriver() {
        return $this->options['CacheDriver'];
    }

    protected function caller() {
        $return = [];
        $debug = debug_backtrace();

        // working directory
        $wd = $_SERVER["DOCUMENT_ROOT"];
        $wd = str_replace(DIRECTORY_SEPARATOR, "/", $wd);

        $myFilename = $debug[0]['file'];
        $myFilename = str_replace(DIRECTORY_SEPARATOR, "/", $myFilename);
        $myFilename = str_replace($wd, '', $myFilename);

        $child = (new \ReflectionClass($this))->getShortName();

        foreach($debug as $ind => $call) {
            // our filename
            $call['file'] = str_replace(DIRECTORY_SEPARATOR, "/", $call['file']);
            $call['file'] = str_replace($wd, '', $call['file']);

            if($myFilename != $call['file'] && !preg_match('/' . $child . '\.\w+$/', $call['file'])) {
                $return[] = [
                    'file'     => $call['file'],
                    'line'     => $call['line'],
                    'function' => $call['function']
                ];
            }
        }

        return $return;
    }

    //endregion

    //region PRIVATE methods

    private function setOptions($options) {
        foreach($options as $key => $value) {
            if(is_string($key)) {
                if(array_key_exists($key, $this->options)) {
                    $this->options[$key] = $value;
                }
                else {
                    throw new Exception("Unknown option provided");
                }
            }
            else {
                throw new Exception("Option must be a string");
            }
        }

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

    private function prepareArgs($ARGS) {
        $statement = array_shift($ARGS);
        $args = $this->parseArgs($ARGS);

        return [
            $statement,
            $args
        ];
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
            'ARGS'    => $args
        ];
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
                $v = 't';
            }
            if($v === false) {
                $v = 'f';
            }
            $args[] = $v;
        }

        $columns = preg_replace("/, $/", "", $columns);
        $values = preg_replace("/,$/", "", $values);

        return [
            'COLUMNS' => $columns,
            'VALUES'  => $values,
            'ARGS'    => $args
        ];
    }

    private function setPassword($password) {
        if($password)
            $this->password = $password;

        return $this;
    }

    private function setUsername($username) {
        $this->username = $username;

        return $this;
    }

    private function setDatabase($database) {
        $this->database = $database;

        return $this;
    }

    /**
     * @param $dsn
     *
     * @return $this
     */
    private function setDsn($dsn) {
        $this->dsn = $dsn;

        return $this;
    }

    //endregion

    //region ABSTRACT methods

    abstract protected function connect();

    abstract protected function _connect();

    abstract protected function _disconnect();

    abstract protected function _begin();

    abstract protected function _commit();

    abstract protected function _rollback();

    abstract protected function _query($statement);

    abstract protected function _queryExplain($statement);

    abstract protected function _errorMessage();

    abstract protected function _numRows();

    abstract protected function _escape($string);

    abstract protected function _affectedRows();

    abstract protected function _fetchAssoc();

    abstract protected function _fetchArray();

    abstract protected function _convertIntFloat(&$data, $type);

    abstract protected function _compileInsert($table, $params, $return = "");

    abstract protected function _compileUpdate($table, $params, $where, $return = "");
    //endregion
}