<?php
/**
 * DBD
 *
 * @author    Nurlan Mukhanov <nurike@gmail.com>
 * @copyright 2020 Nurlan Mukhanov
 * @license   https://en.wikipedia.org/wiki/MIT_License MIT License
 * @link      https://github.com/Falseclock/dbd-php
 */

declare(strict_types=1);

namespace DBD;

use DateInterval;
use DBD\Base\Bind;
use DBD\Base\CacheHolder;
use DBD\Base\Config;
use DBD\Base\Debug;
use DBD\Base\Helper;
use DBD\Base\Options;
use DBD\Base\Query;
use DBD\Common\DBDException;
use DBD\Entity\Common\EntityException;
use DBD\Entity\Constraint;
use DBD\Entity\Entity;
use DBD\Entity\Primitive;
use Exception;
use Psr\SimpleCache\InvalidArgumentException;
use ReflectionClass;
use Throwable;

/**
 * Class DBD
 *
 * @package DBD
 */
abstract class DBD
{
    const CSV_EXTENSION = "csv";
    const STORAGE_CACHE = "Cache";
    const STORAGE_DATABASE = "database";
    const UNDEFINED = "UNDEF";
    const GOT_FROM_CACHE = "GOT_FROM_CACHE";
    /** @var array $preparedStatements */
    public static $preparedStatements = [];
    /** @var array $executedStatements queries that's really executed in database */
    public static $executedStatements = [];
    /** @var Config $Config */
    protected $Config;
    /** @var Options $Options */
    protected $Options;
    /** @var string $query SQL query */
    protected $query;
    /** @var resource $resourceLink Database or curl connection resource */
    protected $resourceLink;
    /** @var resource $result Query result data */
    protected $result;
    /** @var CacheHolder */
    protected $CacheHolder = null;
    /** @var string $storage This param is used for identifying where data taken from */
    protected $storage;
    /** @var mixed $fetch */
    private $fetch = self::UNDEFINED;
    /** @var bool $inTransaction Stores current transaction state */
    private $inTransaction = false;
    /** @var Bind[] $binds */
    private $binds = [];

    /**
     * DBD constructor.
     * ```
     * $db = new DBD/Pg($config, $options);
     * $db->connect();
     * ```
     *
     * @param Config $config
     * @param Options|null $options
     */
    final public function __construct(Config $config, Options $options = null)
    {
        $this->Config = $config;

        if (!is_null($options))
            $this->Options = $options;
        else
            $this->Options = new Options();
    }

    /**
     * Must be called after statement prepare
     * ```
     * $sth = $db->prepare("SELECT bank_id AS id, bank_name AS name FROM banks ORDER BY bank_name ASC");
     * $sth->cache("AllBanks");
     * $sth->execute();
     * ```
     *
     * @param string $key
     * @param int|float|DateInterval|string $ttl
     *
     * @throws DBDException
     */
    public function cache(string $key, $ttl = null)
    {
        if (isset($this->Config->cacheDriver)) {

            if (!isset($this->query))
                throw new DBDException("SQL statement not prepared");

            if (preg_match("/^[\s\t\r\n]*select/i", $this->query)) {
                // set hash key
                $this->CacheHolder = new CacheHolder($key);

                if ($ttl !== null)
                    $this->CacheHolder->expire = $ttl;
            } else {
                throw new DBDException("Caching setup failed, current query is not of SELECT type");
            }
        }
    }

    /**
     * Base and main method to start. Returns self instance of DBD driver
     * ```
     * $db = (new DBD\Pg())->connect($config, $options);
     * ```
     *
     * @return $this
     * @see MSSQL::connect
     * @see MySQL::connect
     * @see OData::connect
     * @see Pg::connect
     */
    abstract public function connect(): DBD;

    /**
     * Closes a database connection
     *
     * @return $this
     * @throws DBDException
     */
    public function disconnect(): DBD
    {
        if ($this->isConnected()) {
            if ($this->inTransaction) {
                throw new DBDException("Uncommitted transaction state");
            }
            $this->_disconnect();
            $this->resourceLink = null;
        }

        return $this;
    }

    /**
     * Check whether connection is established or not
     *
     * @return bool true if var is a resource, false otherwise
     */
    protected function isConnected(): bool
    {
        return is_resource($this->resourceLink);
    }

    /**
     * @return bool true on successful disconnection
     * @see Pg::_disconnect
     * @see MSSQL::_disconnect
     * @see MySQL::_disconnect
     * @see OData::_disconnect
     * @see disconnect
     */
    abstract protected function _disconnect(): bool;

    /**
     * Just executes query and returns affected rows with the query
     *
     * @return int
     * @throws DBDException
     */
    public function do(): int
    {
        if (!func_num_args())
            throw new DBDException("query failed: statement is not set or empty");

        [$statement, $args] = Helper::prepareArgs(func_get_args());

        $sth = $this->query($statement, $args);
        $this->result = $sth->result;

        return $sth->rows();
    }

    /**
     * Like doit method, but return self instance
     *
     * Example 1:
     * ```
     * $sth = $db->query("SELECT * FROM invoices");
     * while ($row = $sth->fetchRow()) {
     *      //do something
     * }
     * ```
     *
     * Example 2:
     * ```
     * $sth = $db->query("UPDATE invoices SET invoice_uuid=?",'550e8400-e29b-41d4-a716-446655440000');
     * echo($sth->affectedRows());
     * ```
     *
     * @return DBD
     * @throws DBDException
     */
    public function query(): DBD
    {
        if (!func_num_args())
            throw new DBDException("query failed: statement is not set or empty");

        [$statement, $args] = Helper::prepareArgs(func_get_args());

        return $this->prepare($statement)->execute($args);
    }

    /**
     * Sends a request to execute a prepared statement with given parameters, and waits for the result.
     *
     * @return DBD
     * @throws DBDException
     */
    public function execute(): DBD
    {
        // Unset result
        $this->result = null;
        $this->storage = null;
        $this->fetch = self::UNDEFINED;

        $executeArguments = func_get_args();
        $preparedQuery = $this->getPreparedQuery($executeArguments);

        //--------------------------------------
        // Is query uses cache?
        //--------------------------------------
        if (isset($this->Config->cacheDriver) and !is_null($this->CacheHolder)) {

            if ($this->Options->isUseDebug())
                Debug::me()->startTimer();

            // Get data from cache
            try {
                $this->CacheHolder->result = $this->Config->cacheDriver->get($this->CacheHolder->key);
            } catch (Throwable | Exception | InvalidArgumentException $e) {
                throw new DBDException("Failed to get from cache: {$e->getMessage()}", $preparedQuery);
            }

            // Cache not empty?
            if ($this->CacheHolder->result !== false) {
                $cost = Debug::me()->endTimer();
                // To avoid errors as result by default is NULL
                $this->result = self::GOT_FROM_CACHE;
                $this->storage = self::STORAGE_CACHE;
            }
        }

        // If not found in cache, then let's get from DB
        if ($this->result != self::GOT_FROM_CACHE) {

            $this->connectionPreCheck();

            if ($this->Options->isUseDebug())
                Debug::me()->startTimer();

            if ($this->Options->isPrepareExecute()) {
                $uniqueName = crc32($preparedQuery);

                // We can call same query several times, that is why we should store
                // it statically, cause database will raise error if we will try
                // to store same query again
                if (!isset(self::$preparedStatements[$uniqueName])) {
                    self::$preparedStatements[$uniqueName] = $preparedQuery;

                    if (!$this->_prepareNamed((string)$uniqueName, $preparedQuery))
                        throw new DBDException ($this->_errorMessage(), $preparedQuery);
                }
                $this->result = $this->_executeNamed($uniqueName, Helper::parseArgs($executeArguments));
                self::$executedStatements[] = $preparedQuery;
            } else {
                // Execute query to the database
                $this->result = $this->_query($preparedQuery);
                self::$executedStatements[] = $preparedQuery;
            }

            $cost = Debug::me()->endTimer();

            if (is_null($this->result))
                throw new DBDException ($this->_errorMessage(), $preparedQuery, $this->Options->isPrepareExecute() ? Helper::parseArgs($executeArguments) : null);

            $this->storage = self::STORAGE_DATABASE;

            // Now we have to store result in the cache
            if (!is_null($this->CacheHolder)) {

                // Emulating we got it from cache
                $this->CacheHolder->result = $this->fetchRowSet();
                $this->storage = self::STORAGE_CACHE;

                // Setting up our cache
                try {
                    $this->Config->cacheDriver->set($this->CacheHolder->key, $this->CacheHolder->result, $this->CacheHolder->expire);
                } catch (Exception | InvalidArgumentException | Throwable $e) {
                    throw new DBDException("Failed to store in cache: {$e->getMessage()}", $preparedQuery);
                }
            }
        }

        if ($this->Options->isUseDebug()) {
            $cost = isset($cost) ? $cost : 0;

            $driver = $this->storage == self::STORAGE_CACHE ? self::STORAGE_CACHE : (new ReflectionClass($this))->getShortName();
            $caller = Helper::caller($this);

            Debug::addQueries(
                new Query(
                    Helper::cleanSql($this->getPreparedQuery($executeArguments, true)),
                    $cost,
                    $caller[0],
                    Helper::debugMark($cost),
                    $driver
                )
            );

            Debug::addTotalQueries(1);
            Debug::addTotalCost($cost);
        }

        return $this;
    }

    /**
     * @param      $ARGS
     * @param bool $overrideOption
     *
     * @return string
     * @throws DBDException
     */
    private function getPreparedQuery($ARGS, $overrideOption = false): string
    {
        $placeHolder = $this->Options->getPlaceHolder();
        $isPrepareExecute = $this->Options->isPrepareExecute();

        $preparedQuery = $this->query;
        $binds = substr_count($this->query, $placeHolder);
        $executeArguments = Helper::parseArgs($ARGS);

        $numberOfArgs = count($executeArguments);

        if ($binds != $numberOfArgs)
            throw new DBDException("Execute failed, called with $numberOfArgs bind variables when $binds are needed", $this->query, $executeArguments);

        if ($numberOfArgs) {
            $query = str_split($this->query);

            $placeholderPosition = 1;
            foreach ($query as $ind => $str) {
                if ($str == $placeHolder) {
                    if ($isPrepareExecute and !$overrideOption) {
                        $query[$ind] = "\${$placeholderPosition}";
                        $placeholderPosition++;
                    } else {
                        $query[$ind] = $this->_escape(array_shift($executeArguments));
                    }
                }
            }
            $preparedQuery = implode("", $query);
        }

        foreach ($this->binds as $bind)
            $this->replaceBind($preparedQuery, $bind);

        return $preparedQuery;
    }

    /**
     * @param mixed $string
     *
     * @return mixed
     * @see MSSQL::_escape
     * @see MySQL::_escape
     * @see OData::_escape
     * @see Pg::_escape
     * @see getPreparedQuery
     */
    abstract protected function _escape($string): string;

    /**
     * @param string $preparedQuery
     * @param Bind $bind
     * @return void
     * @see Pg::replaceBind
     * @see MSSQL::replaceBind
     * @see MySQL::replaceBind
     * @see OData::replaceBind
     */
    abstract protected function replaceBind(string &$preparedQuery, Bind $bind): void;

    /**
     * Check connection existence and does connection if not
     *
     * @return void
     */
    private function connectionPreCheck()
    {
        if (!$this->isConnected())
            $this->_connect();
    }

    /**
     * @return void
     * @see Pg::_connect
     * @see MSSQL::_connect
     * @see MySQL::_connect
     * @see OData::_connect
     * @see connectionPreCheck
     */
    abstract protected function _connect(): void;

    /**
     * Prepare named query
     *
     * @param string $uniqueName
     * @param string $statement
     *
     * @return bool|null
     * @see MSSQL::_prepareNamed
     * @see MySQL::_prepareNamed
     * @see OData::_prepareNamed
     * @see Pg::_prepareNamed
     */
    abstract protected function _prepareNamed(string $uniqueName, string $statement): ?bool;

    /**
     * @return string
     * @see MSSQL::_errorMessage
     * @see MySQL::_errorMessage
     * @see OData::_errorMessage
     * @see Pg::_errorMessage
     */
    abstract protected function _errorMessage(): string;

    /**
     * Executes prepared named question
     * @param $uniqueName
     * @param $arguments
     *
     * @return mixed
     * @see MSSQL::_executeNamed()
     * @see MySQL::_executeNamed()
     * @see OData::_executeNamed()
     * @see Pg::_executeNamed()
     * @see DBD::execute()
     */
    abstract protected function _executeNamed($uniqueName, $arguments);

    /**
     * Executes the query on the specified database connection.
     *
     * @param $statement
     *
     * @return mixed|null
     * @see Pg::_query
     * @see MSSQL::_query
     * @see MySQL::_query
     * @see OData::_query
     * @see execute
     */
    abstract protected function _query($statement);

    /**
     * @param null $uniqueKey
     *
     * @return array|mixed
     * @throws DBDException
     */
    public function fetchRowSet($uniqueKey = null): array
    {
        $array = [];

        if ($this->storage == self::STORAGE_DATABASE) {
            while ($row = $this->fetchRow()) {
                if ($uniqueKey) {
                    if (!isset($array[$row[$uniqueKey]]))
                        $array[$row[$uniqueKey]] = $row;
                    else
                        throw new DBDException("Key '{$row[$uniqueKey]}' not unique");
                } else {
                    $array[] = $row;
                }
            }
        } else {
            if ($uniqueKey) {
                foreach ($this->CacheHolder->result as $row) {
                    if (!isset($array[$row[$uniqueKey]]))
                        $array[$row[$uniqueKey]] = $row;
                    else
                        throw new DBDException("Key '{$row[$uniqueKey]}' not unique");
                }
            } else {
                $array = $this->CacheHolder->result;
            }

            $this->CacheHolder->result = [];
        }

        return $array;
    }

    /**
     * @return mixed|null
     */
    public function fetchRow()
    {
        if ($this->storage == self::STORAGE_DATABASE) {
            $return = $this->_fetchAssoc();

            if ($this->Options->isConvertNumeric() || $this->Options->isConvertBoolean())
                $this->_convertTypes($return);

            return $return;
        } else {
            return array_shift($this->CacheHolder->result);
        }
    }

    /**
     * @return array|bool
     * @see DBD::fetchRow()
     * @see Pg::_fetchAssoc
     * @see MSSQL::_fetchAssoc
     * @see MySQL::_fetchAssoc
     * @see OData::_fetchAssoc
     * @see fetchRow
     */
    abstract protected function _fetchAssoc();

    /**
     * @param $data
     *
     * @return void
     * @see Pg::_convertTypes
     * @see MySQL::_convertTypes
     * @see OData::_convertTypes
     * @see MSSQL::_convertTypes
     */
    abstract protected function _convertTypes(&$data): void;

    /**
     * Creates a prepared statement for later execution.
     * Calling this function new instance of driver will be created and all
     * options and configuration will be passed as reference, as well as resource
     * link, caching driver and transaction state
     *
     * @param string $statement
     *
     * @return $this
     * @throws DBDException
     */
    public function prepare(string $statement): DBD
    {
        if (!isset($statement) or empty($statement))
            throw new DBDException("prepare failed: statement is not set or empty");

        $className = get_class($this);
        $class = new $className($this->Config, $this->Options);

        $class->resourceLink = &$this->resourceLink;
        $class->inTransaction = &$this->inTransaction;
        $class->query = $statement;

        return $class;
    }

    /**
     * Returns the number of rows in the result
     *
     * @return int
     */
    final public function rows(): int
    {
        if ($this->storage == self::STORAGE_DATABASE) {
            return $this->_rows();
        } else {
            return count($this->CacheHolder->result);
        }
    }

    /**
     * @return int number of updated or deleted rows
     * @see rows
     * @see Pg::_rows
     * @see MSSQL::_rows
     * @see MySQL::_rows
     * @see OData::_rows
     */
    abstract protected function _rows(): int;

    /**
     * @param string $paramName
     * @param mixed $value
     * @param string $dataType
     * @return $this
     * @throws DBDException
     */
    public function bind(string $paramName, $value, string $dataType = Primitive::String): DBD
    {
        $this->binds[] = new Bind($paramName, $value, $dataType);
        return $this;
    }

    /**
     * Dumping result as CSV file
     *
     * @param array|null $executeArguments
     * @param string $fileName
     * @param string $delimiter
     * @param string $nullString
     * @param bool $header
     * @param string $tmpPath
     * @param string $type
     * @param bool $utf8
     *
     * @return mixed
     * @throws DBDException
     */
    public function dump(?array $executeArguments = [], $fileName = "dump", $delimiter = "\\t", $nullString = "", $header = true, $tmpPath = "/tmp", $type = "csv", $utf8 = true)
    {
        $BOM = b"\xEF\xBB\xBF";
        $preparedQuery = $this->getPreparedQuery($executeArguments);

        $filename = $this->_dump($preparedQuery, $fileName, $delimiter, $nullString, $header, $tmpPath);

        header('Content-Description: File Transfer');
        switch (strtolower($type)) {
            case "csv":
                header('Content-Type: text/csv');
                header('Content-Disposition: attachment;filename="' . $fileName . '.csv"');
                break;
            case "tsv":
                header('Content-Type: text/tab-separated-values');
                header('Content-Disposition: attachment;filename="' . $fileName . '.tsv"');
                break;
            default:
                header('Content-Type: text/plain');
                header('Content-Disposition: attachment;filename="' . $fileName . '.txt"');
                break;
        }

        header('Cache-Control: max-age=0');
        // If you're serving to IE 9, then the following may be needed
        header('Cache-Control: max-age=1');
        header('Expires: 0');
        header('Pragma: public');

        if ($utf8) {
            $file = @fopen($filename, "r");
            $bom = fread($file, 3);
            fclose($file);

            if ($bom != $BOM) {
                header('Content-Length: ' . (filesize($filename) + mb_strlen($BOM)));
                echo $BOM;
            }
        } else {
            header('Content-Length: ' . filesize($filename));
        }

        readfile($filename);

        unlink($filename);

        exit;
    }

    /**
     * @param string $preparedQuery
     * @param string $fileName
     * @param string $delimiter
     * @param string $nullString
     * @param bool $showHeader
     * @param string $tmpPath
     *
     * @return string full file path
     * @see Pg::_dump
     * @see MSSQL::_dump
     * @see MySQL::_dump
     * @see OData::_dump
     * @see DBD::dump()
     */
    abstract protected function _dump(string $preparedQuery, string $fileName, string $delimiter, string $nullString, bool $showHeader, string $tmpPath): string;

    /**
     *
     * @param Entity $entity
     *
     * @return bool
     * @throws DBDException
     */
    public function entityDelete(Entity $entity): bool
    {
        [$execute, $columns] = $this->getPrimaryKeysForEntity($entity);

        $sth = $this->prepare(sprintf("DELETE FROM %s.%s WHERE %s", $entity::SCHEME, $entity::TABLE, implode(" AND ", $columns)));
        $sth->execute($execute);

        if ($sth->rows() > 0)
            return true;

        return false;
    }

    /**
     * @param Entity $entity
     *
     * @return array
     * @throws DBDException
     */
    private function getPrimaryKeysForEntity(Entity $entity): array
    {
        try {
            $keys = $entity::map()->getPrimaryKey();

            if (!count($keys))
                throw new DBDException(sprintf("Entity %s does not have any defined primary key", get_class($entity)));

            $columns = [];
            $execute = [];

            $placeHolder = $this->Options->getPlaceHolder();

            foreach ($keys as $keyName => $column) {
                if (!isset($entity->$keyName))
                    throw new DBDException(sprintf("Value of %s->%s, which is primary key column, is null", get_class($entity), $keyName));

                $execute[] = $entity->$keyName;
                $columns[] = "{$column->name} = {$placeHolder}";
            }

            return [$execute, $columns];
        } catch (Exception $e) {
            if ($e instanceof DBDException)
                throw $e;
            else
                throw new DBDException($e->getMessage(), null, null, $e);
        }
    }

    /**
     * @param Entity $entity
     *
     * @return Entity
     * @throws DBDException
     */
    public function entityInsert(Entity &$entity): Entity
    {
        try {
            $record = $this->createInsertRecord($entity);

            $sth = $this->insert($entity::table(), $record, "*");

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
     * @param Entity $entity
     * @return array
     * @throws DBDException
     * @throws EntityException
     */
    protected function createInsertRecord(Entity $entity): array
    {
        $record = [];

        $columns = $entity::map()->getColumns();

        // Cycle through all available columns according to Mapper definition
        foreach ($columns as $propertyName => $column) {

            $originName = $column->name;

            if ($column->nullable == false) {
                // Mostly we always define properties for any columns
                if (property_exists($entity, $propertyName)) {
                    if (!isset($entity->$propertyName) and ($column->isAuto === false and !isset($column->defaultValue)))
                        throw new DBDException(sprintf("Property '%s' of %s can't be null according to Mapper annotation", $propertyName, get_class($entity)));

                    if ($column->isAuto === false) {
                        // Finally add column to record if it is set
                        if (isset($entity->$propertyName))
                            $finalValue = $entity->$propertyName;
                        else
                            $finalValue = $column->defaultValue;

                        $record[$originName] = $column->type->getValue() == Primitive::Binary ? $this->_escapeBinary($finalValue) : $finalValue;
                    }
                }
            } else {
                // Finally add column to record if it is set
                if (isset($entity->$propertyName)) {
                    $record[$originName] = ($column->type->getValue() == Primitive::Binary) ? $this->_escapeBinary($entity->$propertyName) : $entity->$propertyName;
                } else {
                    // If value not set and we have some default value, let's define also
                    if ($column->isAuto === false and isset($column->defaultValue))
                        $record[$originName] = $column->defaultValue;
                }
            }
        }
        return $record;
    }

    /**
     * @param string|null $binaryString
     *
     * @return string|null
     * @see entityInsert
     * @see Pg::_escapeBinary
     * @see MSSQL::_escapeBinary
     * @see MySQL::_escapeBinary
     * @see OData::_escapeBinary
     * @see execute
     */
    abstract protected function _escapeBinary(?string $binaryString): ?string;

    /**
     * Easy insert operation
     *
     * @param string $table
     * @param array $args
     * @param string|null $return
     *
     * @return DBD
     * @throws DBDException
     */
    public function insert(string $table, array $args, string $return = null): DBD
    {
        $params = Helper::compileInsertArgs($args, $this, $this->Options);

        $sth = $this->prepare($this->_compileInsert($table, $params, $return));

        return $sth->execute($params['ARGS']);
    }

    /**
     * @param string $table
     * @param array $params
     * @param string|null $return
     *
     * @return mixed
     * @see OData::_compileInsert
     * @see Pg::_compileInsert
     * @see MSSQL::_compileInsert
     * @see MySQL::_compileInsert
     * @see insert
     */
    abstract protected function _compileInsert(string $table, array $params, ?string $return = ""): string;

    /**
     *
     * @param Entity $entity
     *
     * @return Entity
     * @throws DBDException
     */
    public function entityUpdate(Entity &$entity): Entity
    {
        [$execute, $primaryColumns] = $this->getPrimaryKeysForEntity($entity);

        $record = [];

        try {
            $columns = $entity::map()->getColumns();
            $constraints = $entity::map()->getConstraints();
        } catch (EntityException $e) {
            throw new DBDException($e->getMessage(), null, null, $e);
        }

        foreach ($columns as $propertyName => $column) {

            if (property_exists($entity, $propertyName) and isset($entity->$propertyName)) {
                if ($column->type == Primitive::String and stripos($column->originType, 'json') !== false and !is_string($entity->$propertyName)) {
                    $entity->$propertyName = json_encode($entity->$propertyName, JSON_UNESCAPED_UNICODE);
                }
            }

            if ($column->nullable === false) {
                if (property_exists($entity, $propertyName)) {
                    if (isset($entity->$propertyName))
                        $record[$column->name] = $entity->$propertyName;
                    else
                        throw new DBDException(sprintf("Property '%s' of %s can't be null", $propertyName, get_class($entity)));
                } else {
                    throw new DBDException(sprintf("Property '%s' of %s not set", $propertyName, get_class($entity)));
                }
            } else {
                if (property_exists($entity, $propertyName)) {
                    $record[$column->name] = $entity->$propertyName;
                } else {
                    // Possibly we got reference constraint field
                    foreach ($constraints as $constraintName => $constraint) {
                        if (property_exists($entity, $constraintName)) {
                            if ($constraint->localColumn->name == $column->name and isset($entity->$constraintName)) {

                                $foreignProperty = $this->findForeignProperty($constraint);

                                if (isset($entity->$constraintName->$foreignProperty)) {
                                    $record[$column->name] = $entity->$constraintName->$foreignProperty;
                                }
                                // Otherwise it seems we do not want update reference value
                            }
                        }
                    }
                }
            }
        }

        $sth = $this->update($entity::table(), $record, implode(" AND ", $primaryColumns), $execute, "*");
        $affected = $sth->rows();

        if ($affected > 1)
            throw new DBDException(sprintf("More then one records updated with query. Transaction rolled back!"));
        else if ($affected == 0)
            throw new DBDException(sprintf("No any records updated."));

        /** @var Entity $class */
        $class = get_class($entity);

        $entity = new $class($sth->fetchRow());

        return $entity;
    }

    /**
     * @param Constraint $constraint
     *
     * @return mixed
     * @throws DBDException
     */
    private function findForeignProperty(Constraint $constraint)
    {
        try {
            /** @var Entity $constraintEntity */
            $constraintEntity = new $constraint->class;
            $fields = array_flip($constraintEntity::map()->getOriginFieldNames());

            /** @var string $foreignColumn name of origin column */
            $foreignColumn = $constraint instanceof Constraint ? $constraint->foreignColumn : $constraint->foreignColumn->name;

            return $fields[$foreignColumn];
        } catch (EntityException $e) {
            throw new DBDException($e->getMessage(), null, null, $e);
        }
    }

    /**
     * Simplifies update procedures. Method makes updates of the rows by giving parameters and prepared values. Returns self instance.
     * Example 1:
     * ```php
     * $update = [
     *     'invoice_date'   => $doc['Date'],
     *     'invoice_number' => [ $doc['Number'] ],
     *     'invoice_amount' => [ $doc['Amount'], 'numeric' ],
     * ];
     * // this will update all rows in a table
     * $sth = $db->update('invoices', $update);
     * echo($sth->rows);
     * Example 2:
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
     * @return DBD
     * @throws DBDException
     */
    public function update(): DBD
    {
        $binds = 0;
        $where = null;
        $return = null;
        $ARGS = func_get_args();
        $table = $ARGS[0];
        $values = $ARGS[1];

        $params = Helper::compileUpdateArgs($values, $this);

        if (func_num_args() > 2) {
            $where = $ARGS[2];
            $binds = substr_count($where, $this->Options->getPlaceHolder());
        }

        // If we set $where with placeholders or we set $return
        if (func_num_args() > 3) {
            for ($i = 3; $i < $binds + 3; $i++) {
                $params['ARGS'][] = $ARGS[$i];
            }
            if (func_num_args() > $binds + 3) {
                $return = $ARGS[func_num_args() - 1];
            }
        }

        return $this->query($this->_compileUpdate($table, $params, $where, $return), $params['ARGS']);
    }

    /**
     * @param string $table
     * @param array $params
     * @param string $where
     * @param string|null $return
     *
     * @return mixed
     * @see update
     * @see Pg::_compileUpdate
     * @see MSSQL::_compileUpdate
     * @see MySQL::_compileUpdate
     * @see OData::_compileUpdate
     */
    abstract protected function _compileUpdate(string $table, array $params, string $where, ?string $return = ""): string;

    /**
     * Common usage when you have an Entity object with filled primary key only and want to fetch all available data
     *
     * @param Entity $entity
     * @param bool $exceptionIfNoRecord
     *
     * @return Entity|null
     * @throws DBDException
     */
    public function entitySelect(Entity &$entity, bool $exceptionIfNoRecord = true): ?Entity
    {
        [$execute, $columns] = $this->getPrimaryKeysForEntity($entity);

        $sth = $this->prepare(sprintf("SELECT * FROM %s.%s WHERE %s", $entity::SCHEME, $entity::TABLE, implode(" AND ", $columns)));
        $sth->execute($execute);

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
     * @param string|null $string
     * @return string
     */
    public function escape(?string $string): string
    {
        return $this->_escape($string);
    }

    /**
     * @param string|null $string
     * @return string
     */
    public function escapeBinary(?string $string): string
    {
        return $this->_escapeBinary($string);
    }

    /**
     * @return Options
     */
    public function getOptions(): Options
    {
        return $this->Options;
    }

    /**
     * @return Config
     */
    public function getConfig(): Config
    {
        return $this->Config;
    }

    /**
     * Simply query and get first column.
     * Usefully when need quickly fetch count(*)
     *
     * @return null|mixed
     * @throws DBDException
     */
    public function select()
    {
        $sth = $this->query(func_get_args());

        if ($sth->rows())
            return $sth->fetch();

        return null;
    }

    /**
     * Fetches first row, reduces result and returns shifted first element
     * @return null|mixed
     */
    public function fetch()
    {
        if ($this->fetch == self::UNDEFINED) {

            if (is_null($this->CacheHolder)) {

                $return = $this->_fetchArray();

                if ($this->Options->isConvertNumeric() || $this->Options->isConvertBoolean()) {
                    $this->_convertTypes($return);
                }

                $this->fetch = $return;
            } else {
                $this->fetch = array_shift($this->CacheHolder->result);
            }
        }
        if (!$this->fetch || !count($this->fetch))
            return null;

        return array_shift($this->fetch);
    }

    /**
     * @return array|bool
     * @see Pg::_fetchArray
     * @see MSSQL::_fetchArray
     * @see MySQL::_fetchArray
     * @see OData::_fetchArray
     * @see fetch
     */
    abstract protected function _fetchArray();

    /**
     * Starts database transaction
     *
     * @return bool
     * @throws DBDException
     */
    public function begin(): bool
    {
        if ($this->inTransaction == true)
            throw new DBDException("Already in transaction");

        $this->connectionPreCheck();
        $this->result = $this->_begin();
        if ($this->result === false)
            throw new DBDException("Can't start transaction: " . $this->_errorMessage());

        $this->inTransaction = true;

        return true;
    }

    /**
     * @return bool true on success begin
     * @see Pg::_begin
     * @see MSSQL::_begin
     * @see MySQL::_begin
     * @see OData::_begin
     * @see begin
     */
    abstract protected function _begin(): bool;

    /**
     * Rolls back a transaction that was begun
     *
     * @return bool
     * @throws DBDException
     */
    public function rollback(): bool
    {
        if ($this->inTransaction) {
            $this->connectionPreCheck();
            $this->result = $this->_rollback();
            if ($this->result === false) {
                throw new DBDException("Can not end transaction: " . $this->_errorMessage());
            }
        } else {
            throw new DBDException("No transaction to rollback");
        }
        $this->inTransaction = false;

        return true;
    }

    /**
     * @return bool true on successful rollback
     * @see Pg::_rollback
     * @see MSSQL::_rollback
     * @see MySQL::_rollback
     * @see OData::_rollback
     * @see rollback
     */
    abstract protected function _rollback(): bool;

    /**
     * Commits a transaction that was begun
     *
     * @return bool
     * @throws DBDException
     */
    public function commit(): bool
    {
        if (!$this->isConnected()) {
            throw new DBDException("No connection established yet");
        }
        if ($this->inTransaction) {
            $this->result = $this->_commit();
            if ($this->result === false)
                throw new DBDException("Can not commit transaction: " . $this->_errorMessage());
        } else {
            throw new DBDException("No transaction to commit");
        }
        $this->inTransaction = false;

        return true;
    }

    /**
     * @return bool true on success commit
     * @see Pg::_commit
     * @see MSSQL::_commit
     * @see MySQL::_commit
     * @see OData::_commit
     * @see commit
     */
    abstract protected function _commit(): bool;
}
