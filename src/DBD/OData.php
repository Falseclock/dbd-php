<?php
/**
 * OData
 *
 * @author       Nurlan Mukhanov <nurike@gmail.com>
 * @copyright    2020 Nurlan Mukhanov
 * @license      https://en.wikipedia.org/wiki/MIT_License MIT License
 * @link         https://github.com/Falseclock/dbd-php
 * @noinspection PhpComposerExtensionStubsInspection
 */

declare(strict_types=1);

namespace DBD;

use DBD\Base\Bind;
use DBD\Base\Helper;
use DBD\Common\DBDException;
use DBD\Common\DBDException as Exception;
use DBD\Entity\Common\EntityException;
use DBD\Entity\Entity;
use LSS\XML2Array;

class OData extends DBD
{
    protected $body = null;
    protected $dataKey = null;
    protected $header = null;
    protected $httpCode = null;
    protected $metadata = null;
    protected $replacements = null;
    protected $requestUrl = null;

    /**
     * @param Entity $entity
     * @return Entity
     * @throws Exception
     * @inheritDoc
     */
    public function entityInsert(Entity &$entity): Entity
    {
        try {
            $record = $this->createInsertRecord($entity);

            $sth = $this->insertCustom($entity::TABLE, $record, "*");

            /** @var Entity $class */
            $class = get_class($entity);

            $entity = new $class($sth->fetchRow());

            return $entity;

        } catch (DBDException | EntityException $e) {
            if ($e instanceof DBDException)
                throw $e;
            else
                throw new DBDException($e->getMessage(), null, null, $e);
        }
    }

    /**
     * @param string $table
     * @param array $args
     *
     * @return DBD|mixed
     */
    public function insertCustom(string $table, array $args): array
    {
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

        $this->setupRequest($this->Config->getHost() . $table . '?$format=application/json;odata=nometadata&', "POST", json_encode($args, JSON_UNESCAPED_UNICODE));
        $this->_connect();

        return json_decode($this->body, true);
    }

    protected function dropVars()
    {
        $this->CacheHolder = [
            'key' => null,
            'result' => null,
            'compress' => null,
            'expire' => null,
        ];

        $this->query = null;
        $this->replacements = null;
        $this->result = null;
        $this->requestUrl = null;
        $this->httpCode = null;
        $this->header = null;
        $this->body = null;
    }

    protected function setupRequest($url, $method = "GET", $content = null)
    {
        if (!is_resource($this->resourceLink)) {
            $this->resourceLink = curl_init();
        }
        curl_setopt($this->resourceLink, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        curl_setopt($this->resourceLink, CURLOPT_URL, $this->urlEncode($url));
        curl_setopt($this->resourceLink, CURLOPT_USERAGENT, __CLASS__);
        curl_setopt($this->resourceLink, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->resourceLink, CURLOPT_HEADER, 1);

        if ($this->Config->getUsername() && $this->Config->getPassword()) {
            curl_setopt($this->resourceLink, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($this->resourceLink, CURLOPT_USERPWD, $this->Config->getUsername() . ":" . $this->Config->getPassword());
        }
        switch ($method) {
            case "POST":
                curl_setopt($this->resourceLink, CURLOPT_POST, true);
                curl_setopt($this->resourceLink, CURLOPT_HTTPGET, false);
                curl_setopt($this->resourceLink, CURLOPT_CUSTOMREQUEST, null);
                break;
            case "PATCH":
                curl_setopt($this->resourceLink, CURLOPT_POST, false);
                curl_setopt($this->resourceLink, CURLOPT_HTTPGET, false);
                curl_setopt($this->resourceLink, CURLOPT_CUSTOMREQUEST, 'PATCH');
                break;
            case "PUT":
                curl_setopt($this->resourceLink, CURLOPT_POST, false);
                curl_setopt($this->resourceLink, CURLOPT_HTTPGET, false);
                curl_setopt($this->resourceLink, CURLOPT_CUSTOMREQUEST, 'PUT');
                break;
            case "DELETE":
                curl_setopt($this->resourceLink, CURLOPT_POST, false);
                curl_setopt($this->resourceLink, CURLOPT_HTTPGET, false);
                curl_setopt($this->resourceLink, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;
            case "GET":
            default:
                curl_setopt($this->resourceLink, CURLOPT_POST, false);
                curl_setopt($this->resourceLink, CURLOPT_HTTPGET, true);
                curl_setopt($this->resourceLink, CURLOPT_CUSTOMREQUEST, null);
                break;
        }
        if ($content) {
            curl_setopt($this->resourceLink, CURLOPT_POSTFIELDS, $content);
        }

        return $this;
    }

        protected function urlEncode($string)
    {
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

        protected function _connect(): void
    {
        // if we never invoke connect and did not setup it, just call setup with DSN url
        if (!is_resource($this->resourceLink)) {
            $this->setupRequest($this->Config->getHost());
        }
        // TODO: read keep-alive header and reset handler if not exist
        $response = curl_exec($this->resourceLink);
        $header_size = curl_getinfo($this->resourceLink, CURLINFO_HEADER_SIZE);
        $this->header = trim(substr($response, 0, $header_size));
        $this->body = preg_replace("/\xEF\xBB\xBF/", "", substr($response, $header_size));
        $this->httpCode = curl_getinfo($this->resourceLink, CURLINFO_HTTP_CODE);

        if ($this->httpCode >= 200 && $this->httpCode < 300) {
            // do nothing
        } else {
            $this->parseError();
        }
    } // TODO:

    protected function parseError()
    {
        $fail = $this->urlDecode(curl_getinfo($this->resourceLink, CURLINFO_EFFECTIVE_URL));
        if ($this->body) {
            $error = json_decode($this->body, true);
            if ($error && isset($error['odata.error']['message']['value'])) {
                throw new Exception("URL: {$fail}\n" . $error['odata.error']['message']['value'], $this->query);
            } else {
                $this->body = str_replace([
                    "\\r\\n",
                    "\\n",
                    "\\r",
                ],
                    "\n",
                    $this->body
                );
                throw new Exception("HEADER: {$this->header}\nURL: {$fail}\nBODY: {$this->body}\n", $this->query);
            }
        } else {
            throw new Exception("HTTP STATUS: {$this->httpCode}\n" . strtok($this->header, "\n"), $this->query);
        }
    }

    /*--------------------------------------------------------------*/

    protected function urlDecode($string)
    {
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

    /*--------------------------------------------------------------*/

    public function begin(): bool
    {
        throw new Exception("BEGIN not supported by OData");
    }

    public function commit(): bool
    {
        throw new Exception("COMMIT not supported by OData");
    }

    /*--------------------------------------------------------------*/

    /**
     * We do not need to connect anywhere until something real should be get via HTTP request, otherwise we will
     * consume resources for nothing
     *
     * @return $this|DBD
     */
    public function connect(): DBD
    {
        return $this;
    }

    /*--------------------------------------------------------------*/

/**
     * @return $this
     */
    public function disconnect(): DBD
    {
        if ($this->isConnected()) {
            curl_close($this->resourceLink);
        }

        return $this;
    }

    /*--------------------------------------------------------------*/

public function du()
    {
        return $this;
    }

    /*--------------------------------------------------------------*/

    /**
     * @return DBD
     * @throws Exception
     */
    public function execute(): DBD
    {

        $this->tryGetFromCache();

        // If not found in cache or we dont use it, then let's get via HTTP request
        if ($this->result === null) {

            $this->prepareUrl(func_get_args());

            // just initicate connect with prepared URL and HEADERS
            $this->setupRequest($this->Config->getHost() . $this->requestUrl);
            // and make request
            $this->_connect();

            // Will return NULL in case of failure
            $json = json_decode($this->body, true);

            if ($this->dataKey) {
                if ($json[$this->dataKey]) {
                    $this->result = $this->doReplacements($json[$this->dataKey]);
                } else {
                    $this->result = $json;
                }
            } else {
                $this->result = $this->doReplacements($json);
            }

            $this->storeResultToCache();
        }
        $this->query = null;

        return $this;
    }

    /*--------------------------------------------------------------*/

    protected function tryGetFromCache()
    {
        // If we have cache driver
        if (isset($this->Config->CacheDriver)) {
            // we set cache via $sth->cache('blabla');
            if ($this->CacheHolder['key'] !== null) {
                // getting result
                $this->CacheHolder['result'] = $this->Config->CacheDriver->get($this->CacheHolder['key']);

                // Cache not empty?
                if ($this->CacheHolder['result'] && $this->CacheHolder['result'] !== false) {
                    // set to our class var and count rows
                    $this->result = $this->CacheHolder['result'];
                }
            }
        }

        return $this;
    }

    /*--------------------------------------------------------------*/

    protected function prepareUrl($ARGS)
    {
        // Check and prepare args
        $binds = substr_count($this->query, $this->Options->getPlaceHolder());
        $args = Helper::parseArgs($ARGS);
        $numargs = count($args);

        if ($binds != $numargs) {
            throw new Exception("Query failed: called with $numargs bind variables when $binds are needed", $this->query);
        }

        // Make url and put arguments
        //return $this->buildUrlFromQuery($this->query,$args);
        //protected function buildUrlFromQuery($query,$args)

        // Replace placeholders with values
        if (count($args)) {
            $request = str_split($this->query);

            foreach ($request as $ind => $str) {
                if ($str == $this->Options->getPlaceHolder()) {
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
        $pieces = preg_split('/(?=(SELECT|FROM|WHERE|ORDER BY|LIMIT|EXPAND|JOIN).+?)/u', $query);
        $struct = [];

        foreach ($pieces as $piece) {
            preg_match('/(SELECT|FROM|WHERE|ORDER BY|LIMIT|EXPAND|JOIN)(.+)/u', $piece, $matches);
            if (count($matches)) {
                $rule = strtoupper(trim($matches[1]));
                if ($rule == 'JOIN')
                    $struct[$rule][] = trim($matches[2]);
                else
                    $struct[$rule] = trim($matches[2]);
            }
        }

        // Start URL build
        $this->requestUrl = "{$struct['FROM']}?\$format=application/json;odata=nometadata&";

        // Let's identify we want to select some columns with diff names
        $fields = explode(",", $struct['SELECT']);

        if (count($fields) && $fields[0] != '*') {
            $this->replacements = [];

            foreach ($fields as &$field) {
                $keywords = preg_split("/AS/i", $field);
                if (isset($keywords[1])) {
                    $this->replacements[trim($keywords[0])] = trim($keywords[1]);
                    $field = trim($keywords[0]);
                }
                $field = trim($field);
            }
            $this->requestUrl .= '$select=' . implode(",", $fields) . '&';
        }

        if (isset($struct['EXPAND'])) {
            $this->requestUrl .= '$expand=' . $struct['EXPAND'] . '&';
        }

        if (isset($struct['JOIN'])) {
            $expands = [];
            foreach ($struct['JOIN'] as $expand) {
                preg_match('/(.+?)\s/', $expand, $matches);
                $expands[] = $matches[1];
            }
            $this->requestUrl .= '$expand=' . implode(',', $expands) . '&';
        }

        if (isset($struct['WHERE'])) {

            $where = $struct['WHERE'];
            $paramId = 1;
            $params = [];
            preg_replace_callback("('.+?')",
                function ($matches) use (&$where, &$paramId, &$params) {
                    foreach ($matches as $match) {
                        $params[sprintf(":param%d", $paramId)] = $match;
                        $pos = strpos($where, $match);
                        if ($pos !== false) {
                            $where = substr_replace($where, sprintf(":param%d", $paramId), $pos, strlen($match));
                        }
                        $paramId++;
                    }
                },
                $where
            );

            $where = str_replace(['=', '<>', '>=', '<=', '>', '<'], ['eq', 'ne', 'ge', 'le', 'gt', 'lt'], $where);
            $where = str_replace(array_keys($params), array_values($params), $where);

            $this->requestUrl .= '$filter=' . $where . '&';
        }

        if (isset($struct['ORDER BY'])) {

            $struct['ORDER BY'] = implode(",",
                array_map(function ($order) {
                    return preg_replace_callback("/(\s+(asc|desc)\s*)$/i",
                        function ($matches) {
                            return strtolower($matches[1]);
                        },
                        $order
                    );
                },
                    explode(",", $struct['ORDER BY'])
                )
            );

            $this->requestUrl .= '$orderby=' . $struct['ORDER BY'] . '&';
        }

        if (isset($struct['LIMIT'])) {
            $this->requestUrl .= '$top=' . $struct['LIMIT'] . '&';
        }

        return $this;
    }

    /*--------------------------------------------------------------*/

    protected function doReplacements($data)
    {
        if (isset($this->replacements) && count($this->replacements) && $data != null) {
            foreach ($data as &$value) {
                foreach ($value as $key => $val) {
                    if (array_key_exists($key, $this->replacements)) {
                        $value[$this->replacements[$key]] = $val;
                        unset($value[$key]);
                    }
                }
            }
        }

        return $data;
    }

    /*--------------------------------------------------------------*/

    protected function storeResultToCache()
    {
        if ($this->result) {
            // If we want to store to the cache
            if ($this->CacheHolder['key'] !== null) {
                // Setting up our cache
                $this->Config->CacheDriver->set($this->CacheHolder['key'], $this->result, $this->CacheHolder['expire']);
            }
        }

        return $this;
    }

    /*--------------------------------------------------------------*/

    /**
     * @see https://github.com/Falseclock/dbd-php/issues/19 FIXME: see below
     * @return bool|mixed|null
     * @throws Exception
     */
    public function fetch()
    {
        if (is_iterable($this->result) and count($this->result) == 1 and !isset($this->result['value'])) { // FIXME : !isset($this->result['value']
            if (!count($this->result[0]))
                return null;

            return array_shift($this->result[0]);
        } else if (is_iterable($this->result) and count($this->result) > 1) {
            throw new Exception("Do not know how to fetch results if number of rows more then 1");
        } else {
            return null;
            // FIXME поставить как исправится баг
            //throw new Exception("Nothing to fetch");
        }
    }

    /*--------------------------------------------------------------*/

    public function fetchRowSet($uniqueKey = null): array
    {

        $array = [];
        while ($row = $this->fetchRow()) {
            if ($uniqueKey) {
                $array[$row[$uniqueKey]] = $row;
            } else {
                $array[] = $row;
            }
        }

        return $array;
    }

    /*--------------------------------------------------------------*/

    public function fetchRow()
    {
        return array_shift($this->result);
    }

    /*--------------------------------------------------------------*/

    public function metadata($key = null, $expire = null)
    {
        // If we already got metadata
        if ($this->metadata) {
            if ($key)
                return $this->metadata[$key];
            else
                return $this->metadata;
        }

        // Let's get from cache
        if (isset($this->Config->CacheDriver)) {
            $metadata = $this->Config->CacheDriver->get(__CLASS__ . ':metadata');
            if ($metadata && $metadata !== false) {
                $this->metadata = $metadata;
                if ($key)
                    return $this->metadata[$key];
                else
                    return $this->metadata;
            }
        }
        $this->dropVars();

        $this->setupRequest($this->Config->getHost() . '$metadata');
        $this->_connect();

        $array = XML2Array::createArray($this->body);

        $metadata = [];

        foreach ($array['edmx:Edmx']['edmx:DataServices']['Schema']['EntityType'] as $EntityType) {

            $object = [];

            foreach ($EntityType['Property'] as $Property) {
                if (preg_match('/Collection\(StandardODATA\.(.+)\)/', $Property['@attributes']['Type'], $matches)) {

                    $object[$Property['@attributes']['Name']] = [];

                    $ComplexType = $this->findComplexTypeByName($array, $matches[1]);
                    foreach ($ComplexType['Property'] as $prop) {
                        $object[$Property['@attributes']['Name']][0][$prop['@attributes']['Name']] = [
                            'Type' => $prop['@attributes']['Type'],
                            'Nullable' => $prop['@attributes']['Nullable'],
                        ];
                    }
                } else {
                    $object[$Property['@attributes']['Name']] = [
                        'Type' => $Property['@attributes']['Type'],
                        'Nullable' => $Property['@attributes']['Nullable'],
                    ];
                }
            }
            $metadata[$EntityType['@attributes']['Name']] = $object;
        }

        if (isset($this->Config->CacheDriver)) {
            $this->Config->CacheDriver->set(__CLASS__ . ':metadata', $metadata, $expire ? $expire : $this->CacheHolder['expire']);
        }
        $this->metadata = $metadata;

        if ($key)
            return $this->metadata[$key];
        else
            return $this->metadata;
    }

    /*--------------------------------------------------------------*/

    protected function findComplexTypeByName($array, $name)
    {
        foreach ($array['edmx:Edmx']['edmx:DataServices']['Schema']['ComplexType'] as $ComplexType) {
            if ($ComplexType['@attributes']['Name'] == $name) {
                return $ComplexType;
            }
        }

        return null;
    }

    /*--------------------------------------------------------------*/

    public function prepare($statement): DBD
    {

        // This is not SQL driver, so we can't make several instances with prepare
        // and let's allow only one by one requests per driver
        if ($this->query) {
            throw new Exception("You have an unexecuted query", $this->query);
        }
        // Drop current protected vars to do not mix up
        $this->dropVars();

        // Just storing query. Parse will be done later during buildQuery
        $this->query = $statement;

        return $this;
    }

    /*--------------------------------------------------------------*/

    public function query(): DBD
    {
        return $this;
    }

    /*--------------------------------------------------------------*/

    public function rollback(): bool
    {
        trigger_error("ROLLBACK not supported by OData");

        return false;
    }

    public function rows(): int
    {
        //if(is_iterable($this->result) and count($this->result) == 1 and !isset($this->result['value'])) // FIXME: исправить в выборке
        if (is_iterable($this->result))
            return count($this->result);

        return 0;
    }

    public function setDataKey($dataKey)
    {
        $this->dataKey = $dataKey;

        return $this;
    }

    public function update(): DBD
    {
        $binds = 0;
        $where = null;
        $return = null;
        $ARGS = func_get_args();
        $table = $ARGS[0];
        $values = $ARGS[1];
        $args = [];

        if (func_num_args() > 2) {
            $where = $ARGS[2];
            $binds = substr_count($where, $this->Options->getPlaceHolder());
        }
        // If we set $where with placeholders or we set $return
        if (func_num_args() > 3) {
            for ($i = 3; $i < $binds + 3; $i++) {
                $args[] = $ARGS[$i];
            }
            //if(func_num_args() > $binds + 3) {
            // FIXME: закоментарил, потому что варнило
            //$return = $ARGS[ func_num_args() - 1 ];
            //}
        }

        $url = $table . ($where ? $where : "");

        if (count($args)) {
            $request = str_split($url);

            foreach ($request as $ind => $str) {
                if ($str == $this->Options->getPlaceHolder()) {
                    $request[$ind] = "'" . array_shift($args) . "'";
                }
            }
            $url = implode("", $request);
        }

        $this->setupRequest($this->Config->getHost() . $url . '?$format=application/json;odata=nometadata&', "PATCH", json_encode($values, JSON_UNESCAPED_UNICODE));
        $this->_connect();

        return json_decode($this->body, true);
    }

    protected function _begin(): bool
    {
        // TODO: Implement _begin() method.
    }

    /**
     * @param string|null $binaryString
     *
     * @return string|null
     */
    protected function _escapeBinary(?string $binaryString): ?string
    {
        // TODO: Implement _binaryEscape() method.
    }

    protected function _commit(): bool
    {
        // TODO: Implement _commit() method.
    }

    protected function _compileInsert(string $table, array $params, string $return = ""): string
    {
        // TODO: Implement _compileInsert() method.
    }

    protected function _compileUpdate(string $table, array $params, string $where, ?string $return = ""): string
    {
        // TODO: Implement _compileUpdate() method.
    }

    protected function _convertTypes(&$data): void
    {
        // TODO: Implement _convertTypes() method.
    }

    protected function _disconnect(): bool
    {
        // TODO: Implement _disconnect() method.
    }

    /**
     * @inheritDoc
     */
    protected function _dump(string $preparedQuery, string $fileName, string $delimiter, string $nullString, bool $showHeader, string $tmpPath): string
    {
        // TODO: Implement _dump() method.
    }

    protected function _errorMessage(): string
    {
        // TODO: Implement _errorMessage() method.
    }

    protected function _escape($string): string
    {
        // TODO: Implement _escape() method.
    }

    /**
     * @param $uniqueName
     * @param $arguments
     *
     * @return mixed
     * @see MSSQL::_execute
     * @see MySQL::_execute
     * @see OData::_execute
     * @see Pg::_execute
     */
    protected function _execute($uniqueName, $arguments)
    {
        // TODO: Implement _execute() method.
    }

    protected function _fetchArray(): array
    {
        // TODO: Implement _fetchArray() method.
    }

    protected function _fetchAssoc()
    {
        // TODO: Implement _fetchAssoc() method.
    }

    /**
     * @param $uniqueName
     * @param $statement
     *
     * @return mixed
     * @see MSSQL::_prepare
     * @see MySQL::_prepare
     * @see OData::_prepare
     * @see Pg::_prepare
     */
    protected function _prepare(string $uniqueName, string $statement): bool
    {
        // TODO: Implement _prepare() method.
    }

    protected function _query($statement)
    {
        // TODO: Implement _query() method.
    }

    protected function _rollback(): bool
    {
        // TODO: Implement _rollback() method.
    }

    protected function _rows(): int
    {
        // TODO: Implement _affectedRows() method.
    }

    protected function doConnection()
    {
    }

    protected function replaceBind(string &$preparedQuery, Bind $bind): void
    {
        // TODO: Implement replaceBind() method.
    }
}
