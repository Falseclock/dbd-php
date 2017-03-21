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
use DBD\Base\Debug as Debug;
use Exception;

class Pg extends DBD {

    /**
     * @return PgExtend
     */
    public function connect()
    {
        $dsn = "user={$this->username} ";
        $dsn .= $this->password ? "password={$this->password} " : "";

        $chunks = array_chunk(preg_split('/(=|;)/', $this->dsn), 2);
        $options = array_combine(array_column($chunks, 0), array_column($chunks, 1));

        foreach ($options as $key => $value) {
            $dsn .= "{$key}={$value} ";
        }
        //--------------------------
        // Connect
        //--------------------------
        if ($this->options['Persistent'])
            $this->dbh = pg_pconnect($dsn);
        else
            $this->dbh = pg_connect($dsn);

        if ( ! $this->dbh )
            trigger_error("Can not connect to PostgreSQL server: " .pg_errormessage(), E_USER_ERROR);

        return new PgExtend($this);
    }

    public function disconnect()
    {
        if ($this->isConnected())
            pg_close($this->dbh);

        return $this;
    }
    public function begin()
    {
        $this->result = @pg_query($this->dbh, "BEGIN;");
        if ($this->result === false )
            trigger_error("Can not start transaction " .pg_errormessage(), E_USER_ERROR);

        $this->transaction = true;
        return $this;
    }

    public function commit()
    {
        if ($this->transaction) {
            $this->result = @pg_query($this->dbh, "COMMIT;");
            if ($this->result === false )
                trigger_error("Can not end transaction " . pg_errormessage(), E_USER_ERROR);
        } else {
            trigger_error("No transaction to commit", E_USER_ERROR);
        }
        $this->transaction = false;
        return $this;
    }

    public function rollback()
    {
        if ($this->transaction) {
            $this->result = @pg_query($this->dbh, "ROLLBACK;");
            if ($this->result === false)
                trigger_error("Can not end transaction " . pg_errormessage(), E_USER_ERROR);
        } else {
            trigger_error("No transaction to rollback", E_USER_ERROR);
        }
        $this->transaction = false;
        return $this;
    }
}

final class PgExtend extends Pg implements DBI {

    private $rows       = 0;
    private $affected   = 0;
    private $fetch      = "UNDEF";

    public function __construct($object, $statement = "")
    {
        foreach (get_object_vars($object) as $key => $value) {
            $this->$key = $value;
        }
        $this->query = $statement;

        if ($this->options['CacheDriver']) {
            $this->cache['compress'] = $this->options['CacheDriver']->COMPRESS;
            $this->cache['expire'] = $this->options['CacheDriver']->EXPIRE;
        }
    }

    public function prepare($statement)
    {
        if ( ! isset($statement) or empty($statement))
            trigger_error("prepare failed: statement is not set or empty", E_USER_ERROR);

        return new PgExtend($this, $statement);
    }

    public function du()
    {
        if ( !func_num_args() )
            trigger_error("query failed: statement is not set or empty", E_USER_ERROR);

        $ARGS		= func_get_args();
        $statement	= array_shift($ARGS);
        $args		= $this->parse_args($ARGS);

        $sth = $this->prepare($statement);

        if (is_array($args)) {
            $sth->execute($args);
        } else {
            $sth->execute();
        }

        $sth->affected = @pg_affected_rows($sth->result);

        return $sth;
    }

    public function execute()
    {
        // Set result to false
        $this->result = false;
        $this->fetch = "UNDEF";

        //--------------------------------------
        // Is query uses cache?
        //--------------------------------------
        if ($this->options['CacheDriver'])
        {
            if ($this->cache['key'] !== null)
            {
                // Get data from cache
                $this->cache['result'] = Cache::me()->get($this->cache['key']);

                // Cache not empty?
                if ($this->cache['result'] && $this->cache['result'] !== false)
                {
                    // To avoid errors as result by default is NULL
                    $this->result = 'cached';
                    // Do not show in debug, cause data taken from cache
                    $storeDebug = 0;
                }
            }
        }

        // If not found in cache, then let's get from DB
        if ($this->result != 'cached')
        {
            // Store debug
            $storeDebug = 1;

            $binds	= substr_count($this->query,"?");
            $ARGS	= func_get_args();
            $exec	= $this->query;
            $args	= $this->parse_args($ARGS);

            $numargs = count($args);

            if ($binds != $numargs) {
                $caller = $this->caller();

                trigger_error (
                    "Execute failed: called with 
					$numargs bind variables when $binds are needed at 
					{$caller['file']} line {$caller['line']}",
                    E_USER_ERROR
                );
            }

            if ($numargs) {
                $query = str_split($this->query);

                foreach($query as $ind => $str) {
                    if ($str == '?') {
                        $query[$ind] = $this->quote(array_shift($args));
                    }
                }
                $exec = implode("", $query);
            }
            // FIXME: what for I did this?
            // Print query to window for debug purposes
            //if ($this->obj['print']) {
            //    print ($exec);
            //}

            //--------------------------------------
            // Debug?
            //--------------------------------------

            if ($storeDebug and $this->options['UseDebug'])
            {
                $Debug = new Debug;
                $Debug->startTimer();
            }
            // Execute query to the database
            $this->result = @pg_query($this->dbh, $exec);

            // If query from cache
            if ($this->cache['key'] !== null)
            {
                //  As we already queried database we have to set key to NULL
                //  because during internal method invoke this Driver
                //  will think we have data from cache

                $storedKey = $this->cache['key'];
                $this->cache['key'] = null;

                // If we have data from query
                if ( $this->rows() ) {
                    $this->cache['result'] = $this->fetchrowset();
                } else {
                    // select is empty
                    $this->cache['result'] = array();
                }

                // reverting all back, cause we stored data to cache
                $this->result = 'cached';
                $this->cache['key'] = $storedKey;

                // Setting up our cache
                Cache::me()->set
                (
                    $this->cache['key'],
                    $this->cache['result'],
                    $this->cache['expire']
                );
            }
        }

        if ( $this->result === false )  {
/*
            $this->_error($exec);
            $this->error = 	self::$errstr;
*/
            trigger_error (
                "Query failed: " . pg_last_error($this->dbh),E_USER_ERROR
            );
        } elseif ( $storeDebug ) {
            //--------------------------------------
            // Debug?
            //--------------------------------------
/*
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
                    $explain = pg_query($this->dbh,"EXPLAIN $exec");

                    while ($row = pg_fetch_row($explain))
                    {
                        $site->debug['db'][$current]['explain'] .= $row[0]."\n";
                    }
                }
            }

            $DB->obj['cache'][] = $exec;
*/
        }
        return $this->result;
    }

    public function rows()
    {
        if ($this->cache['key'] === null) {
            return pg_num_rows($this->result);
        } else {
            return count( $this->cache['result'] );
        }
    }

    public function fetchrow()
    {
        if ($this->cache['key'] === null) {
            if( pg_result_status($this->result) == "") {
                return FALSE;
            }
            $return = pg_fetch_assoc($this->result);

            if ($this->options['ConvertNumeric']) {
                return $this->_convertIntFloat($return,'row');
            }
            return $return;
        } else {
            return array_shift($this->cache['result']);
        }
    }

    public function fetchrowset($key = null)
    {
        $array = array();

        if ($this->cache['key'] === null) {
            if ( pg_result_status($this->result) == "" ) {
                return FALSE;
            }

            while ($row = $this->fetchrow()) {
                if ($key) {
                    $array[$row[$key]] = $row;
                } else {
                    $array[] = $row;
                }
            }
        } else {
            $cache = $this->cache['result'];
            $this->cache['result'] = array();

            if ($key) {
                foreach ($cache as $row) {
                    $array[$row[$key]] = $row;
                }
            } else {
                $array = $cache;
            }
        }
        return $array;
    }

    private function _convertIntFloat(&$data,$type)
    {
        // TODO: do this for fetch and check for other types of returns
        // TODO: in case of fetchrowset do not get each time and use static variable

        if ($data && pg_num_fields($this->result) != count($data)) {

            $names = array();
            for ($i=0; $i<pg_num_fields($this->result); $i++) {
                $names[pg_field_name ( $this->result, $i )]++;
            }
            $names = array_filter($names, function($v){return $v > 1;});
            $dublications = "";
            foreach ($names as $key => $value) {
                $dublications .= "[<b>{$key}</b>] => <b style='color:crimson'>{$value}</b><br />";
            }

            throw new Exception(
                "Statement result has ".pg_num_fields($this->result)." columns while fetched row only ".count($data).". 
				Fetching it associative reduces number of columns. 
				Rename column with `AS` inside statement or fetch as indexed array.<br /><br />
				Dublicating columns are:<br /> {$dublications}<br />"
            );
        }


        $types = array();

        $map = array(
            'int'		=> 'integer',
            'int2'		=> 'integer',
            'int4'		=> 'integer',
            'int8'		=> 'integer',
            'serial4'	=> 'integer',
            'serial8'	=> 'integer',
            'smallint'	=> 'integer',
            'bigint'	=> 'integer',
            'bigserial'	=> 'integer',
            'serial'	=> 'integer',
            'numeric'	=> 'float',
            'decimal'	=> 'float',
            'real	'	=> 'float',
            'float'		=> 'float',
            'float4'	=> 'float',
            'float8'	=> 'float'
        );

        if ($type == 'row') {
            if ($data) {
                // count how many fields we have and get their types
                for ($i=0; $i < pg_num_fields($this->result); $i++) {
                    $types[] = pg_field_type($this->result, $i);
                }

                // Identify on which column we are
                $i = 0;
                //       row       idx      value
                foreach ($data as $key => $value) {
                    // if type of current column exist in map array
                    if ( array_key_exists($types[$i], $map) ) {
                        // using data key, cause can be
                        $data[$key] = ($types[$i] == 'integer' ? intval($value) : floatval($value));
                    }
                    $i++;
                }
            }
        }

        return $data;
    }

    public function update( $table, $values, $where="", $return = null )
    {
        $db = $this->compile_update( $values );

        $numargs = func_num_args();
        if ($numargs > 3) {
            $args = func_get_args();
            for ($i = 3; $i < $numargs; $i++) {
                $db['ARGS'][] = $args[$i];
            }
        }

        return $this->du("UPDATE $table SET {$db['COLUMNS']}" . ($where?" WHERE $where":"") . ($return ? " RETURNING {$return}":""), $db['ARGS'] );
    }

    public function insert( $table, $args, $return = null )
    {
        $db = $this->compile_insert( $args );

        $sth = $this->prepare("INSERT INTO $table ({$db['COLUMNS']}) VALUES ({$db['VALUES']})" . ($return ? " RETURNING {$return}":"") );
        $sth->execute($db['ARGS']);

        return $sth;
    }

    public function delete( $table, $where="", $return = null )
    {
        return $this->du("DELETE FROM $table" . ($where?" WHERE $where":"") . ($return ? " RETURNING {$return}":""));
    }

    private function quote($arg)
    {
        if ( !isset($arg) or $arg == NULL) {
            return "NULL";
        }
        $str = pg_escape_string($arg);
        return "'$str'";
    }
}