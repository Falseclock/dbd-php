<?php
/**
 * DBD package
 * MIT License
 * Copyright (C) 2009-2019 by Nurlan Mukhanov <nurike@gmail.com>
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace DBD;

use DateInterval;
use DBD\Base\Config;
use DBD\Base\Debug;
use DBD\Base\Helper;
use DBD\Base\Options;
use DBD\Base\Query;
use DBD\Common\DBDException as Exception;
use DBD\Entity\Common\EntityException;
use DBD\Entity\Constraint;
use DBD\Entity\ConstraintRaw;
use DBD\Entity\Entity;
use Psr\SimpleCache\CacheInterface;
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
	const CSV_EXTENSION    = "csv";
	const STORAGE_CACHE    = "Cache";
	const STORAGE_DATABASE = "database";
	const UNDEFINED        = "UNDEF";
	/** @var CacheInterface|Cache */
	public $CacheDriver;
	/**
	 * @deprecated make private
	 * @var int
	 */
	public $rows = 0;
	/** @var Config $Config */
	protected $Config;
	/** @var Options $Options */
	protected $Options;
	/** @var bool $applicationNameIsSet just to set it once */
	protected $applicationNameIsSet = false;
	/** @var array */
	protected $cache = [
		'key'      => null,
		'result'   => null,
		'compress' => null,
		'expire'   => null,
	];
	/** @var string $query SQL query */
	protected $query;
	/** @var resource $resourceLink Database or curl connection resource */
	protected $resourceLink;
	/** @var resource|string|array $result Query result data */
	protected $result;
	/** @var mixed $fetch */
	private $fetch = self::UNDEFINED;
	/** @var bool $inTransaction Stores current transaction state */
	private $inTransaction = false;
	/** @var array $preparedStatements */
	private static $preparedStatements = [];
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
	 * @param Config       $config
	 * @param Options|null $options
	 */
	final public function __construct(Config $config, Options $options = null) {

		$this->Config = $config;
		$this->CacheDriver = $config->getCacheDriver();

		if(isset($options)) {
			$this->Options = $options;
		}
		else {
			$this->Options = new Options;
		}
	}

	/**
	 * Returns number of affected rows during update or delete
	 * ```
	 * $sth = $db->prepare("DELETE FROM foo WHERE bar = ?");
	 * $sth->execute($someVar);
	 * if ($sth->affectedRows()) {
	 *      // Do something
	 * }
	 * ```
	 *
	 * @return int
	 */
	public function affectedRows() {
		return $this->_affectedRows();
	}

	/**
	 * Starts database transaction
	 *
	 * @return bool
	 * @throws Exception
	 */
	public function begin() {
		if($this->inTransaction == true)
			throw new Exception("Already in transaction");

		$this->connectionPreCheck();
		$this->result = $this->_begin();
		if($this->result === false)
			throw new Exception("Can't start transaction: " . $this->_errorMessage());

		$this->inTransaction = true;

		return true;
	}

	/**
	 * Must be called after statement prepare
	 * ```
	 * $sth = $db->prepare("SELECT bank_id AS id, bank_name AS name FROM banks ORDER BY bank_name ASC");
	 * $sth->cache("AllBanks");
	 * $sth->execute();
	 * ```
	 *
	 * @param string                        $key
	 * @param int|float|DateInterval|string $ttl
	 *
	 * @throws Exception
	 */
	public function cache($key, $ttl = null) {
		if(!isset($this->CacheDriver)) {
			//throw new Exception("CacheDriver not initialized");
			return;
		}
		if(!isset($key) or !$key) {
			throw new Exception("caching failed: key is not set or empty");
		}
		if(!is_string($key)) {
			throw new Exception("key is not string type");
		}
		if(!isset($this->query)) {
			throw new Exception("SQL statement not prepared");
		}

		if(preg_match("/^[\s\t\r\n]*select/i", $this->query)) {
			// set hash key
			$this->cache['key'] = $key;

			if($ttl !== null)
				$this->cache['expire'] = $ttl;
		}
		else {
			throw new Exception("caching failed: current query is not of SELECT type");
		}

		return;
	}

	/**
	 * Commits a transaction that was begun
	 *
	 * @return bool
	 * @throws Exception
	 */
	public function commit() {
		if(!$this->isConnected()) {
			throw new Exception("No connection established yet");
		}
		if($this->inTransaction) {
			$this->result = $this->_commit();
			if($this->result === false)
				throw new Exception("Can not commit transaction: " . $this->_errorMessage());
		}
		else {
			throw new Exception("No transaction to commit");
		}
		$this->inTransaction = false;

		return true;
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
	abstract public function connect();

	/**
	 * Closes a database connection
	 *
	 * @return $this
	 * @throws Exception
	 */
	public function disconnect() {
		if($this->isConnected()) {
			if($this->inTransaction) {
				throw new Exception("Uncommitted transaction state");
			}
			$this->_disconnect();
			$this->resourceLink = null;
		}

		return $this;
	}

	/**
	 * @return int
	 * @throws Exception
	 * @throws InvalidArgumentException
	 * @throws ReflectionException
	 */
	public function do() {
		if(!func_num_args())
			throw new Exception("query failed: statement is not set or empty");

		[ $statement, $args ] = Helper::prepareArgs(func_get_args());

		$sth = $this->query($statement, $args);

		return $sth->rows;
	}

	/**
	 * For simple SQL query, mostly delete or update, when you do not need to get results and only want to know affected rows
	 * Example 1:
	 * ```
	 * $affectedRows = $db->doit("UPDATE table SET column1 = ? WHERE column2 = ?", NULL, 'must be null');
	 * ```
	 * Example 2:
	 * ```
	 * $db->doit("DELETE FROM main_table);
	 * ```
	 *
	 * @return int Number of affected tuples will be stored in $result variable
	 * @throws Exception
	 * @throws InvalidArgumentException
	 * @throws ReflectionException
	 * @deprecated use do
	 */
	public function doIt() {
		if(!func_num_args())
			throw new Exception("query failed: statement is not set or empty");

		[ $statement, $args ] = Helper::prepareArgs(func_get_args());

		$sth = $this->query($statement, $args);

		return $sth->rows;
	}

	/**
	 * Dumping result as CSV file
	 *
	 * @param array  $executeArguments
	 * @param string $fileName
	 * @param string $delimiter
	 * @param string $nullString
	 * @param bool   $header
	 * @param string $tmpPath
	 * @param string $type
	 * @param bool   $utf8
	 *
	 * @return mixed
	 * @throws Exception
	 */
	public function dump(?array $executeArguments = [], $fileName = "dump", $delimiter = "\\t", $nullString = "", $header = true, $tmpPath = "/tmp", $type = "csv", $utf8 = true) {

		$BOM = b"\xEF\xBB\xBF";
		$preparedQuery = $this->getPreparedQuery($executeArguments);

		$filename = $this->_dump($preparedQuery, $fileName, $delimiter, $nullString, $header, $tmpPath);

		header('Content-Description: File Transfer');
		switch(strtolower($type)) {
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

		if($utf8) {
			$file = @fopen($filename, "r");
			$bom = fread($file, 3);
			fclose($file);

			if($bom != $BOM) {
				header('Content-Length: ' . (filesize($filename) + mb_strlen($BOM)));
				echo $BOM;
			}
		}
		else {
			header('Content-Length: ' . filesize($filename));
		}

		readfile($filename);

		unlink($filename);

		exit;
	}

	/**
	 * @noinspection PhpUnused
	 *
	 * @param Entity $entity
	 *
	 * @return bool
	 * @throws EntityException
	 * @throws Exception
	 * @throws InvalidArgumentException
	 * @throws ReflectionException
	 */
	public function entityDelete(Entity $entity) {
		[ $execute, $columns ] = $this->getPrimaryKeysForEntity($entity);

		$sth = $this->prepare(sprintf("DELETE FROM %s.%s WHERE %s", $entity::SCHEME, $entity::TABLE, implode(" AND ", $columns)));
		$sth->execute($execute);

		if($sth->isAffected())
			return true;

		return false;
	}

	/**
	 * @noinspection PhpUnused
	 *
	 * @param Entity $entity
	 *
	 * @return Entity
	 * @throws EntityException
	 * @throws Exception
	 * @throws InvalidArgumentException
	 * @throws ReflectionException
	 */
	public function entityInsert(Entity $entity) {

		$record = [];
		$columns = $entity::map()->getColumns();
		$constraints = $entity::map()->getConstraints();

		// Cycle through all available columns according to Mapper definition
		foreach($columns as $propertyName => $column) {

			$originName = $column->name;

			if($column->nullable == false) {

				// Mostly we always define properties for any columns
				if(property_exists($entity, $propertyName)) {
					if(!isset($entity->$propertyName) and ($column->isAuto === false and !isset($column->defaultValue)))
						throw new Exception(sprintf("Property '%s' of %s can't be null according to Mapper annotation", $propertyName, get_class($entity)));

					// Finally add column to record if it is set
					if(isset($entity->$propertyName))
						$record[$originName] = $entity->$propertyName;
				}
				else {
					// But sometimes we do not use reference fields in Entity directly, but use them as constraint

					$columnFound = false;
					foreach($constraints as $constraintName => $constraint) {
						// We have definition of Constraint which is public variable
						if(property_exists($entity, $constraintName)) {
							if($constraint->localColumn->name == $column->name) {
								$columnFound = true;

								if(isset($entity->$constraintName)) {
									$foreignProperty = $this->findForeignProperty($constraint);

									if(isset($entity->$constraintName->$foreignProperty)) {
										$record[$originName] = $entity->$constraintName->$foreignProperty;
									}
									else {
										if($column->nullable !== false) {
											throw new Exception(sprintf("Property '%s->%s' of %s can't be null", $constraintName, $foreignProperty, get_class($entity)));
										}
										else {
											if(isset($column->defaultValue))
												$record[$originName] = $column->defaultValue;
										}
									}
								}
								else {
									throw new Exception(sprintf("Property '%s' of %s not set.", $constraintName, get_class($entity)));
								}
							}
						}
					}
					if($columnFound == false) {
						throw new Exception(sprintf("Can't understand how to get value of %s(%s) in %s", $propertyName, $column->name, get_class($entity)));
					}
				}
			}
			else {
				// Finally add column to record if it is set
				if(isset($entity->$propertyName)) {
					$record[$originName] = $entity->$propertyName;
				}
				else {
					// If value not set and we have some default value, let's define also
					if($column->isAuto === false and isset($column->defaultValue)) {
						$record[$originName] = $column->defaultValue;
					}
					else {
						// В некоторых случаях, мы объявляем констрейнт в маппере, но поле остается protected.
						// в этом случае у нас отсутствует поле как таковое в объекте, так как мы не можем его вызвать или засэтить,
						// но в Entity может быть объектное поле, в котором создан инстанс и определен primary key
						// Типичный пример: таблица ссылается на саму себя, но поле может быть null
						foreach($constraints as $constraintName => $constraint) {
							if($originName == $constraint->localColumn->name and isset($entity->$constraintName)) {
								/** @var Entity $constraintClass */
								$constraintClass = $constraint->class;
								$constraintPKs = $constraintClass::map()->getPrimaryKey();
								foreach($constraintPKs as $keyName => $key) {
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
	 * Common usage when you have an Entity object with filled primary key only and want to fetch all available data
	 *
	 * @param Entity $entity
	 * @param bool   $exceptionIfNoRecord
	 *
	 * @return Entity|null
	 * @throws EntityException
	 * @throws Exception
	 * @throws InvalidArgumentException
	 * @throws ReflectionException
	 */
	public function entitySelect(Entity &$entity, bool $exceptionIfNoRecord = true) {

		[ $execute, $columns ] = $this->getPrimaryKeysForEntity($entity);

		$sth = $this->prepare(sprintf("SELECT * FROM %s.%s WHERE %s", $entity::SCHEME, $entity::TABLE, implode(" AND ", $columns)));
		$sth->execute($execute);

		if(!$sth->rows()) {
			if($exceptionIfNoRecord)
				throw new Exception(sprintf("No data found for entity %s with ", get_class($entity)));
			else
				return null;
		}
		/** @var Entity $class */
		$class = get_class($entity);

		$entity = new $class($sth->fetchRow());

		return $entity;
	}

	/**
	 * TODO: обновлять поле констрейнта, если оно присутствует в Entity помимо Complex
	 *
	 * @noinspection PhpUnused
	 *
	 * @param Entity $entity
	 *
	 * @return Entity
	 * @throws EntityException
	 * @throws Exception
	 * @throws InvalidArgumentException
	 * @throws ReflectionException
	 */
	public function entityUpdate(Entity &$entity) {
		[ $execute, $primaryColumns ] = $this->getPrimaryKeysForEntity($entity);

		$record = [];
		$columns = $entity::map()->getColumns();
		$constraints = $entity::map()->getConstraints();

		foreach($columns as $propertyName => $column) {

			if($column->nullable === false) {
				if(property_exists($entity, $propertyName)) {
					if(isset($entity->$propertyName))
						$record[$column->name] = $entity->$propertyName;
					else
						throw new Exception(sprintf("Property '%s' of %s can't be null", $propertyName, get_class($entity)));
				}
				else {
					throw new Exception(sprintf("Property '%s' of %s not set", $propertyName, get_class($entity)));
				}
			}
			else {
				if(property_exists($entity, $propertyName)) {
					$record[$column->name] = $entity->$propertyName;
				}
				else {
					// Possibly we got reference constraint field
					foreach($constraints as $constraintName => $constraint) {
						if(property_exists($entity, $constraintName)) {
							if($constraint->localColumn->name == $column->name and isset($entity->$constraintName)) {

								$foreignProperty = $this->findForeignProperty($constraint);

								if(isset($entity->$constraintName->$foreignProperty)) {
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
			$affected = $sth->affectedRows();
			if($affected > 1) {
				$this->rollbackIntermediate();
				throw new Exception(sprintf("More then one records updated with query. Transaction rolled back!"));
			}
			else if($affected == 0) {
				$this->rollbackIntermediate();
				throw new Exception(sprintf("No any records updated."));
			}

			$this->commitIntermediate();
		}
		catch(Throwable $throwable) {
			$this->rollbackIntermediate();
			throw new Exception($throwable->getMessage());
		}

		/** @var Entity $class */
		$class = get_class($entity);

		$newEntity = new $class($sth->fetchRow());

		// Nobody knows what we updated with this query, so lets' cycle trough all constraints and recheck new values
		foreach($constraints as $constraintName => $constraint) {
			if(property_exists($entity, $constraintName)) {
				$foreignProperty = $this->findForeignProperty($constraint);
				if($entity->$constraintName->$foreignProperty != $newEntity->$constraintName->$foreignProperty) {
					// Reselect data for this constraint if it is references to new record
					$newEntity->$constraintName = $this->entitySelect($newEntity->$constraintName);
				}
			}
		}

		$entity = $newEntity;

		return $entity;
	}

	public function escape($string) {
		return $this->_escape($string);
	}

	/**
	 * Sends a request to execute a prepared statement with given parameters, and waits for the result.
	 *
	 * @return mixed
	 * @throws Exception
	 * @throws InvalidArgumentException
	 * @throws ReflectionException
	 * @throws \Exception
	 */
	public function execute() {
		// Set result to false
		$this->result = false;
		$this->fetch = self::UNDEFINED;
		$this->storage = null;
		$executeArguments = func_get_args();
		$preparedQuery = $this->getPreparedQuery($executeArguments);

		//--------------------------------------
		// Is query uses cache?
		//--------------------------------------
		if(isset($this->CacheDriver)) {
			if($this->cache['key'] !== null) {
				// Get data from cache
				if($this->Options->isUseDebug()) {
					Debug::me()->startTimer();
				}
				$this->cache['result'] = $this->CacheDriver->get($this->cache['key']);

				// Cache not empty?
				if($this->cache['result'] !== false) {
					$cost = Debug::me()->endTimer();
					// To avoid errors as result by default is NULL
					$this->result = "cached";
					$this->storage = self::STORAGE_CACHE;
					$this->rows = count($this->cache['result']);
				}
			}
		}

		// If not found in cache, then let's get from DB
		if($this->result != "cached") {

			$this->connectionPreCheck();
			if($this->Options->isUseDebug()) {
				Debug::me()->startTimer();
			}
			if($this->Options->isPrepareExecute()) {
				$uniqueName = crc32($preparedQuery);

				if(!in_array($uniqueName, self::$preparedStatements)) {
					self::$preparedStatements[] = $uniqueName;
					$prepareResult = $this->_prepare($uniqueName, $preparedQuery);

					if($prepareResult === false) {
						throw new Exception ($this->_errorMessage(), $preparedQuery);
					}
				}

				$this->result = $this->_execute($uniqueName, Helper::parseArgs($executeArguments));
			}
			else {
				$this->checkStatement($preparedQuery);
				// Execute query to the database
				$this->result = $this->_query($preparedQuery);
			}
			$cost = Debug::me()->endTimer();

			if($this->result !== false) {
				$this->rows = $this->_numRows();
				$this->storage = self::STORAGE_DATABASE;
			}
			else {
				throw new Exception ($this->_errorMessage(), $preparedQuery, $this->Options->isPrepareExecute() ? Helper::parseArgs($executeArguments) : null);
			}

			// If query from cache
			if($this->cache['key'] !== null) {
				//  As we already queried database we have to set key to NULL
				//  because during internal method invoke (fetchRowSet below) this Driver
				//  will think we have data from cache

				$storedKey = $this->cache['key'];
				$this->cache['key'] = null;

				// If we have data from query
				if($this->rows()) {
					$this->cache['result'] = $this->fetchRowSet();
				}
				else {
					// select is empty
					$this->cache['result'] = [];
				}

				// reverting all back, cause we stored data to cache
				$this->result = "cached";
				$this->cache['key'] = $storedKey;

				// Setting up our cache
				$this->CacheDriver->set($this->cache['key'], $this->cache['result'], $this->cache['expire']);
			}
		}

		if($this->result === false) {
			throw new Exception($this->_errorMessage(), $preparedQuery);
		}

		if($this->Options->isUseDebug()) {
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
	 * @return bool|mixed
	 */
	public function fetch() {
		if($this->fetch == self::UNDEFINED) {

			if($this->cache['key'] === null) {

				$return = $this->_fetchArray();

				if($this->Options->isConvertNumeric() || $this->Options->isConvertBoolean()) {
					$return = $this->convertTypes($return, "row");
				}

				$this->fetch = $return;
			}
			else {
				$this->fetch = array_shift($this->cache['result']);
			}
		}
		if(!count($this->fetch))
			return null;

		return array_shift($this->fetch);
	}

	/**
	 * @noinspection PhpUnused
	 * @return array
	 */
	public function fetchArraySet() {
		$array = [];

		if($this->cache['key'] === null) {
			while($row = $this->fetchRow()) {
				$entry = [];
				foreach($row as $key => $value) {
					$entry[] = $value;
				}
				$array[] = $entry;
			}
		}
		else {
			$cache = $this->cache['result'];
			$this->cache['result'] = [];
			foreach($cache as $row) {
				$entry = [];
				foreach($row as $key => $value) {
					$entry[] = $value;
				}
				$array[] = $entry;
			}
		}

		return $array;
	}

	/**
	 * @return mixed
	 */
	public function fetchRow() {
		if($this->cache['key'] === null) {
			$return = $this->_fetchAssoc();

			if($this->Options->isConvertNumeric() || $this->Options->isConvertBoolean()) {
				return $this->convertTypes($return, "row");
			}

			return $return;
		}
		else {
			return array_shift($this->cache['result']);
		}
	}

	/**
	 * @param null $key
	 *
	 * @return array|mixed
	 */
	public function fetchRowSet($key = null) {
		$array = [];

		if($this->cache['key'] === null) {
			while($row = $this->fetchRow()) {
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

	/**
	 * @return Options
	 */
	public function getOptions(): Options {
		return $this->Options;
	}

	/**
	 * @return array|resource|string
	 * @deprecated
	 */
	public function getResult() {
		return $this->result;
	}

	/**
	 * @return string
	 * @deprecated
	 */
	public function getStorage() {
		return $this->storage;
	}

	/**
	 * Easy insert operation
	 *
	 * @param string $table
	 * @param array  $args
	 * @param null   $return
	 *
	 * @return DBD
	 * @throws Exception
	 * @throws InvalidArgumentException
	 * @throws ReflectionException
	 */
	public function insert($table, $args, $return = null) {
		$params = Helper::compileInsertArgs($args, $this, $this->Options);

		$sth = $this->prepare($this->_compileInsert($table, $params, $return));
		$sth->execute($params['ARGS']);

		return $sth;
	}

	/**
	 * Same as affectedRows but returns boolean
	 *
	 * @return bool
	 */
	public function isAffected() {
		return $this->affectedRows() > 0;
	}

	/**
	 * Creates a prepared statement for later execution
	 *
	 * @param string $statement
	 *
	 * @return $this
	 * @throws Exception
	 */
	public function prepare(string $statement) {
		if(!isset($statement) or empty($statement))
			throw new Exception("prepare failed: statement is not set or empty");

		return $this->extendMe($this, $statement);
	}

	/**
	 * Like doit method, but return self instance
	 * Example 1:
	 * ```
	 * $sth = $db->query("SELECT * FROM invoices");
	 * while ($row = $sth->fetchrow()) {
	 *      //do something
	 * }
	 * ```
	 * Example 2:
	 * ```
	 * $sth = $db->query("UPDATE invoices SET invoice_uuid=?",'550e8400-e29b-41d4-a716-446655440000');
	 * echo($sth->affectedRows());
	 * ```
	 *
	 * @return DBD
	 * @throws Exception
	 * @throws InvalidArgumentException
	 * @throws ReflectionException
	 */
	public function query() {
		if(!func_num_args())
			throw new Exception("query failed: statement is not set or empty");

		[ $statement, $args ] = Helper::prepareArgs(func_get_args());

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
	 * Rolls back a transaction that was begun
	 *
	 * @return bool
	 * @throws Exception
	 */
	public function rollback() {
		if($this->inTransaction) {
			$this->connectionPreCheck();
			$this->result = $this->_rollback();
			if($this->result === false) {
				throw new Exception("Can not end transaction: " . $this->_errorMessage());
			}
		}
		else {
			throw new Exception("No transaction to rollback");
		}
		$this->inTransaction = false;

		return true;
	}

	/**
	 * Returns the number of rows in a database result resource.
	 *
	 * @return int
	 */
	public function rows() {
		if($this->cache['key'] === null) {
			if(preg_match("/^(\s*?)select\s*?.*?\s*?from/is", $this->query)) {
				return $this->_numRows();
			}

			return $this->_affectedRows();
		}
		else {
			return count($this->cache['result']);
		}
	}

	/**
	 * @return null|mixed
	 * @throws Exception
	 * @throws InvalidArgumentException
	 * @throws ReflectionException
	 */
	public function select() {

		$sth = $this->query(func_get_args());

		if($sth->rows()) {
			return $sth->fetch();
		}

		return null;
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
	 * @throws Exception
	 * @throws InvalidArgumentException
	 * @throws ReflectionException
	 */
	public function update() {
		$binds = 0;
		$where = null;
		$return = null;
		$ARGS = func_get_args();
		$table = $ARGS[0];
		$values = $ARGS[1];

		$params = Helper::compileUpdateArgs($values, $this);

		if(func_num_args() > 2) {
			$where = $ARGS[2];
			$binds = substr_count($where, $this->Options->getPlaceHolder());
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
	 * @return int number of updated or deleted rows
	 * @see rows
	 * @see Pg::_affectedRows
	 * @see MSSQL::_affectedRows
	 * @see MySQL::_affectedRows
	 * @see OData::_affectedRows
	 * @see affectedRows
	 */
	abstract protected function _affectedRows();

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
	 * @return bool true on success commit
	 * @see Pg::_commit
	 * @see MSSQL::_commit
	 * @see MySQL::_commit
	 * @see OData::_commit
	 * @see commit
	 */
	abstract protected function _commit();

	/**
	 * @param        $table
	 * @param        $params
	 * @param string $return
	 *
	 * @return mixed
	 * @see OData::_compileInsert
	 * @see insert
	 * @see Pg::_compileInsert
	 * @see MSSQL::_compileInsert
	 * @see MySQL::_compileInsert
	 */
	abstract protected function _compileInsert($table, $params, $return = "");

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
	 * @return DBD
	 * @see Pg::_connect
	 * @see MSSQL::_connect
	 * @see MySQL::_connect
	 * @see OData::_connect
	 * @see connectionPreCheck
	 */
	abstract protected function _connect();

	/**
	 * @param $data
	 * @param $type
	 *
	 * @return mixed
	 * @see MySQL::_convertBoolean
	 * @see OData::_convertBoolean
	 * @see convertTypes
	 * @see Pg::_convertBoolean
	 * @see MSSQL::_convertBoolean
	 */
	abstract protected function _convertBoolean(&$data, $type);

	/**
	 * @param $data
	 * @param $type
	 *
	 * @return mixed
	 * @see MySQL::_convertIntFloat
	 * @see OData::_convertIntFloat
	 * @see convertTypes
	 * @see Pg::_convertIntFloat
	 * @see MSSQL::_convertIntFloat
	 */
	abstract protected function _convertIntFloat(&$data, $type);

	/**
	 * @return bool true on successful disconnection
	 * @see Pg::_disconnect
	 * @see MSSQL::_disconnect
	 * @see MySQL::_disconnect
	 * @see OData::_disconnect
	 * @see disconnect
	 */
	abstract protected function _disconnect();

	/**
	 * @param string $preparedQuery
	 * @param string $fileName
	 * @param string $delimiter
	 * @param string $nullString
	 * @param bool   $showHeader
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
	 * @return string
	 * @see MSSQL::_errorMessage
	 * @see MySQL::_errorMessage
	 * @see OData::_errorMessage
	 * @see Pg::_errorMessage
	 */
	abstract protected function _errorMessage();

	/**
	 * @param $value
	 *
	 * @return mixed
	 * @see MSSQL::_escape
	 * @see MySQL::_escape
	 * @see OData::_escape
	 * @see getPreparedQuery
	 * @see Pg::_escape
	 */
	abstract protected function _escape($value);

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
	 * @return mixed
	 * @see Pg::_fetchArray
	 * @see MSSQL::_fetchArray
	 * @see MySQL::_fetchArray
	 * @see OData::_fetchArray
	 * @see fetch
	 */
	abstract protected function _fetchArray();

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
	 * @return mixed
	 * @see rows
	 * @see Pg::_numRows
	 * @see MSSQL::_numRows
	 * @see MySQL::_numRows
	 * @see OData::_numRows
	 * @see execute
	 */
	abstract protected function _numRows();

	/**
	 * @param string $uniqueName
	 * @param string $statement
	 *
	 * @return mixed
	 * @see MSSQL::_prepare
	 * @see MySQL::_prepare
	 * @see OData::_prepare
	 * @see Pg::_prepare
	 */
	abstract protected function _prepare($uniqueName, $statement);

	/**
	 * @param $statement
	 *
	 * @return mixed
	 * @see MSSQL::_query
	 * @see MySQL::_query
	 * @see OData::_query
	 * @see execute
	 * @see Pg::_query
	 */
	abstract protected function _query($statement);

	/**
	 * @return bool true on successful rollback
	 * @see Pg::_rollback
	 * @see MSSQL::_rollback
	 * @see MySQL::_rollback
	 * @see OData::_rollback
	 * @see rollback
	 */
	abstract protected function _rollback();

	/**
	 * @return void
	 * @see rows
	 * @see Pg::_setApplicationName
	 * @see MSSQL::_setApplicationName
	 * @see MySQL::_setApplicationName
	 * @see OData::_setApplicationName
	 * @see execute
	 */
	abstract protected function _setApplicationName();

	/**
	 * Check whether connection is established or not
	 *
	 * @return bool true if var is a resource, false otherwise
	 */
	protected function isConnected() {
		return is_resource($this->resourceLink);
	}

	/**
	 * @param $statement
	 *
	 * @return string|null
	 */
	private function getQueryType($statement) {
		if(is_array($statement))
			$statement = key($statement);

		$matches = null;
		if(!preg_match('/^\s*(SELECT|INSERT|REPLACE|UPDATE|DELETE)\b/si', $statement, $matches))
			return null;

		return strtoupper(trim($matches[1]));
	}

	/**
	 * Begin transaction internally of we don't know was transaction started somewhere else or not
	 *
	 * @return bool
	 * @throws Exception
	 */
	private function beginIntermediate() {

		$this->transactionsIntermediate++;

		if($this->inTransaction == false)
			return $this->begin();
		else
			return true;
	}

	/**
	 * @param string $statement
	 */
	private function checkStatement(string $statement) {
		if($this->applicationNameIsSet)
			return;

		if($this->Options->isSetApplicationOnDelete() or $this->Options->isSetApplicationOnInsert() or $this->Options->isSetApplicationOnUpdate()) {
			if(in_array($this->getQueryType($statement), [ 'INSERT', 'UPDATE', 'DELETE' ]))
				$this->_setApplicationName();
		}
	}

	/**
	 *  Commit transaction internally of we don't know was transaction started somewhere else or not
	 *
	 * @return bool
	 * @throws Exception
	 */
	private function commitIntermediate() {

		$this->transactionsIntermediate--;

		if($this->transactionsIntermediate == 0) {
			return $this->commit();
		}

		return true;
	}

	/**
	 * Check connection existence and do connection if not
	 *
	 * @return void
	 */
	private function connectionPreCheck() {
		if(!$this->isConnected()) {
			$this->_connect();
		}
	}

	/**
	 * @param $data
	 * @param $type
	 *
	 * @return mixed
	 */
	private function convertTypes(&$data, $type) {
		if($this->Options->isConvertNumeric()) {
			$this->_convertIntFloat($data, $type);
		}
		if($this->Options->isConvertBoolean()) {
			$this->_convertBoolean($data, $type);
		}

		return $data;
	}

	/**
	 * Copies object variables after extended class construction
	 * TODO: may be clone?
	 *
	 * @param DBD    $context
	 * @param string $statement
	 *
	 * @return DBD
	 */
	final private function extendMe(DBD $context, string $statement) {

		$className = get_class($context);

		/** @var DBD $class */
		$class = new $className($context->Config, $context->Options);

		$class->Config = &$context->Config;
		$class->Options = &$context->Options;
		$class->resourceLink = &$context->resourceLink;
		$class->CacheDriver = &$context->CacheDriver;
		$class->inTransaction = &$context->inTransaction;
		$class->query = $statement;

		return $class;
	}

	/**
	 * @param Constraint $constraint
	 *
	 * @return mixed
	 * @throws EntityException
	 */
	private function findForeignProperty(Constraint $constraint) {
		/** @var Entity $constraintEntity */
		$constraintEntity = new $constraint->class;
		$fields = array_flip($constraintEntity::map()->getOriginFieldNames());

		/** @var string $foreignColumn name of origin column */
		$foreignColumn = $constraint instanceof ConstraintRaw ? $constraint->foreignColumn : $constraint->foreignColumn->name;

		return $fields[$foreignColumn];
	}

	/**
	 * @param      $ARGS
	 * @param bool $overrideOption
	 *
	 * @return string
	 * @throws Exception
	 */
	private function getPreparedQuery($ARGS, $overrideOption = false) {
		$placeHolder = $this->Options->getPlaceHolder();
		$isPrepareExecute = $this->Options->isPrepareExecute();

		$preparedQuery = $this->query;
		$binds = substr_count($this->query, $placeHolder);
		$executeArguments = Helper::parseArgs($ARGS);

		$numberOfArgs = count($executeArguments);

		if($binds != $numberOfArgs) {
			throw new Exception("Execute failed: called with $numberOfArgs bind variables when $binds are needed", $this->query);
		}

		if($numberOfArgs) {
			$query = str_split($this->query);

			$placeholderPosition = 1;
			foreach($query as $ind => $str) {
				if($str == $placeHolder) {
					if($isPrepareExecute and !$overrideOption) {
						$query[$ind] = "\${$placeholderPosition}";
						$placeholderPosition++;
					}
					else {
						$query[$ind] = $this->_escape(array_shift($executeArguments));
					}
				}
			}
			$preparedQuery = implode("", $query);
		}

		return $preparedQuery;
	}

	/**
	 * @param Entity $entity
	 *
	 * @return array
	 * @throws EntityException
	 * @throws Exception
	 */
	private function getPrimaryKeysForEntity(Entity $entity) {
		$keys = $entity::map()->getPrimaryKey();

		if(!count($keys))
			throw new Exception(sprintf("Entity %s does not have any defined primary key", get_class($entity)));

		$columns = [];
		$execute = [];

		$placeHolder = $this->Options->getPlaceHolder();

		foreach($keys as $keyName => $column) {
			if(!isset($entity->$keyName))
				throw new Exception(sprintf("Value of %s->%s, which is primary key column, is null", get_class($entity), $keyName));

			$execute[] = $entity->$keyName;
			$columns[] = "{$column->name} = {$placeHolder}";
		}

		return [ $execute, $columns ];
	}

	/**
	 * @return bool
	 * @throws Exception
	 */
	private function rollbackIntermediate() {
		if($this->inTransaction) {
			$this->transactionsIntermediate--;

			if($this->transactionsIntermediate == 0) {
				return $this->rollback();
			}
		}
		else {
			throw new Exception("No transaction to rollback");
		}

		return true;
	}
}
