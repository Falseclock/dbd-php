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
use ReflectionException;
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
    /** @var array $preparedStatements */
    private static $preparedStatements = [];
    /** @var Config $Config */
    protected $Config;
    /** @var Options $Options */
    protected $Options;
    /** @var bool $applicationNameIsSet just to set it once */
    protected $applicationNameIsSet = false;
    /** @var string $query SQL query */
    protected $query;
    /** @var resource $resourceLink Database or curl connection resource */
    protected $resourceLink;
    /** @var mixed $result Query result data */
    protected $result;
    /** @var int */
    protected $rows = 0;
    /** @var CacheHolder */
    private $CacheHolder = null;
    /** @var mixed $fetch */
    private $fetch = self::UNDEFINED;
    /** @var bool $inTransaction Stores current transaction state */
    private $inTransaction = false;
    /** @var string $storage This param is used for identifying where data taken from */
    private $storage;
    /** @var int $transactionsIntermediate */
    private $transactionsIntermediate = 0;

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
        if (!isset($this->Config->cacheDriver))
            return;

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

        $sth = $this->prepare($statement);
        $sth->execute($args);

        return $sth;
    }

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
        $class->CacheHolder = null;

        return $class;
    }

    /**
     * Sends a request to execute a prepared statement with given parameters, and waits for the result.
     *
     * @return mixed
     * @throws DBDException
     */
    public function execute()
    {
        // Set result to false
        $this->result = null;
        $this->fetch = self::UNDEFINED;
        $this->storage = null;
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
            } catch (Exception | InvalidArgumentException $e) {
                throw new DBDException("Failed to get from cache: {$e->getMessage()}", $preparedQuery);
            }

            // Cache not empty?
            if ($this->CacheHolder->result !== false) {
                $cost = Debug::me()->endTimer();
                // To avoid errors as result by default is NULL
                $this->result = "cached";
                $this->storage = self::STORAGE_CACHE;
                $this->rows = count($this->CacheHolder->result);
            }
        }

        // If not found in cache, then let's get from DB
        if ($this->result != "cached") {

            $this->connectionPreCheck();

            if ($this->Options->isUseDebug())
                Debug::me()->startTimer();

            if ($this->Options->isPrepareExecute()) {
                $uniqueName = crc32($preparedQuery);

                if (!in_array($uniqueName, self::$preparedStatements)) {
                    self::$preparedStatements[] = $uniqueName;

                    if (!$this->_prepare((string)$uniqueName, $preparedQuery))
                        throw new DBDException ($this->_errorMessage(), $preparedQuery);
                }

                $this->result = $this->_execute($uniqueName, Helper::parseArgs($executeArguments));
            } else {
                // Execute query to the database
                $this->result = $this->_query($preparedQuery);
            }

            $cost = Debug::me()->endTimer();

            if (is_null($this->result))
                throw new DBDException ($this->_errorMessage(), $preparedQuery, $this->Options->isPrepareExecute() ? Helper::parseArgs($executeArguments) : null);

            $this->rows = $this->_rows();
            $this->storage = self::STORAGE_DATABASE;

            // If query from cache
            if (!is_null($this->CacheHolder)) {
                //  As we already queried database we have to set key to NULL
                //  because during internal method invoke (fetchRowSet below) this Driver
                //  will think we have data from cache

                $storedKey = $this->CacheHolder->key;
                $this->CacheHolder->key = null;

                // If we have data from query
                if ($this->rows()) {
                    $this->CacheHolder->result = $this->fetchRowSet();
                } else {
                    // select is empty
                    $this->CacheHolder->result = [];
                }

                // reverting all back, cause we stored data to cache
                $this->result = "cached";
                $this->CacheHolder->key = $storedKey;

                // Setting up our cache
                try {
                    $this->Config->cacheDriver->set($this->CacheHolder->key, $this->CacheHolder->result, $this->CacheHolder->expire);
                } catch (Exception | InvalidArgumentException $e) {
                    throw new DBDException("Failed to store in cache: {$e->getMessage()}", $preparedQuery);
                }
            }
        }

        if (is_null($this->result))
            throw new DBDException($this->_errorMessage(), $preparedQuery);

        if ($this->Options->isUseDebug()) {
            $cost = isset($cost) ? $cost : 0;

            $driver = $this->storage == self::STORAGE_CACHE ? self::STORAGE_CACHE : (new ReflectionClass($this))->getShortName();
            $caller = Helper::caller($this);

            Debug::addQueries(new Query(Helper::cleanSql($this->getPreparedQuery($executeArguments, true)), $cost, $caller[0], Helper::debugMark($cost), $driver)
            );
            Debug::addTotalQueries(1);
            Debug::addTotalCost($cost);
        }

        return $this->result;
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
            throw new DBDException("Execute failed: called with $numberOfArgs bind variables when $binds are needed", $this->query, $executeArguments);

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

        return $preparedQuery;
    }

    /**
     * @param mixed $value
     *
     * @return mixed
     * @see MSSQL::_escape
     * @see MySQL::_escape
     * @see OData::_escape
     * @see Pg::_escape
     * @see getPreparedQuery
     */
    abstract protected function _escape($value): string;

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
     * @param string $uniqueName
     * @param string $statement
     *
     * @return bool|null
     * @see MSSQL::_prepare
     * @see MySQL::_prepare
     * @see OData::_prepare
     * @see Pg::_prepare
     */
    abstract protected function _prepare(string $uniqueName, string $statement): ?bool;

    /**
     * @return string
     * @see MSSQL::_errorMessage
     * @see MySQL::_errorMessage
     * @see OData::_errorMessage
     * @see Pg::_errorMessage
     */
    abstract protected function _errorMessage(): string;

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
    abstract protected function _execute($uniqueName, $arguments);

    /**
     * @param $statement
     *
     * @return resource|null
     * @see MSSQL::_query
     * @see MySQL::_query
     * @see OData::_query
     * @see execute
     * @see Pg::_query
     */
    abstract protected function _query($statement);

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
     * Returns the number of rows in the result
     *
     * @return int
     */
    public function rows(): int
    {
        if (is_null($this->CacheHolder)) {
            return $this->_rows();
        } else {
            return count($this->CacheHolder->result);
        }
    }

    /**
     * @param null $uniqueKey
     *
     * @return array|mixed
     * @throws DBDException
     */
    public function fetchRowSet($uniqueKey = null): array
    {
        $array = [];

        if (is_null($this->CacheHolder)) {
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
        if (is_null($this->CacheHolder)) {
            $return = $this->_fetchAssoc();

            if ($this->Options->isConvertNumeric() || $this->Options->isConvertBoolean())
                $this->_convertTypes($return);

            return $return;
        } else {
            return array_shift($this->CacheHolder->result);
        }
    }

    /**
     * @return mixed
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
    abstract protected function _dump(string $preparedQuery, string $fileName, string $delimiter, string $nullString, bool $showHeader, string $tmpPath);

    /**
     *
     * @param Entity $entity
     *
     * @return bool
     * @throws DBDException
     */
    public function entityDelete(Entity $entity)
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
     */
    private function getPrimaryKeysForEntity(Entity $entity)
    {
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
    }

    /**
     * @param Entity $entity
     *
     * @return Entity
     * @throws EntityException
     * @throws DBDException
     * @throws InvalidArgumentException
     * @throws ReflectionException
     */
    public function entityInsert(Entity $entity): Entity
    {
        $record = [];
        $columns = $entity::map()->getColumns();
        $constraints = $entity::map()->getConstraints();

        // Cycle through all available columns according to Mapper definition
        foreach ($columns as $propertyName => $column) {

            $originName = $column->name;

            if ($column->nullable == false) {

                // Mostly we always define properties for any columns
                if (property_exists($entity, $propertyName)) {
                    if (!isset($entity->$propertyName) and ($column->isAuto === false and !isset($column->defaultValue)))
                        throw new DBDException(sprintf("Property '%s' of %s can't be null according to Mapper annotation", $propertyName, get_class($entity)));

                    // Finally add column to record if it is set
                    if (isset($entity->$propertyName))
                        $record[$originName] = $entity->$propertyName;
                } else {
                    // But sometimes we do not use reference fields in Entity directly, but use them as constraint

                    $columnFound = false;
                    foreach ($constraints as $constraintName => $constraint) {
                        // We have definition of Constraint which is public variable
                        if (property_exists($entity, $constraintName)) {
                            if ($constraint->localColumn->name == $column->name) {
                                $columnFound = true;

                                if (isset($entity->$constraintName)) {
                                    $foreignProperty = $this->findForeignProperty($constraint);

                                    if (isset($entity->$constraintName->$foreignProperty)) {
                                        $record[$originName] = $entity->$constraintName->$foreignProperty;
                                    } else {
                                        if ($column->nullable !== false) {
                                            throw new DBDException(sprintf("Property '%s->%s' of %s can't be null", $constraintName, $foreignProperty, get_class($entity)));
                                        } else {
                                            if (isset($column->defaultValue))
                                                $record[$originName] = $column->defaultValue;
                                        }
                                    }
                                } else {
                                    throw new DBDException(sprintf("Property '%s' of %s not set.", $constraintName, get_class($entity)));
                                }
                            }
                        }
                    }
                    if ($columnFound == false) {
                        throw new DBDException(sprintf("Can't understand how to get value of %s(%s) in %s", $propertyName, $column->name, get_class($entity)));
                    }
                }
            } else {
                // Finally add column to record if it is set
                if (isset($entity->$propertyName)) {
                    if ($column->type->getValue() == Primitive::Binary)
                        $record[$originName] = $this->_binaryEscape($entity->$propertyName);
                    else
                        $record[$originName] = $entity->$propertyName;
                } else {
                    // If value not set and we have some default value, let's define also
                    if ($column->isAuto === false and isset($column->defaultValue)) {
                        $record[$originName] = $column->defaultValue;
                    } else {
                        // В некоторых случаях, мы объявляем констрейнт в маппере, но поле остается protected.
                        // в этом случае у нас отсутствует поле как таковое в объекте, так как мы не можем его вызвать или засэтить,
                        // но в Entity может быть объектное поле, в котором создан инстанс и определен primary key
                        // Типичный пример: таблица ссылается на саму себя, но поле может быть null
                        foreach ($constraints as $constraintName => $constraint) {
                            if ($originName == $constraint->localColumn->name and isset($entity->$constraintName)) {
                                /** @var Entity $constraintClass */
                                $constraintClass = $constraint->class;
                                $constraintPKs = $constraintClass::map()->getPrimaryKey();
                                foreach ($constraintPKs as $keyName => $key) {
                                    $record[$originName] = $entity->$constraintName->$keyName;
                                }
                            }
                        }
                    }
                }
            }
        }

        $sth = $this->insert($entity::table(), $record, "*");

        /** @var Entity $class */
        $class = get_class($entity);

        return new $class($sth->fetchRow());
    }

    /**
     * @param Constraint $constraint
     *
     * @return mixed
     * @throws EntityException
     */
    private function findForeignProperty(Constraint $constraint)
    {
        /** @var Entity $constraintEntity */
        $constraintEntity = new $constraint->class;
        $fields = array_flip($constraintEntity::map()->getOriginFieldNames());

        /** @var string $foreignColumn name of origin column */
        $foreignColumn = $constraint instanceof Constraint ? $constraint->foreignColumn : $constraint->foreignColumn->name;

        return $fields[$foreignColumn];
    }

    /**
     * @param string|null $binaryString
     *
     * @return string|null
     * @see entityInsert
     * @see Pg::_binaryEscape
     * @see MSSQL::_binaryEscape
     * @see MySQL::_binaryEscape
     * @see OData::_binaryEscape
     * @see execute
     */
    abstract protected function _binaryEscape(?string $binaryString): ?string;

    /**
     * Easy insert operation
     *
     * @param string $table
     * @param array $args
     * @param null $return
     *
     * @return DBD
     * @throws DBDException
     */
    public function insert(string $table, array $args, $return = null): DBD
    {
        $params = Helper::compileInsertArgs($args, $this, $this->Options);

        $sth = $this->prepare($this->_compileInsert($table, $params, $return));
        $sth->execute($params['ARGS']);

        return $sth;
    }

    /**
     * @param        $table
     * @param        $params
     * @param string $return
     *
     * @return mixed
     * @see OData::_compileInsert
     * @see Pg::_compileInsert
     * @see MSSQL::_compileInsert
     * @see MySQL::_compileInsert
     * @see insert
     */
    abstract protected function _compileInsert($table, $params, $return = ""): string;

    /**
     *
     * @param Entity $entity
     *
     * @return Entity
     * @throws DBDException
     * @throws EntityException
     * @throws InvalidArgumentException
     * @throws ReflectionException
     */
    public function entityUpdate(Entity &$entity)
    {
        [$execute, $primaryColumns] = $this->getPrimaryKeysForEntity($entity);

        $record = [];
        $columns = $entity::map()->getColumns();
        $constraints = $entity::map()->getConstraints();

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
        try {
            $this->beginIntermediate();
            $sth = $this->update($entity::table(), $record, implode(" AND ", $primaryColumns), $execute, "*");
            $affected = $this->rows() > 0;
            if ($affected > 1) {
                $this->rollbackIntermediate();
                throw new DBDException(sprintf("More then one records updated with query. Transaction rolled back!"));
            } else if ($affected == 0) {
                $this->rollbackIntermediate();
                throw new DBDException(sprintf("No any records updated."));
            }

            $this->commitIntermediate();
        } catch (Throwable $throwable) {
            $this->rollbackIntermediate();
            throw new DBDException($throwable->getMessage());
        }

        /** @var Entity $class */
        $class = get_class($entity);

        $newEntity = new $class($sth->fetchRow());

        // Nobody knows what we updated with this query, so lets' cycle trough all constraints and recheck new values
        foreach ($constraints as $constraintName => $constraint) {
            if (property_exists($entity, $constraintName)) {
                $foreignProperty = $this->findForeignProperty($constraint);
                if ($entity->$constraintName->$foreignProperty != $newEntity->$constraintName->$foreignProperty) {
                    // Reselect data for this constraint if it is references to new record
                    $newEntity->$constraintName = $this->entitySelect($newEntity->$constraintName);
                }
            }
        }

        $entity = $newEntity;

        return $entity;
    }

    /**
     * Begin transaction internally of we don't know was transaction started somewhere else or not
     *
     * @return bool
     * @throws DBDException
     */
    private function beginIntermediate()
    {
        $this->transactionsIntermediate++;

        if ($this->inTransaction == false)
            return $this->begin();
        else
            return true;
    }

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
    abstract protected function _begin();

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
    public function update()
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
     * @param        $table
     * @param        $params
     * @param        $where
     * @param string $return
     *
     * @return mixed
     * @see update
     * @see Pg::_compileUpdate
     * @see MSSQL::_compileUpdate
     * @see MySQL::_compileUpdate
     * @see OData::_compileUpdate
     */
    abstract protected function _compileUpdate($table, $params, $where, $return = "");

    /**
     * @return bool
     * @throws DBDException
     */
    private function rollbackIntermediate()
    {
        if ($this->inTransaction) {
            $this->transactionsIntermediate--;

            if ($this->transactionsIntermediate == 0) {
                return $this->rollback();
            }
        } else {
            throw new DBDException("No transaction to rollback");
        }

        return true;
    }

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
     *  Commit transaction internally of we don't know was transaction started somewhere else or not
     *
     * @return bool
     * @throws DBDException
     */
    private function commitIntermediate()
    {
        $this->transactionsIntermediate--;

        if ($this->transactionsIntermediate == 0) {
            return $this->commit();
        }

        return true;
    }

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

    /**
     * Common usage when you have an Entity object with filled primary key only and want to fetch all available data
     *
     * @param Entity $entity
     * @param bool $exceptionIfNoRecord
     *
     * @return Entity|null
     * @throws EntityException
     * @throws DBDException
     * @throws InvalidArgumentException
     * @throws ReflectionException
     */
    public function entitySelect(Entity &$entity, bool $exceptionIfNoRecord = true)
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
     * @param $string
     * @return string
     */
    public function escape(string $string): string
    {
        return $this->_escape($string);
    }

    /**
     * @return Options
     */
    public function getOptions(): Options
    {
        return $this->Options;
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

        throw new DBDException("Possibly non SELECT query");
    }

    /**
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
        if (!count($this->fetch))
            return null;

        return array_shift($this->fetch);
    }

    /**
     * @return mixed
     * @see Pg::_fetchArray
     * @see MSSQL::_fetchArray
     * @see MySQL::_fetchArray
     * @see OData::_fetchArray
     * @see fetch
     */
    abstract protected function _fetchArray();

    /**
     * @return void
     * @see rows
     * @see Pg::_setApplicationName
     * @see MSSQL::_setApplicationName
     * @see MySQL::_setApplicationName
     * @see OData::_setApplicationName
     * @see execute
     */
    abstract protected function _setApplicationName(): void;
}
