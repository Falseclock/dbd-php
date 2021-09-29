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
use DBD\Base\CRUD;
use DBD\Base\Helper;
use DBD\Common\DBDException;
use DBD\Entity\Common\EntityException;
use DBD\Entity\Entity;
use DBD\Entity\Primitive;
use DBD\Utils\InsertArguments;
use DBD\Utils\OData\Metadata;
use DBD\Utils\UpdateArguments;
use Throwable;

class OData extends DBD
{
    const METHOD_POST = "POST";
    const METHOD_GET = "GET";
    const METHOD_PATCH = "PATCH";
    const METHOD_DELETE = "DELETE";
    const METHOD_PUT = "PUT";
    protected $body = null;
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
     * @return bool
     * @throws DBDException
     */
    public function entityDelete(Entity $entity): bool
    {
        /** @var Bind[] $binds */
        [$columns, $binds] = $this->makeBindsForEntity($entity);

        $query = sprintf("DELETE FROM %s WHERE %s", $entity::TABLE, implode(" AND ", $columns));

        $sth = $this->prepare($query);
        foreach ($binds as $bind)
            $sth->bind($bind->name, $bind->value, null, $bind->column);

        $sth->execute();

        if ($sth->rows() > 0)
            return true;

        return false;
    }

    /**
     * @param Entity $entity
     * @return array[]
     * @throws DBDException
     */
    private function makeBindsForEntity(Entity $entity): array
    {
        try {
            $keys = $entity::map()->getPrimaryKey();

            if (!count($keys))
                throw new DBDException(sprintf("Entity %s does not have any defined primary key", get_class($entity)));

            $columns = [];
            $binds = [];

            foreach ($keys as $keyName => $column) {
                if (!isset($entity->$keyName))
                    throw new DBDException(sprintf("Value of %s->%s, which is primary key column, is null", get_class($entity), $keyName));

                $columns[] = "{$column->name} = :{$column->name}";
                $binds[] = new Bind(":{$column->name}", $entity->$keyName, $column->type->getValue(), $column->name);
            }

            return [$columns, $binds];
        } catch (Throwable $e) {
            if ($e instanceof DBDException)
                throw $e;
            else
                throw new DBDException($e->getMessage(), null, null, $e);
        }
    }

    /**
     * @param Entity $entity
     * @param bool $exceptionIfNoRecord
     * @return Entity|null
     * @throws DBDException
     * @inheritDoc
     */
    public function entitySelect(Entity &$entity, bool $exceptionIfNoRecord = true): ?Entity
    {
        /** @var Bind[] $binds */
        [$columns, $binds] = $this->makeBindsForEntity($entity);

        // fictive query for logging and etc
        $statement = sprintf("SELECT * FROM %s WHERE %s", $entity::TABLE, implode(" AND ", $columns));

        foreach ($binds as $bind)
            $this->replaceBind($statement, $bind);

        $sth = $this->prepare($statement);
        $sth->execute();

        if (!$sth->rows()) {
            if ($exceptionIfNoRecord)
                throw new DBDException(sprintf("No data found for entity %s with ", get_class($entity)));
            else
                return null;
        }

        /** @var Entity $class */
        $class = get_class($entity);

        $entity = new $class($sth->fetchRow());

        return $entity;
    }

    /**
     * @param Entity $entity
     * @return Entity
     * @inheritDoc
     */
    public function entityInsert(Entity &$entity): Entity
    {
        try {
            $record = $this->createInsertRecord($entity);

            $params = Helper::compileInsertArgs($record, $this);

            // ONLY FOR EMULATION
            $this->query = $this->_compileInsert($entity::TABLE, $params);

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
     * That's a fictive function, just to emulate query
     * @param string $table
     * @param InsertArguments $insert
     * @param string|null $return
     * @return string
     * @throws DBDException
     * @inheritDoc
     */
    protected function _compileInsert(string $table, InsertArguments $insert, ?string $return = null): string
    {
        $values = array_map(function ($value) {
            return $this->escape($value);
        }, $insert->arguments);

        $values = implode(",", $values);

        $return = $return ? sprintf(" RETURNING %s", $return) : null;

        return sprintf("INSERT INTO %s (%s) VALUES (%s)", $table, implode(", ", $insert->columns), $values) . $return;
    }

    /**
     * @param mixed $value
     * @return string
     * @inheritDoc
     */
    protected function _escape($value): string
    {
        return sprintf("'%s'", $this->_escapeEncode($value));
    }

    /**
     * @param $string
     *
     * @return string
     */
    protected function _escapeEncode($string): string
    {

        $search = ['!', '*', '\'', '(', ')', ';', ':', '@', '&', '=', '+', '$', ',', '/', '?', '#', '[', ']'];
        $replace = ['%21', '%2A', '%27', '%28', '%29', '%3B', '%3A', '%40', '%26', '%3D', '%2B', '%24', '%2C', '%2F', '%3F', '%23', '%5B', '%5D'];

        return str_replace($search, $replace, $string);
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
        $this->setupRequest($this->Config->getHost() . $table . '?$format=application/json&', self::METHOD_POST, json_encode($args, JSON_UNESCAPED_UNICODE));
        $this->_connect();

        return json_decode($this->body, true);
    }

    /**
     * @param $url
     * @param string $method
     * @param null $content
     * @return $this
     */
    protected function setupRequest($url, $method = self::METHOD_GET, $content = null): self
    {
        if (!is_resource($this->resourceLink))
            $this->resourceLink = curl_init();

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
            case self::METHOD_POST:
                curl_setopt($this->resourceLink, CURLOPT_POST, true);
                curl_setopt($this->resourceLink, CURLOPT_HTTPGET, false);
                curl_setopt($this->resourceLink, CURLOPT_CUSTOMREQUEST, null);
                break;
            case self::METHOD_PATCH:
                curl_setopt($this->resourceLink, CURLOPT_POST, false);
                curl_setopt($this->resourceLink, CURLOPT_HTTPGET, false);
                curl_setopt($this->resourceLink, CURLOPT_CUSTOMREQUEST, self::METHOD_PATCH);
                break;
            case self::METHOD_PUT:
                curl_setopt($this->resourceLink, CURLOPT_POST, false);
                curl_setopt($this->resourceLink, CURLOPT_HTTPGET, false);
                curl_setopt($this->resourceLink, CURLOPT_CUSTOMREQUEST, self::METHOD_PUT);
                break;
            case self::METHOD_DELETE:
                curl_setopt($this->resourceLink, CURLOPT_POST, false);
                curl_setopt($this->resourceLink, CURLOPT_HTTPGET, false);
                curl_setopt($this->resourceLink, CURLOPT_CUSTOMREQUEST, self::METHOD_DELETE);
                break;
            case self::METHOD_GET:
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
     * @inheritDoc
     */
    protected function _connect(): void
    {
        // if we never invoke connect and did not setup it, just call setup with DSN url
        if (!is_resource($this->resourceLink)) {
            $this->setupRequest($this->Config->getHost());
        }

        $response = curl_exec($this->resourceLink);
        $header_size = curl_getinfo($this->resourceLink, CURLINFO_HEADER_SIZE);
        $this->header = trim(substr($response, 0, $header_size));
        $this->body = preg_replace("/\xEF\xBB\xBF/", "", substr($response, $header_size));
        $this->httpCode = curl_getinfo($this->resourceLink, CURLINFO_HTTP_CODE);

        if ($this->httpCode < 200 || $this->httpCode > 300) {
            switch (Helper::getQueryType($this->query)) {
                case CRUD::DELETE:
                    switch ($this->httpCode) {
                        // Entity not found, we will just return 0 affected rows()
                        case 404:
                            return;
                        default:
                            $this->parseError();
                    }
                    break;
                case CRUD::CREATE:
                case CRUD::UPDATE:
                case CRUD::READ:
                default:
                    $this->parseError();
            }
        }
    }

    /*--------------------------------------------------------------*/

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
            if (!is_null($metadata)) {
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

    protected function _compileUpdate(string $table, UpdateArguments $updateArguments, ?string $where = null, ?string $return = null): string
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
     * @throws DBDException
     */
    protected function _dump(string $preparedQuery, string $filePath, string $delimiter, string $nullString, bool $showHeader): void
    {
        throw new DBDException("OData doesn't not data dumping");
    }

    protected function _errorMessage(): string
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
    protected function _executeNamed($uniqueName, $arguments)
    {
        throw new DBDException("OData doesn't not support named query execution");
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
     * @inheritDoc
     * @return bool
     * @throws DBDException
     */
    protected function _prepareNamed(string $uniqueName, string $statement): bool
    {
        throw new DBDException("OData doesn't not support named prepared queries");
    }

    /**
     * @param $statement
     * @return array|null
     * @throws DBDException
     * @inheritDoc
     */
    protected function _query($statement): ?array
    {
        $this->processQuery($statement);

        return $this->result;
    }

    /**
     * @return $this
     * @throws DBDException
     */
    protected function prepareRequestUrl(): self
    {
        // keep initial query unchanged
        $query = $this->query;

        // make one string for REGEXP
        $query = preg_replace('/\t/', " ", $query);
        $query = preg_replace('/\r/', "", $query);
        $query = preg_replace('/\n/', " ", $query);
        $query = preg_replace('/\s+/', " ", $query);
        $query = trim($query);

        // split whole query by special words
        $pieces = preg_split('/(?=(DELETE|SELECT|FROM|WHERE|ORDER BY|LIMIT|EXPAND|JOIN).+?)/u', $query);
        $struct = [];

        foreach ($pieces as $piece) {
            preg_match('/(DELETE|SELECT|FROM|WHERE|ORDER BY|LIMIT|EXPAND|JOIN)(.+)/u', $piece, $matches);
            if (count($matches)) {
                $rule = strtoupper(trim($matches[1]));
                if ($rule == 'JOIN')
                    $struct[$rule][] = trim($matches[2]);
                else
                    $struct[$rule] = trim($matches[2]);
            }
        }

        if (isset($struct['DELETE'])) {
            if (!isset($struct['WHERE']))
                throw new DBDException("WHERE not declared for OData entity");

            $params = [];
            foreach ($this->binds as $bind)
                $params[] = sprintf("%s='%s'", $bind->column, $bind->value);

            $this->requestUrl = sprintf("%s(%s)?\$format=application/json", $struct['FROM'], implode(",", $params));

            return $this;
        }

        // Start URL build
        $this->requestUrl = "{$struct['FROM']}?\$format=application/json&";

        if (isset($struct['SELECT'])) {
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
        }

        $expandEntities = [];
        if (isset($struct['EXPAND'])) {
            $expandEntities[] = $struct['EXPAND'];
        }

        if (isset($struct['JOIN'])) {
            foreach ($struct['JOIN'] as $expand) {
                preg_match('/(.+?)\s/', $expand, $matches);
                $expandEntities[] = $matches[1];
            }
        }

        if (count($expandEntities) > 0) {
            $this->requestUrl .= '$expand=' . implode(',', $expandEntities) . '&';
        }

        // TODO: change AND and OR case
        if (isset($struct['WHERE'])) {

            $where = $struct['WHERE'];
            $paramId = 1;
            $params = [];
            preg_replace_callback("('.+?')",
                function ($matches) use (&$where, &$paramId, &$params) {
                    foreach ($matches as $match) {
                        $params[sprintf(":param%d", $paramId)] = $this->_escapeEncode($match);
                        $position = strpos($where, $match);

                        if ($position !== false)
                            $where = substr_replace($where, sprintf(":param%d", $paramId), $position, strlen($match));

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

        if (isset($struct['LIMIT']))
            $this->requestUrl .= '$top=' . $struct['LIMIT'] . '&';

        return $this;
    }

    /**
     * @return int
     * @inheritDoc
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

    /**
     * @return bool
     * @throws DBDException
     */
    protected function _rollback(): bool
    {
        throw new DBDException("OData doesn't not support transactions");
    }

    /**
     * @param string $preparedQuery
     * @param Bind $bind
     */
    protected function replaceBind(string &$preparedQuery, Bind $bind): void
    {
        switch ($bind->type) {
            case Primitive::Guid:
                $preparedQuery = str_replace($bind->name, sprintf("%s'%s'", strtolower($bind->type), $bind->value), $preparedQuery);
                break;
            case Primitive::String:
            default:
                $preparedQuery = str_replace($bind->name, sprintf("'%s'", $bind->value), $preparedQuery);
                break;
        }
    }

    /**
     * @param $statement
     * @param bool $doUrlPreparation
     * @throws DBDException
     */
    private function processQuery($statement, $doUrlPreparation = true): void
    {
        $this->initialAffectedRows = null;
        $this->query = $statement;

        if ($doUrlPreparation)
            $this->prepareRequestUrl();

        $method = null;
        switch (Helper::getQueryType($this->query)) {
            case CRUD::UPDATE:
                $method = self::METHOD_PATCH;
                break;
            case CRUD::DELETE:
                $method = self::METHOD_DELETE;
                break;
            case CRUD::CREATE:
                $method = self::METHOD_POST;
                break;
            default:
                $method = self::METHOD_GET;
        }

        // just initiate connect with prepared URL and HEADERS
        $this->setupRequest($this->Config->getHost() . $this->requestUrl, $method);
        // and make request
        $this->_connect();

        switch ($method) {
            case self::METHOD_DELETE:
                $this->result = [];
                switch ($this->httpCode) {
                    case 204:
                        $this->initialAffectedRows = 1;
                        break;
                    case 404:
                        $this->initialAffectedRows = 0;
                        break;
                }
                break;
            default:
                // Will return NULL in case of failure
                $this->result = json_decode($this->body, true);
                // Count rows in advance
                $this->_rows();

        }
    }

    protected function _inTransaction(): bool
    {
        // TODO: Implement inTransaction() method.
    }
}
