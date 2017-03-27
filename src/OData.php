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
use DBD\Base\ErrorHandler as ErrorHandler;
use Exception;

class OData extends DBD implements DBI {
	
	protected $replacements = null;
	protected $dataKey      = null;
	
	public function connect()
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->dsn);
		curl_setopt($ch, CURLOPT_USERAGENT, 'DBD\OData driver');
		curl_setopt($ch, CURLOPT_HTTPGET, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, 1);
		if ($this->username && $this->password) {
			curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
			curl_setopt($ch, CURLOPT_USERPWD, $this->username.":".$this->password);
		}
		curl_setopt($ch, CURLOPT_POST, false);
		
		$this->dbh = $ch;
		return $this;
		
		$response  = curl_exec($ch);
		$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
		$header = substr($response, 0, $header_size);
		$body = substr($response, $header_size);
		$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		
		if ($httpcode>=200 && $httpcode<300) {
			$this->dbh = $ch;
			return $this;
		} else {
			trigger_error($body, E_USER_ERROR);
		}
	}
	
	public function disconnect()
	{
		if ($this->isConnected()) {
			curl_close($this->dbh);
		}
		if (is_resource($this->options['CacheDriver'])) {
			$this->options['CacheDriver']->close();
		}
		
		return $this;
	}
	public function begin() {trigger_error("BEGIN not supported by OData", E_USER_ERROR);}
	public function commit() {trigger_error("COMMIT not supported by OData", E_USER_ERROR);}
	public function rollback() {trigger_error("ROLLBACK not supported by OData", E_USER_ERROR);}
	
	public function setDataKey($dataKey){
		$this->dataKey = $dataKey;
		return $this;
	}
	
	public function prepare($statement) {
		
		if (!$this->isConnected()) {
			$this->connect();
		}
		
		// This is not SQL driver, so we can't make several instances with prepare
		// and let's allow only one by one requests per driver
		if ($this->query) {
//			trigger_error("You have an unexecuted query: ".$this->query, E_USER_ERROR);
		}
		// Drop current proteced vars to do not mix up
		$this->dropVars();
		
		// Just storing query. Parse will be done later during buildQuery
		$this->query = $statement;
		
		return $this;
	}
	
	protected function tryGetFromCache()
	{
		// If we have cache driver
		if ($this->options['CacheDriver']) {
			// we set cache via $sth->cache('blabla');
			if ($this->cache['key'] !== null) {
				// getting result
				$this->cache['result'] = $this->options['CacheDriver']->get($this->cache['key']);
				
				// Cache not empty?
				if ($this->cache['result'] && $this->cache['result'] !== false) {
					if ($this->myDebug) {echo("Cache data\n");}
					// set to our class var and count rows
					$this->result = $this->cache['result'];
					$this->rows = count($this->cache['result']);
				}
			}
		}
		
		return $this;
	}
	
	protected function storeResultToache()
	{
		if ( $this->result )  {
			$this->rows = count($this->result);
			// If we want to store to the cache
			if ($this->cache['key'] !== null) {
				// Setting up our cache
				$this->options['CacheDriver']->set
				(
					$this->cache['key'],
					$this->result,
					$this->cache['expire']
				);
			}
		}
		return $this;
	}
	
	private function prepareUrl($ARGS) {
		// Check and prepare args
		$binds	= substr_count($this->query,"?");
		$args	= $this->parse_args($ARGS);
		$numargs = count($args);
		$query	= $this->query;
		
		if ($binds != $numargs) {
			$caller = $this->caller();
			trigger_error (
				"Query failed: called with 
				$numargs bind variables when $binds are needed at 
				{$caller[0]['file']} line {$caller[0]['line']}",
				E_USER_ERROR
			);
		}
		// Make url and put arguments
		return $this->buildUrlFromQuery($this->query,$args);
	}
	
	// TODO: если у нас ошибка 500 или еще какая, то ошибки не выводить там
	// а выводить через хендлер и возвращать null
	public function execute(){
		$this->result = null;
		
		$this->tryGetFromCache();
		
		// If not found in cache, then let's get via HTTP request
		if ($this->result === null) {

			$url = $this->prepareUrl(func_get_args());
			
			// Query and store to result
			if ($this->myDebug) {echo("HTTP request\n");}
			$this->result = $this->queryOData($url, $this->dsn, $this->dataKey);
			
			$this->storeResultToache();
		}
		
		if ( $this->result === null)  {
			new ErrorHandler ("error happen"); //FIXME:
		}
		
		return $this->result;
	}
	
	public function fetchrow() {
		return array_shift($this->result);
	}
	
	public function fetchrowset($key = null) {
		
		$array = array();
		while ($row = $this->fetchrow()) {
			if ($key) {
				$array[$row[$key]] = $row;
			} else {
				$array[] = $row;
			}
		}
		return $array;
	}
	
	public function rows() {
		return count( $this->result );
	}
	
	public function du() {}
	public function query() {}
	public function fetch() {}
	public function update() {}
	public function insert( $table, $values, $return = null) {}
	
	private function buildUrlFromQuery($query,$args) {
		
		if (count($args)) {
			$request = str_split($query);
			
			foreach($request as $ind => $str) {
				if ($str == '?') {
					$request[$ind] = "'".array_shift($args)."'";
				}
			}
			$query = implode("", $request);
		}
		
		$query = preg_replace('/\t/'," ",$query);
		$query = preg_replace('/\r/',"",$query);
		$query = preg_replace('/\n/'," ",$query);
		$query = preg_replace('/\s+/'," ",$query);
		$query = trim($query);
		
		$pieces = preg_split('/(?=(SELECT|FROM|WHERE|ORDER BY|LIMIT|EXPAND).+?)/',$query);
		$struct = array();
		
		foreach ($pieces as $piece) {
			preg_match('/(SELECT|FROM|WHERE|ORDER BY|LIMIT|EXPAND)(.+)/',$piece,$matches);
			$struct[trim($matches[1])] = trim($matches[2]);
		}
		
		$url = "{$struct['FROM']}?\$format=application/json;odata=nometadata&";
		
		$fields = explode(",", $struct['SELECT']);
		$this->replacements = array();
		
		foreach ($fields as &$field) {
			$keywords = preg_split("/AS/i", $field);
			if ($keywords[1]) {
				$this->replacements[trim($keywords[0])] = trim($keywords[1]);
				$field = trim($keywords[0]);
			}
			$field = trim($field);
		}
		
		$url .= '$select=' . implode(",", $fields) .'&';
		
		if ($struct['EXPAND']) {
			$url .= '$expand=' . $struct['EXPAND'] .'&';
		}
		
		if ($struct['WHERE']) {
			$url .= '$filter=' . $struct['WHERE'] .'&';
		}
		
		if ($struct['ORDER BY']) {
			$url .= '$orderby=' . $struct['ORDER BY'] .'&';
		}
		
		if ($struct['LIMIT']) {
			$url .= '$top=' . $struct['LIMIT'] .'&';
		}
		
		return $url;
	}
	
	protected function queryOData($query, $dsn, $key) {
		$url = $this->myUrlEncode($dsn.$query);
		
		curl_setopt($this->dbh, CURLOPT_URL, $url);
		
		$response  = curl_exec($this->dbh);
		
		$header_size = curl_getinfo($this->dbh, CURLINFO_HEADER_SIZE);
		$header = substr($response, 0, $header_size);
		$body = substr($response, $header_size);
		$httpcode = curl_getinfo($this->dbh, CURLINFO_HTTP_CODE);
		
		if ($httpcode>=200 && $httpcode<300) {
			$json = json_decode($body,true);
			if ($key) {
				return $this->doReplacements($json[$key]);
			} else {
				return $this->doReplacements($json);
			}
		} else {
			throw new Exception("Error code: {$httpcode}<br>\n$url".self::prettyPrint($body));
		}
	}

	private function doReplacements($data) {
		if (count($this->replacements)) {
			foreach ($data as &$value) {
				foreach ($value as $key => $val) {
					if (array_key_exists($key,$this->replacements)) {
						$value[$this->replacements[$key]] = $val;
						unset($value[$key]);
					}
				}
			}
		}
		return $data;
	}
	
	private function myUrlEncode($string) {
		$entities = array('%20', '%27');
		$replacements = array(' ', "'");
		$string =  str_replace($replacements, $entities, $string);
		
		return $string;
	}
	
	protected function dropVars()
	{
		$this->cache =
			[
			'key'				=> null,
			'result'			=> null,
			'compress'			=> null,
			'expire'			=> null,
			];

		$this->query = null;
		$this->replacements = null;
		$this->result = null;
	}
	
	private function prettyPrint( $json )
	{
		$result = "<br>\n";
		$level = 0;
		$in_quotes = false;
		$in_escape = false;
		$ends_line_level = NULL;
		$json_length = strlen( $json );
		
		for( $i = 0; $i < $json_length; $i++ ) {
			$char = $json[$i];
			$new_line_level = NULL;
			$post = "";
			if( $ends_line_level !== NULL ) {
				$new_line_level = $ends_line_level;
				$ends_line_level = NULL;
			}
			if ( $in_escape ) {
				$in_escape = false;
			} else if( $char === '"' ) {
				$in_quotes = !$in_quotes;
			} else if( ! $in_quotes ) {
				switch( $char ) {
					case '}': case ']':
						$level--;
						$ends_line_level = NULL;
						$new_line_level = $level;
						break;
						
					case '{': case '[':
						$level++;
					case ',':
						$ends_line_level = $level;
						break;
						
					case ':':
						$post = " ";
						break;
						
					case " ": case "\t": case "\n": case "\r":
						$char = "";
						$ends_line_level = $new_line_level;
						$new_line_level = NULL;
						break;
				}
			} else if ( $char === '\\' ) {
				$in_escape = true;
			}
			if( $new_line_level !== NULL ) {
				$result .= "<br />\n".str_repeat( "&nbsp; &nbsp; &nbsp;", $new_line_level );
			}
			$result .= $char.$post;
		}
		
		return $result;
	}
}