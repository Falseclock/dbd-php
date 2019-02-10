<?php
/************************************************************************************
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

use DBD\Base\DBDPHPException as Exception;
use LSS\XML2Array;

class OData extends DBD
{
    protected $body         = null;
    protected $dataKey      = null;
    protected $header       = null;
    protected $httpcode     = null;
    protected $metadata     = null;
    protected $replacements = null;
    protected $requestUrl   = null;

    public function metadata($key = null, $expire = null) {
        // If we already got metadata
        if($this->metadata) {
            if($key)
                return $this->metadata[$key];
            else
                return $this->metadata;
        }

        // Let's get from cache
        if(isset($this->CacheDriver)) {
            $metadata = $this->CacheDriver->get(__CLASS__ . ':metadata');
            if($metadata && $metadata !== false) {
                $this->metadata = $metadata;
                if($key)
                    return $this->metadata[$key];
                else
                    return $this->metadata;
            }
        }
        $this->dropVars();

        $this->setupCurl($this->Config->getDsn() . '$metadata');
        $this->connect();

        $array = XML2Array::createArray($this->body);

        $metadata = [];

        foreach($array['edmx:Edmx']['edmx:DataServices']['Schema']['EntityType'] as $EntityType) {

            $object = [];

            foreach($EntityType['Property'] as $Property) {
                if(preg_match('/Collection\(StandardODATA\.(.+)\)/', $Property['@attributes']['Type'], $matches)) {

                    $object[$Property['@attributes']['Name']] = [];

                    $ComplexType = $this->findComplexTypeByName($array, $matches[1]);
                    foreach($ComplexType['Property'] as $prop) {
                        $object[$Property['@attributes']['Name']][0][$prop['@attributes']['Name']] = [
                            'Type'     => $prop['@attributes']['Type'],
                            'Nullable' => $prop['@attributes']['Nullable'],
                        ];
                    }
                }
                else {
                    $object[$Property['@attributes']['Name']] = [
                        'Type'     => $Property['@attributes']['Type'],
                        'Nullable' => $Property['@attributes']['Nullable'],
                    ];;
                }
            }
            $metadata[$EntityType['@attributes']['Name']] = $object;
        }

        if(isset($this->CacheDriver)) {
            $this->CacheDriver->set(__CLASS__ . ':metadata', $metadata, $expire ? $expire : $this->cache['expire']);
        }
        $this->metadata = $metadata;

        if($key)
            return $this->metadata[$key];
        else
            return $this->metadata;
    }

    protected function dropVars() {
        $this->cache = [
            'key'      => null,
            'result'   => null,
            'compress' => null,
            'expire'   => null,
        ];

        $this->query = null;
        $this->replacements = null;
        $this->result = null;
        $this->requestUrl = null;
        $this->httpcode = null;
        $this->header = null;
        $this->body = null;
    }

    protected function setupCurl($url, $method = "GET", $content = null) {
        if(!is_resource($this->dbResource)) {
            $this->dbResource = curl_init();
        }
        curl_setopt($this->dbResource, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        curl_setopt($this->dbResource, CURLOPT_URL, $this->urlEncode($url));
        curl_setopt($this->dbResource, CURLOPT_USERAGENT, __CLASS__);
        curl_setopt($this->dbResource, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->dbResource, CURLOPT_HEADER, 1);

        if($this->Config->getUsername() && $this->Config->getPassword()) {
            curl_setopt($this->dbResource, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($this->dbResource, CURLOPT_USERPWD, $this->Config->getUsername() . ":" . $this->Config->getPassword());
        }
        switch($method) {
            case "POST":
                curl_setopt($this->dbResource, CURLOPT_POST, true);
                curl_setopt($this->dbResource, CURLOPT_HTTPGET, false);
                curl_setopt($this->dbResource, CURLOPT_CUSTOMREQUEST, null);
                break;
            case "PATCH":
                curl_setopt($this->dbResource, CURLOPT_POST, false);
                curl_setopt($this->dbResource, CURLOPT_HTTPGET, false);
                curl_setopt($this->dbResource, CURLOPT_CUSTOMREQUEST, 'PATCH');
                break;
            case "PUT":
                curl_setopt($this->dbResource, CURLOPT_POST, false);
                curl_setopt($this->dbResource, CURLOPT_HTTPGET, false);
                curl_setopt($this->dbResource, CURLOPT_CUSTOMREQUEST, 'PUT');
                break;
            case "DELETE":
                curl_setopt($this->dbResource, CURLOPT_POST, false);
                curl_setopt($this->dbResource, CURLOPT_HTTPGET, false);
                curl_setopt($this->dbResource, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;
            case "GET":
            default:
                curl_setopt($this->dbResource, CURLOPT_POST, false);
                curl_setopt($this->dbResource, CURLOPT_HTTPGET, true);
                curl_setopt($this->dbResource, CURLOPT_CUSTOMREQUEST, null);
                break;
        }
        if($content) {
            curl_setopt($this->dbResource, CURLOPT_POSTFIELDS, $content);
        }

        return $this;
    }

    protected function findComplexTypeByName($array, $name) {
        foreach($array['edmx:Edmx']['edmx:DataServices']['Schema']['ComplexType'] as $ComplexType) {
            if($ComplexType['@attributes']['Name'] == $name) {
                return $ComplexType;
            }
        }

        return null;
    } // TODO:

    protected function urlEncode($string) {
        $entities = [
            '%20',
            '%27',
        ];
        $replacements = [
            ' ',
            "'",
        ];
        $string = str_replace($replacements, $entities, $string);

        return $string;
    } // TODO:

    public function setDataKey($dataKey) {
        $this->dataKey = $dataKey;

        return $this;
    } // TODO:

    /*--------------------------------------------------------------*/

    protected function _affectedRows() {
        // TODO: Implement _affectedRows() method.
    }

    /*--------------------------------------------------------------*/

    protected function _begin() {
        // TODO: Implement _begin() method.
    }

    protected function _commit() {
        // TODO: Implement _commit() method.
    }

    /*--------------------------------------------------------------*/

    protected function _compileInsert($table, $params, $return = "") {
        // TODO: Implement _compileInsert() method.
    }

    /*--------------------------------------------------------------*/

    protected function _compileUpdate($table, $params, $where, $return = "") {
        // TODO: Implement _compileUpdate() method.
    }

    /*--------------------------------------------------------------*/

    protected function _connect() {
        // TODO: Implement _connect() method.
    }

    /*--------------------------------------------------------------*/

    protected function _convertBoolean(&$data, $type) {
    }

    /*--------------------------------------------------------------*/

    protected function _convertIntFloat(&$data, $type) {
    }

    /*--------------------------------------------------------------*/

    protected function _disconnect() {
        // TODO: Implement _disconnect() method.
    }

    /*--------------------------------------------------------------*/

    protected function _errorMessage() {
        // TODO: Implement _errorMessage() method.
    }

    /*--------------------------------------------------------------*/

    protected function _escape($string) {
        // TODO: Implement _escape() method.
    }

    /*--------------------------------------------------------------*/

    protected function _fetchArray() {
        // TODO: Implement _fetchArray() method.
    }

    /*--------------------------------------------------------------*/

    protected function _fetchAssoc() {
        // TODO: Implement _fetchAssoc() method.
    }

    /*--------------------------------------------------------------*/

    protected function _numRows() {
        // TODO: Implement _numRows() method.
    }

    /*--------------------------------------------------------------*/

    protected function _query($statement) {
        // TODO: Implement _query() method.
    }

    /*--------------------------------------------------------------*/

    protected function _queryExplain($statement) {
        // TODO: Implement _queryExplain() method.
    }

    /*--------------------------------------------------------------*/

    protected function _rollback() {
        // TODO: Implement _rollback() method.
    }

    /*--------------------------------------------------------------*/

    public function begin() {
        throw new Exception("BEGIN not supported by OData");
    }

    /*--------------------------------------------------------------*/

    public function commit() {
        throw new Exception("COMMIT not supported by OData");
    }

    /*--------------------------------------------------------------*/

    public function connect() {
        // if we never invoke connect and did not setup it, just call setup with DSN url
        if(!is_resource($this->dbResource)) {
            $this->setupCurl($this->Config->getDsn());
        }
        // TODO: read keep-alive header and reset handler if not exist
        $response = curl_exec($this->dbResource);
        $header_size = curl_getinfo($this->dbResource, CURLINFO_HEADER_SIZE);
        $this->header = trim(substr($response, 0, $header_size));
        $this->body = preg_replace("/\xEF\xBB\xBF/", "", substr($response, $header_size));
        $this->httpcode = curl_getinfo($this->dbResource, CURLINFO_HTTP_CODE);

        if($this->httpcode >= 200 && $this->httpcode < 300) {
            // do nothing
        }
        else {
            $this->parseError();
        }

        return new OdataExtend($this);
    }

    /*--------------------------------------------------------------*/

    /**
     * @return $this
     */
    public function disconnect() {
        if($this->isConnected()) {
            curl_close($this->dbResource);
        }

        return $this;
    }

    /*--------------------------------------------------------------*/

    public function du() {
        return $this;
    }

    public function execute() {

        $this->tryGetFromCache();

        // If not found in cache or we dont use it, then let's get via HTTP request
        if($this->result === null) {

            $this->prepareUrl(func_get_args());

            // just initicate connect with prepared URL and HEADERS
            $this->setupCurl($this->Config->getDsn() . $this->requestUrl);
            // and make request
            $this->connect();

            // Will return NULL in case of failure
            $json = json_decode($this->body, true);

            if($this->dataKey) {
                if($json[$this->dataKey]) {
                    $this->result = $this->doReplacements($json[$this->dataKey]);
                }
                else {
                    $this->result = $json;
                }
            }
            else {
                $this->result = $this->doReplacements($json);
            }

            $this->storeResultToCache();
        }
        $this->query = null;

        return $this;
    }

    public function fetch() {
        return $this;
    }

    public function fetchrow() {
        return array_shift($this->result);
    }

    public function fetchrowset($key = null) {

        $array = [];
        while($row = $this->fetchrow()) {
            if($key) {
                $array[$row[$key]] = $row;
            }
            else {
                $array[] = $row;
            }
        }

        return $array;
    }

    public function insert($table, $content, $return = null) {
        $this->dropVars();

        /*
        $insert = $this->metadata($table);

        foreach ($insert as $key => &$option) {
            // if we have defined such field
            if (isset($data[$key])) {
                // check options
                if (array_keys($option) !== range(0, count($option) - 1)) { // associative
                    // TODO: check value type
                    $option = $data[$key];
                } else {
                    $option = array();
                    $i = 1;
                    foreach ($data[$key] as $row) {
                        // TODO: check value type
                        $option[] = $row;
                        $i++;
                    }
                }
            } else {
                if (array_keys($option) !== range(0, count($option) - 1)) { // associative
                    if ($option['Nullable']) {
                        $option = null;
                    } else {
                        throw new Exception("$key can't be null");
                    }
                } else {
                    $option = array();
                }
            }
        }
        */

        $this->setupCurl($this->Config->getDsn() . $table . '?$format=application/json;odata=nometadata&', "POST", json_encode($content, JSON_UNESCAPED_UNICODE));
        $this->connect();

        return json_decode($this->body, true);
    }

    public function prepare($statement) {

        // This is not SQL driver, so we can't make several instances with prepare
        // and let's allow only one by one requests per driver
        if($this->query) {
            throw new Exception("You have an unexecuted query", $this->query);
        }
        // Drop current protected vars to do not mix up
        $this->dropVars();

        // Just storing query. Parse will be done later during buildQuery
        $this->query = $statement;

        return $this;
    }

    public function query() {
        return $this;
    }

    public function rollback() {
        throw new Exception("ROLLBACK not supported by OData");
    }

    public function rows() {
        return count($this->result);
    }

    public function update() {
        $binds = 0;
        $where = null;
        $return = null;
        $ARGS = func_get_args();
        $table = $ARGS[0];
        $values = $ARGS[1];
        $args = [];

        if(func_num_args() > 2) {
            $where = $ARGS[2];
            $binds = substr_count($where, "?");
        }
        // If we set $where with placeholders or we set $return
        if(func_num_args() > 3) {
            for($i = 3; $i < $binds + 3; $i++) {
                $args[] = $ARGS[$i];
            }
            //if(func_num_args() > $binds + 3) {
            // FIXME: закоментарил, потому что варнило
            //$return = $ARGS[ func_num_args() - 1 ];
            //}
        }

        $url = $table . ($where ? $where : "");

        if(count($args)) {
            $request = str_split($url);

            foreach($request as $ind => $str) {
                if($str == '?') {
                    $request[$ind] = "'" . array_shift($args) . "'";
                }
            }
            $url = implode("", $request);
        }

        $this->setupCurl($this->Config->getDsn() . $url . '?$format=application/json;odata=nometadata&', "PATCH", json_encode($values, JSON_UNESCAPED_UNICODE));
        $this->connect();

        return json_decode($this->body, true);
    }

    protected function tryGetFromCache() {
        // If we have cache driver
        if(isset($this->CacheDriver)) {
            // we set cache via $sth->cache('blabla');
            if($this->cache['key'] !== null) {
                // getting result
                $this->cache['result'] = $this->CacheDriver->get($this->cache['key']);

                // Cache not empty?
                if($this->cache['result'] && $this->cache['result'] !== false) {
                    // set to our class var and count rows
                    $this->result = $this->cache['result'];
                    $this->rows = count($this->cache['result']);
                }
            }
        }

        return $this;
    }

    protected function prepareUrl($ARGS) {
        // Check and prepare args
        $binds = substr_count($this->query, "?");
        $args = $this->parseArgs($ARGS);
        $numargs = count($args);

        if($binds != $numargs) {
            throw new Exception("Query failed: called with $numargs bind variables when $binds are needed");
        }

        // Make url and put arguments
        //return $this->buildUrlFromQuery($this->query,$args);
        //protected function buildUrlFromQuery($query,$args)

        // Replace placeholders with values
        if(count($args)) {
            $request = str_split($this->query);

            foreach($request as $ind => $str) {
                if($str == '?') {
                    $request[$ind] = "'" . array_shift($args) . "'";
                }
            }
            $this->query = implode("", $request);
        }

        // keep initial quert unchanged
        $query = $this->query;

        // make one string for REGEXP
        $query = preg_replace('/\t/', " ", $query);
        $query = preg_replace('/\r/', "", $query);
        $query = preg_replace('/\n/', " ", $query);
        $query = preg_replace('/\s+/', " ", $query);
        $query = trim($query);

        // split whole query by special words
        $pieces = preg_split('/(?=(SELECT|FROM|WHERE|ORDER BY|LIMIT|EXPAND).+?)/u', $query);
        $struct = [];

        foreach($pieces as $piece) {
            preg_match('/(SELECT|FROM|WHERE|ORDER BY|LIMIT|EXPAND)(.+)/u', $piece, $matches);
            if(count($matches)) {
                $struct[trim($matches[1])] = trim($matches[2]);
            }
        }

        // Start URL build
        $this->requestUrl = "{$struct['FROM']}?\$format=application/json;odata=nometadata&";

        // Let's identify we want to select some columns with diff names
        $fields = explode(",", $struct['SELECT']);

        if(count($fields) && $fields[0] != '*') {
            $this->replacements = [];

            foreach($fields as &$field) {
                $keywords = preg_split("/AS/i", $field);
                if(isset($keywords[1])) {
                    $this->replacements[trim($keywords[0])] = trim($keywords[1]);
                    $field = trim($keywords[0]);
                }
                $field = trim($field);
            }
            $this->requestUrl .= '$select=' . implode(",", $fields) . '&';
        }

        if($struct['EXPAND']) {
            $this->requestUrl .= '$expand=' . $struct['EXPAND'] . '&';
        }

        if($struct['WHERE']) {
            $this->requestUrl .= '$filter=' . $struct['WHERE'] . '&';
        }

        if($struct['ORDER BY']) {

            $struct['ORDER BY'] = implode(
                ",", array_map(
                function($order) {
                    return preg_replace_callback(
                        "/(\s+(asc|desc)\s*)$/i", function($matches) {
                        return strtolower($matches[1]);
                    }, $order);
                }, explode(",", $struct['ORDER BY'])));

            $this->requestUrl .= '$orderby=' . $struct['ORDER BY'] . '&';
        }

        if(isset($struct['LIMIT'])) {
            $this->requestUrl .= '$top=' . $struct['LIMIT'] . '&';
        }

        return $this;
    }

    protected function doReplacements($data) {
        if(count($this->replacements) && $data != null) {
            foreach($data as &$value) {
                foreach($value as $key => $val) {
                    if(array_key_exists($key, $this->replacements)) {
                        $value[$this->replacements[$key]] = $val;
                        unset($value[$key]);
                    }
                }
            }
        }

        return $data;
    }

    protected function storeResultToCache() {
        if($this->result) {
            $this->rows = count($this->result);
            // If we want to store to the cache
            if($this->cache['key'] !== null) {
                // Setting up our cache
                $this->CacheDriver->set($this->cache['key'], $this->result, $this->cache['expire']);
            }
        }

        return $this;
    }

    protected function _convertTypes(&$data, $type) {
        // TODO: Implement _convertTypes() method.
    }

    protected function doConnection() {
    }

    protected function parseError() {
        $fail = $this->urlDecode(curl_getinfo($this->dbResource, CURLINFO_EFFECTIVE_URL));
        if($this->body) {
            $error = json_decode($this->body, true);
            if($error && isset($error['odata.error']['message']['value'])) {
                throw new Exception("URL: {$fail}\n" . $error['odata.error']['message']['value'], $this->query);
            }
            else {
                $this->body = str_replace(
                    [
                        "\\r\\n",
                        "\\n",
                        "\\r",
                    ], "\n", $this->body);
                throw new Exception("HEADER: {$this->header}\nURL: {$fail}\nBODY: {$this->body}\n", $this->query);
            }
        }
        else {
            throw new Exception("HTTP STATUS: {$this->httpcode}\n" . strtok($this->header, "\n"), $this->query);
        }
    }

    protected function urlDecode($string) {
        $replacements = [
            '%20',
            '%27',
        ];
        $entities = [
            ' ',
            "'",
        ];
        $string = str_replace($replacements, $entities, $string);

        return $string;
    }
}

final class OdataExtend extends OData implements DBI
{
    public function __construct($object, $statement = "") {
        parent::extendMe($object, $statement);
    }
}