<?php /** @noinspection PhpComposerExtensionStubsInspection */
/**
 * @author    Nurlan Mukhanov <nurike@gmail.com>
 * @copyright 2020 Nurlan Mukhanov
 * @license   https://en.wikipedia.org/wiki/MIT_License MIT License
 * @link      https://github.com/Falseclock/dbd-php
 */

declare(strict_types=1);

namespace DBD;

use DBD\Common\Bind;
use DBD\Common\CacheHolder;
use DBD\Common\Config;
use DBD\Common\CRUD;
use DBD\Common\DBDException;
use DBD\Common\Debug;
use DBD\Common\Options;
use DBD\Common\Query;
use DBD\Entity\Primitives\StringPrimitives;
use DBD\Helpers\ConversionMap;
use DBD\Helpers\Helper;
use DBD\Helpers\InsertArguments;
use DBD\Helpers\UpdateArguments;
use Psr\SimpleCache\InvalidArgumentException;
use ReflectionClass;
use Throwable;

abstract class DBD implements CRUD
{
    public const CAST_FORMAT_INSERT = null;
    public const CAST_FORMAT_UPDATE = null;
    private const STORAGE_CACHE = "Cache";
    private const STORAGE_DATABASE = "database";
    private const UNDEFINED = "UNDEF";
    private const GOT_FROM_CACHE = "GOT_FROM_CACHE";
    /** @var array */
    public static $preparedStatements = [];
    /** @var array queries that's really executed in database */
    public static $executedStatements = [];
    /** @var Config */
    protected $Config;
    /** @var Options */
    protected $Options;
    /** @var string SQL query */
    protected $query;
    /** @var resource Database or curl connection resource */
    protected $resourceLink;
    /** @var resource Query result data */
    protected $result;
    /** @var CacheHolder */
    protected $CacheHolder = null;
    /** @var string This param is used for identifying where data taken from */
    protected $storage;
    /** @var Bind[] */
    protected $binds = [];
    /** @var ConversionMap */
    protected $conversionMap;
    /** @var mixed */
    private $fetch = self::UNDEFINED;

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
    final public function __construct(Config $config, ?Options $options = null)
    {
        $this->Config = $config;
        $this->Options = $options ?? new Options();
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
     * @param null $ttl
     *
     * @return DBD
     * @throws DBDException
     */
    public function cache(string $key, $ttl = null): DBD
    {
        if (isset($this->Config->cacheDriver)) {

            if (!isset($this->query))
                throw new DBDException(CRUD::ERROR_STATEMENT_NOT_PREPARED);

            if (Helper::getQueryType($this->query) != CRUD::READ)
                throw new DBDException(CRUD::ERROR_CACHING_NON_SELECT_QUERY);

            // set hash key
            $this->CacheHolder = new CacheHolder($key);

            if ($ttl !== null)
                $this->CacheHolder->expire = $ttl;
        }

        return $this;
    }

    /**
     * Base and main method to start. Returns self instance of DBD driver
     * ```
     * $db = (new DBD\Pg())->connect($config, $options);
     * ```
     *
     * @return $this
     * @see MSSQL::connect()
     * @see MySQL::connect()
     * @see OData::connect()
     * @see Pg::connect()
     */
    abstract public function connect(): DBD;

    /**
     * Closes a database connection
     *
     * @return bool
     * @throws DBDException
     */
    public function disconnect(): bool
    {
        $result = false;

        if ($this->isConnected()) {
            if ($this->_inTransaction()) {
                throw new DBDException(CRUD::ERROR_UNCOMMITTED_TRANSACTION);
            }
            $result = $this->_disconnect();
            if ($result)
                $this->resourceLink = null;
        }

        return $result;
    }

    /**
     * Check whether connection is established or not
     *
     * @return bool true if var is a resource, false otherwise
     */
    protected function isConnected(): bool
    {
        return !is_null($this->resourceLink);
    }

    /**
     * @return bool
     * @see Pg::_inTransaction()
     * @see MSSQL::_inTransaction()
     * @see MySQL::_inTransaction()
     * @see OData::_inTransaction()
     * @see DBD::inTransaction()
     */
    abstract protected function _inTransaction(): bool;

    /**
     * @return bool true on successful disconnection
     * @see Pg::_disconnect()
     * @see MSSQL::_disconnect()
     * @see MySQL::_disconnect()
     * @see OData::_disconnect()
     * @see DBD::disconnect()
     */
    abstract protected function _disconnect(): bool;

    /**
     * @return bool
     * @see DBD::_inTransaction()
     */
    public function inTransaction(): bool
    {
        $this->connectionPreCheck();
        return $this->_inTransaction();
    }

    /**
     * Check connection existence and does connection if not
     *
     * @return void
     */
    private function connectionPreCheck()
    {
        if (!$this->isConnected()) {
            $this->_connect();
        }
    }

    /**
     * @return void
     * @see Pg::_connect()
     * @see MSSQL::_connect()
     * @see MySQL::_connect()
     * @see OData::_connect()
     * @see DBD::connectionPreCheck()
     */
    abstract protected function _connect(): void;

    /**
     * Just executes query and returns affected rows with the query
     *
     * @return int
     * @throws DBDException
     */
    public function do(): int
    {
        if (!func_num_args()) {
            throw new DBDException(CRUD::ERROR_NO_STATEMENT);
        }

        $prepare = Helper::prepareArguments(func_get_args());

        $sth = $this->query($prepare->statement, $prepare->arguments);
        $this->result = $sth->result;

        return $sth->rows();
    }

    /**
     * Like do method, but return self instance
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
            throw new DBDException(CRUD::ERROR_NO_STATEMENT);

        $prepare = Helper::prepareArguments(func_get_args());

        return $this->prepare($prepare->statement)->execute($prepare->arguments);
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
        $this->conversionMap = null;

        $this->connectionPreCheck();

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
            } catch (Throwable|InvalidArgumentException $t) {
                throw new DBDException($t->getMessage(), $preparedQuery);
            }

            // Cache not empty?
            if (!is_null($this->CacheHolder->result)) {
                if ($this->Options->isUseDebug())
                    $cost = Debug::me()->endTimer();
                // To avoid errors as result by default is NULL
                $this->result = self::GOT_FROM_CACHE;
                $this->storage = self::STORAGE_CACHE;
            }
        }

        // If not found in cache, then let's get from DB
        if ($this->result != self::GOT_FROM_CACHE) {

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
                        throw new DBDException ($this->_errorMessage(), $preparedQuery, Helper::parseArguments($executeArguments));
                }
                $this->result = $this->_executeNamed((string)$uniqueName, Helper::parseArguments($executeArguments));
            } else {
                // Execute query to the database
                $this->result = $this->_query($preparedQuery);
            }
            self::$executedStatements[] = $preparedQuery;

            if ($this->Options->isUseDebug())
                $cost = Debug::me()->endTimer();

            if (is_null($this->result) || $this->result === false)
                throw new DBDException ($this->_errorMessage(), $preparedQuery, Helper::parseArguments($executeArguments));

            $this->storage = self::STORAGE_DATABASE;

            // Now we have to store result in the cache
            if (!is_null($this->CacheHolder)) {

                // Emulating we got it from cache
                $this->CacheHolder->result = $this->fetchRowSet();
                $this->storage = self::STORAGE_CACHE;

                // Setting up our cache
                try {
                    $this->Config->cacheDriver->set($this->CacheHolder->key, $this->CacheHolder->result, $this->CacheHolder->expire);
                } catch (InvalidArgumentException|Throwable $e) {
                    throw new DBDException($e->getMessage(), $preparedQuery);
                }
            }
        }

        if ($this->Options->isUseDebug()) {
            $cost = $cost ?? 0;

            $driver = $this->storage == self::STORAGE_CACHE ? self::STORAGE_CACHE : (new ReflectionClass($this))->getShortName();
            $caller = Helper::caller($this);

            Debug::storeQuery(
                new Query(
                    Helper::cleanSql($this->Options->isPrepareExecute() ? $this->getPreparedQuery($executeArguments, true) : $preparedQuery),
                    $cost,
                    $caller,
                    $driver
                )
            );
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
    protected function getPreparedQuery($ARGS, bool $overrideOption = false): string
    {
        if (is_null($this->query))
            throw new DBDException(self::ERROR_NOT_PREPARED);

        $placeHolder = $this->Options->getPlaceHolder();
        $isPrepareExecute = $this->Options->isPrepareExecute();

        $preparedQuery = $this->query;
        $binds = substr_count($this->query, $placeHolder);
        $executeArguments = Helper::parseArguments($ARGS);

        $numberOfArgs = count($executeArguments);

        if ($binds != $numberOfArgs)
            throw new DBDException(sprintf(CRUD::ERROR_BINDS_MISMATCH, $numberOfArgs, $binds), $this->query, $executeArguments);

        if ($numberOfArgs) {
            $query = str_split($this->query);

            $placeholderPosition = 1;
            foreach ($query as $ind => $str) {
                if ($str == $placeHolder) {
                    if ($isPrepareExecute and !$overrideOption) {
                        $query[$ind] = "\$$placeholderPosition";
                        $placeholderPosition++;
                    } else {
                        $query[$ind] = $this->escape(array_shift($executeArguments));
                    }
                }
            }
            $preparedQuery = implode("", $query);
        }

        foreach ($this->binds as $bind) {
            $this->replaceBind($preparedQuery, $bind);
        }

        return $preparedQuery;
    }

    /**
     * @param mixed|null $value
     * @return string
     * @throws DBDException
     * @see DBD::_escape()
     */
    public function escape($value): string
    {
        if (is_object($value)) {
            throw new DBDException(CRUD::ERROR_OBJECT_ESCAPE);
        }

        if (is_array($value)) {
            throw new DBDException(CRUD::ERROR_ARRAY_ESCAPE);
        }

        return $this->_escape($value);
    }

    /**
     * @param mixed $value
     *
     * @return mixed
     * @see MSSQL::_escape()
     * @see MySQL::_escape()
     * @see OData::_escape()
     * @see Pg::_escape()
     * @see DBD::escape()
     */
    abstract protected function _escape($value): string;

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
     *
     * @param string $uniqueName
     * @param array $arguments
     *
     * @return mixed
     * @see MSSQL::_executeNamed()
     * @see MySQL::_executeNamed()
     * @see OData::_executeNamed()
     * @see Pg::_executeNamed()
     * @see DBD::execute()
     */
    abstract protected function _executeNamed(string $uniqueName, array $arguments);

    /**
     * Executes the query on the specified database connection.
     *
     * @param $statement
     *
     * @return mixed|null
     * @see Pg::_query()
     * @see MSSQL::_query()
     * @see MySQL::_query()
     * @see OData::_query()
     * @see DBD::execute()
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
                        throw new DBDException(sprintf(CRUD::ERROR_KEY_NOT_UNIQUE, $row[$uniqueKey]));
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
                        throw new DBDException(sprintf(CRUD::ERROR_KEY_NOT_UNIQUE, $row[$uniqueKey]));
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
            $associativeArray = $this->_fetchAssoc();

            if ($this->Options->isConvertNumeric() || $this->Options->isConvertBoolean())
                $this->_convertTypes($associativeArray);

            return $associativeArray;
        } else {
            return array_shift($this->CacheHolder->result);
        }
    }

    /**
     * @return array|bool
     * @see Pg::_fetchAssoc()
     * @see MSSQL::_fetchAssoc()
     * @see MySQL::_fetchAssoc()
     * @see OData::_fetchAssoc()
     * @see DBD::fetchRow()
     */
    abstract protected function _fetchAssoc();

    /**
     * @param $data
     *
     * @return void
     * @see Pg::_convertTypes()
     * @see MySQL::_convertTypes()
     * @see OData::_convertTypes()
     * @see MSSQL::_convertTypes()
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
        if (!isset($statement) or empty($statement)) {
            throw new DBDException(CRUD::ERROR_NOTHING_TO_PREPARE);
        }

        $className = get_class($this);
        $class = new $className($this->Config, $this->Options);

        $class->resourceLink = &$this->resourceLink;
        $class->query = $statement;

        return $class;
    }

    /**
     * Returns the number of selected of affected rows in the result.
     * Number of rows stay initial even after fetchRow of fetchRowSet
     *
     * @return int
     * @see PgRowsTest
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
     * @param string|null $dataType
     * @param string|null $column
     * @return $this
     * @throws DBDException
     */
    public function bind(string $paramName, $value, ?string $dataType = StringPrimitives::String, ?string $column = null): DBD
    {
        $this->binds[] = new Bind($paramName, $value, $dataType, $column);
        return $this;
    }

    /**
     * Easy insert operation
     *
     * @param string $table
     * @param array $arguments
     * @param string|null $return
     *
     * @return DBD
     * @throws DBDException
     */
    public function insert(string $table, array $arguments, string $return = null): DBD
    {
        $insert = Helper::compileInsertArguments($arguments, $this);

        $sth = $this->prepare($this->_compileInsert($table, $insert, $return));

        return $sth->execute($insert->arguments);
    }

    /**
     * @param string $table
     * @param InsertArguments $insert
     * @param string|null $return
     *
     * @return string
     * @see OData::_compileInsert()
     * @see Pg::_compileInsert()
     * @see MSSQL::_compileInsert()
     * @see MySQL::_compileInsert()
     * @see DBD::insert()
     */
    abstract protected function _compileInsert(string $table, InsertArguments $insert, ?string $return = null): string;

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
     * while ($row = $sth->fetchRow()) {
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
     * while($row = $sth->fetchRow()) {
     *     printf("Updated invoice with ID=%d and UUID=%s\n", $row['invoice_id'], $row['invoice_uuid']);
     * }
     * ```
     *
     * @return DBD
     * @throws DBDException
     */
    public function update(): DBD
    {
        $placeholdersCount = 0;
        $where = null;
        $return = null;
        $ARGS = func_get_args();
        $table = $ARGS[0];
        $values = $ARGS[1];
        $numberOfArguments = func_num_args();

        $updateArguments = Helper::compileUpdateArgs($values, $this);

        if ($numberOfArguments > 2 && !is_null($ARGS[2])) {
            $where = $ARGS[2];
            $placeholdersCount = substr_count($where, $this->Options->getPlaceHolder());
        }

        // If we set $where with placeholders, or we set $return
        if ($numberOfArguments > 3) {
            // Because we can pass execution arguments as an array, we have to count arguments not by count of passed parameters,
            // but should check what actually inside,
            // for example: ->update('table', [a => 'foo', b => 'var'], 'column1 = ? and column2 = ? and column3 = ?', [1,2], 3, "*")
            $lastCheckedArgument = 3;
            while ($placeholdersCount != 0) {
                for ($lastCheckedArgument = 3; $lastCheckedArgument <= $numberOfArguments; $lastCheckedArgument++) {
                    if ($placeholdersCount == 0)
                        break;
                    if (is_array($ARGS[$lastCheckedArgument])) {
                        foreach ($ARGS[$lastCheckedArgument] as $argument) {
                            if (!is_scalar($argument)) {
                                throw new DBDException(CRUD::ERROR_ARGUMENT_NOT_SCALAR);
                            }
                            $updateArguments->arguments[] = $argument;
                            $placeholdersCount--;
                        }
                    } else {
                        $updateArguments->arguments[] = $ARGS[$lastCheckedArgument];
                        $placeholdersCount--;
                    }
                }
            }
            // Now we have to check do we have
            if ($lastCheckedArgument < $numberOfArguments) {
                $return = $ARGS[$numberOfArguments - 1];
            }
        }

        return $this->query($this->_compileUpdate($table, $updateArguments, $where, $return), $updateArguments->arguments);
    }

    /**
     * @param string $table
     * @param UpdateArguments $updateArguments
     * @param string|null $where
     * @param string|null $return
     *
     * @return mixed
     * @see update
     * @see Pg::_compileUpdate
     * @see MSSQL::_compileUpdate
     * @see MySQL::_compileUpdate
     * @see OData::_compileUpdate
     */
    abstract protected function _compileUpdate(string $table, UpdateArguments $updateArguments, ?string $where, ?string $return = null): string;

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
     * Usefully when need quickly fetch count(*) for example
     *
     * @return null|mixed
     * @throws DBDException
     */
    public function select()
    {
        $sth = $this->query(func_get_args());

        if ($sth->rows()) {
            return $sth->fetch();
        }

        return null;
    }

    /**
     * Fetches first row, reduces result and returns shifted first element
     * @note TODO: False, 0, null
     * @return null|mixed
     */
    public function fetch()
    {
        if ($this->fetch == self::UNDEFINED) {

            if (is_null($this->CacheHolder)) {

                $return = $this->_fetchAssoc();

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
     * @see DBD::_begin()
     */
    public function begin(): bool
    {
        $this->connectionPreCheck();
        $this->result = $this->_begin();

        return $this->result === true;
    }

    /**
     * @return bool true on success begin
     * @see Pg::_begin()
     * @see MSSQL::_begin()
     * @see MySQL::_begin()
     * @see OData::_begin()
     * @see DBD::begin()
     */
    abstract protected function _begin(): bool;

    /**
     * Rolls back a transaction that was begun
     *
     * @return bool
     * @see DBD::_rollback()
     */
    public function rollback(): bool
    {
        $this->connectionPreCheck();
        $this->result = $this->_rollback();

        return $this->result === true;
    }

    /**
     * @return bool true on successful rollback
     * @see Pg::_rollback()
     * @see MSSQL::_rollback()
     * @see MySQL::_rollback()
     * @see OData::_rollback()
     * @see DBD::rollback()
     */
    abstract protected function _rollback(): bool;

    /**
     * Commits a transaction that was begun
     *
     * @return bool
     * @throws DBDException
     * @see PgTransactionTest::testCommitWithoutConnection()
     * @see PgTransactionTest::testCommitWithoutTransaction()
     */
    public function commit(): bool
    {
        if (!$this->isConnected()) {
            throw new DBDException("No connection established yet");
        }

        $this->result = $this->_commit();

        return $this->result == true;
    }

    /**
     * @return bool true on success commit
     * @see Pg::_commit()
     * @see MSSQL::_commit()
     * @see MySQL::_commit()
     * @see OData::_commit()
     * @see DBD::commit()
     */
    abstract protected function _commit(): bool;

    /**
     * @param string|null $binaryString
     * @return string|null
     */
    public function escapeBinary(?string $binaryString): ?string
    {
        if (is_null($binaryString))
            return null;

        return $this->_escapeBinary($binaryString);
    }

    /**
     * @param string|null $binaryString
     *
     * @return string|null
     * @see entityInsert
     * @see Pg::_escapeBinary()
     * @see MSSQL::_escapeBinary()
     * @see MySQL::_escapeBinary()
     * @see OData::_escapeBinary()
     * @see DBD::escapeBinary()
     */
    abstract protected function _escapeBinary(?string $binaryString): ?string;
}
