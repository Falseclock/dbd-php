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
use DBD\Entity\Common\EntityException;
use DBD\Entity\Entity;
use DBD\Utils\OData\Metadata;

class OData extends DBD
{
    protected $body = null;
    protected $dataKey = null;
    protected $header = null;
    protected $httpCode = null;
    protected $metadata = null;
    protected $replacements = null;
    protected $requestUrl = null;
    /** @var array */
    protected $result;
    /** @var int */
    protected $initialAffectedRows;

    /**
     * @param Entity $entity
     * @return Entity
     * @inheritDoc
     */
    public function entityInsert(Entity &$entity): Entity
    {
        try {
            $record = $this->createInsertRecord($entity);

            $embeddings = $entity::map()->getEmbedded();

            foreach ($embeddings as $propertyName => $embedded) {
                if (property_exists($entity, $propertyName)) {
                    if ($embedded->isIterable)
                        foreach ($entity->$propertyName as $row)
                            $record[$embedded->name][] = $this->createInsertRecord($row);
                    else
                        trigger_error("not implemented");
                }
            }

            /** @var Entity $class */
            $class = get_class($entity);

            $entity = new $class($this->insertCustom($entity::TABLE, $record));

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
     * @return array
     * @throws DBDException
     */
    public function insertCustom(string $table, array $args): array
    {

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

    /**
     * @param $url
     * @param string $method
     * @param null $content
     * @return $this
     */
    protected function setupRequest($url, $method = "GET", $content = null): self
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
    }

    /**
     * @throws DBDException
     */
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

    /**
     * @throws DBDException
     */
    protected function parseError()
    {
        $fail = $this->urlDecode(curl_getinfo($this->resourceLink, CURLINFO_EFFECTIVE_URL));
        if ($this->body) {
            $error = json_decode($this->body, true);
            if ($error && isset($error['odata.error']['message']['value'])) {
                throw new DBDException("URL: {$fail}\n" . $error['odata.error']['message']['value'], $this->query);
            } else {
                $this->body = str_replace([
                    "\\r\\n",
                    "\\n",
                    "\\r",
                ],
                    "\n",
                    $this->body
                );
                throw new DBDException("HEADER: {$this->header}\nURL: {$fail}\nBODY: {$this->body}\n", $this->query);
            }
        } else {
            throw new DBDException("HTTP STATUS: {$this->httpCode}\n" . strtok($this->header, "\n"), $this->query);
        }
    }

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

    /**
     * @return Metadata|null
     * @throws DBDException
     */
    public function metadata(): ?Metadata
    {
        // If we already got metadata
        if ($this->metadata)
            return $this->metadata;

        // Let's get from cache
        if (isset($this->Config->CacheDriver)) {
            $metadata = $this->Config->CacheDriver->get($this->Config->getHost() . ':metadata');
            if ($metadata !== false) {
                $this->metadata = $metadata;
                return $this->metadata;
            }
        }

        $this->setupRequest($this->Config->getHost() . '$metadata');
        $this->_connect();

        $xml = simplexml_load_string($this->body);
        $body = $xml->xpath("//edmx:Edmx/edmx:DataServices/*");
        $schema = json_decode(json_encode($body[0]));

        $this->metadata = new Metadata($schema);

        if (isset($this->Config->CacheDriver))
            $this->Config->CacheDriver->set($this->Config->getHost() . ':metadata', $this->metadata, $this->CacheHolder->expire);

        return $this->metadata;
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

        //return json_decode($this->body, true);
        return $this;
    }

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

    protected function findComplexTypeByName($array, $name)
    {
        foreach ($array['edmx:Edmx']['edmx:DataServices']['Schema']['ComplexType'] as $ComplexType) {
            if ($ComplexType['@attributes']['Name'] == $name) {
                return $ComplexType;
            }
        }

        return null;
    }

    /**
     * @return bool
     * @throws DBDException
     */
    protected function _begin(): bool
    {
        throw new DBDException("OData doesn't not support transactions");
    }

    /**
     * @param string|null $binaryString
     *
     * @return string|null
     */
    protected function _escapeBinary(?string $binaryString): ?string
    {

    }

    /**
     * @return bool
     * @throws DBDException
     */
    protected function _commit(): bool
    {
        throw new DBDException("OData doesn't not support transactions");
    }

    protected function _compileInsert(string $table, array $params, ?string $return = ""): string
    {

    }

    protected function _compileUpdate(string $table, array $params, string $where, ?string $return = ""): string
    {

    }

    protected function _convertTypes(&$data): void
    {

    }

    protected function _disconnect(): bool
    {

    }

    /**
     * @inheritDoc
     */
    protected function _dump(string $preparedQuery, string $fileName, string $delimiter, string $nullString, bool $showHeader, string $tmpPath): string
    {

    }

    protected function _errorMessage(): string
    {

    }

    protected function _escape($string): string
    {

    }

    /**
     * @param $uniqueName
     * @param $arguments
     *
     * @return mixed
     * @throws DBDException
     * @inheritDoc
     */
    protected function _execute($uniqueName, $arguments)
    {
        $this->prepareRequestUrl(func_get_args());

        // just initiate connect with prepared URL and HEADERS
        $this->setupRequest($this->Config->getHost() . $this->requestUrl);
        // and make request
        $this->_connect();

        // Will return NULL in case of failure
        return json_decode($this->body, true);

    }

    /**
     * @param $ARGS
     * @return $this
     * @throws DBDException
     */
    protected function prepareRequestUrl(array $ARGS = [])
    {
        // Check and prepare args
        $binds = substr_count($this->query, $this->Options->getPlaceHolder());
        $args = Helper::parseArgs($ARGS);
        $numargs = count($args);

        if ($binds != $numargs) {
            throw new DBDException("Query failed: called with $numargs bind variables when $binds are needed", $this->query);
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

            $where = str_replace(['<>', '>=', '<=', '>', '<', '='], ['ne', 'ge', 'le', 'gt', 'lt', 'eq'], $where);
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

    /**
     * @return array|boolean
     * @inheritDoc
     */
    protected function _fetchArray()
    {
        if (count($this->result['value']) > 0) {
            $row = array_shift($this->result['value']);
            $array = [];
            foreach ($row as $key => $value)
                $array[] = $value;

            return $array;
        } else {
            return false;
        }
    }

    /**
     * @inheritDoc
     * @return array|bool
     */
    protected function _fetchAssoc()
    {
        if (count($this->result['value']) > 0)
            return array_shift($this->result['value']);
        else
            return false;
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

    /**
     * @param $statement
     * @return mixed|null
     * @throws DBDException
     */
    protected function _query($statement)
    {
        $this->query = $statement;
        $this->prepareRequestUrl();

        // just initiate connect with prepared URL and HEADERS
        $this->setupRequest($this->Config->getHost() . $this->requestUrl);
        // and make request
        $this->_connect();

        // Will return NULL in case of failure
        return json_decode($this->body, true);
    }

    /**
     * @return bool
     * @throws DBDException
     */
    protected function _rollback(): bool
    {
        throw new DBDException("OData doesn't not support transactions");
    }

    /**
     * @return int
     * @inheritDoc
     * @todo Проверить как реагарует постгрес на количество строк после фетчей
     */
    protected function _rows(): int
    {
        if (isset($this->initialAffectedRows))
            return $this->initialAffectedRows;

        if (is_array($this->result) and isset($this->result['value']))
            $this->initialAffectedRows = count($this->result['value']);
        else
            $this->initialAffectedRows = 0;

        return $this->initialAffectedRows;
    }

    protected function replaceBind(string &$preparedQuery, Bind $bind): void
    {
        // TODO: Implement replaceBind() method.
    }
}
