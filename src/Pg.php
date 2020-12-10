<?php
/**
 * Pg
 *
 * @author    Nurlan Mukhanov <nurike@gmail.com>
 * @copyright 2020 Nurlan Mukhanov
 * @license   https://en.wikipedia.org/wiki/MIT_License MIT License
 * @link      https://github.com/Falseclock/dbd-php
 */

declare(strict_types=1);

namespace DBD;

use DBD\Common\DBDException;
use Exception;

/**
 * Class Pg
 *
 * @package DBD
 */
class Pg extends DBD
{
    const CAST_FORMAT_INSERT = "?::%s";
    const CAST_FORMAT_UPDATE = "%s = ?::%s";

    /**
     * Setup connection to the resource
     *
     * @return Pg
     * @throws DBDException
     */
    public function connect(): DBD
    {

        $dsn = "host={$this->Config->getHost()} ";
        $dsn .= "dbname={$this->Config->getDatabase()} ";
        $dsn .= $this->Config->getUsername() ? "user={$this->Config->getUsername()} " : "";
        $dsn .= $this->Config->getPassword() ? "password={$this->Config->getPassword()} " : "";
        $dsn .= $this->Config->getPort() ? "port={$this->Config->getPort()} " : "";
        $dsn .= "application_name={$this->Options->getApplicationName()} ";

        $this->Config->setDsn($dsn);

        if ($this->Options->isOnDemand() === false) {
            $this->_connect();
        }

        return $this;
    }

    /**
     * Do real connection. Can be invoked if OnDemand is set to TRUE
     *
     * @return void
     * @throws DBDException
     */
    protected function _connect(): void
    {
        $this->resourceLink = pg_connect($this->Config->getDsn());

        if (!$this->resourceLink)
            throw new DBDException("Can not connect to PostgreSQL server! ");
    }

    /**
     * returns the number of tuples (instances/records/rows) affected by INSERT, UPDATE, and DELETE queries.
     *
     * @return int
     */
    protected function _rows(): int
    {
        return pg_affected_rows($this->result);
    }

    /**
     * Sends BEGIN; command
     *
     * @return resource
     */
    protected function _begin()
    {
        return $this->_query("BEGIN;");
    }

    /**
     * Executes the query on the specified database connection.
     *
     * @param $statement
     *
     * @return resource|null
     */
    protected function _query($statement)
    {
        $return = null;

        try {
            $return = pg_query($this->resourceLink, $statement);
        } catch (Exception $e) {
        } finally {
            return $return ?: null;
        }
    }

    /**
     * Sends COMMIT; command
     *
     * @return bool
     */
    protected function _commit(): bool
    {
        return $this->_query("COMMIT") !== null;
    }

    /**
     * Compiles INSERT query
     *
     * @param string $table
     * @param array $params
     * @param string $return
     *
     * @return string
     */
    protected function _compileInsert($table, $params, $return = ""): string
    {
        return "INSERT INTO $table ({$params['COLUMNS']}) VALUES ({$params['VALUES']})" . ($return ? " RETURNING {$return}" : "");
    }

    /**
     * Compiles UPDATE query
     *
     * @param string $table
     * @param array $params
     * @param string $where
     * @param string $return
     *
     * @return string
     */
    protected function _compileUpdate($table, $params, $where, $return = "")
    {
        /** @noinspection SqlWithoutWhere */
        return "UPDATE $table SET {$params['COLUMNS']}" . ($where ? " WHERE $where" : "") . ($return ? " RETURNING {$return}" : "");
    }

    /**
     * @param $data
     * @param $type
     *
     * @return array|mixed
     * @throws DBDException
     */
    protected function _convertBoolean(&$data)
    {
        if ($type == 'row') {
            if (isset($data) and is_array($data) and count($data) > 0) {
                for ($i = 0; $i < pg_num_fields($this->result); $i++) {
                    if (pg_field_type($this->result, $i) == 'bool') {
                        $dataKey = pg_field_name($this->result, $i);
                        if (array_keys($data) !== range(0, count($data) - 1)) {
                            $key = $dataKey;
                        } else {
                            $key = $i;
                        }
                        if ($data[$key] == 't') {
                            $data[$key] = true;
                        } else if ($data[$key] == 'f') {
                            $data[$key] = false;
                        } else if ($data[$key] == null) {
                            $data[$key] = null;
                        } else {
                            throw new DBDException("Unexpected boolean value");
                        }
                    }
                }
            }
        }

        return $data;
    }

    /**
     * Converts integer, double, float and boolean values to corresponding PHP types. By default Postgres returns them as string
     *
     * @param $data
     *
     * TODO: in case of fetchrowset do not get each time and use static variable
     */
    protected function _convertTypes(&$data): void
    {
        $numericMap = [
            'int' => 'integer',
            'int2' => 'integer',
            'int4' => 'integer',
            'int8' => 'integer',
            'serial2' => 'integer',
            'serial4' => 'integer',
            'serial8' => 'integer',
            'smallint' => 'integer',
            'bigint' => 'integer',
            'serial' => 'integer',
            'smallserial' => 'integer',
            'bigserial' => 'integer',
            //'numeric'   => 'float',
            //'decimal'     => 'float',
            'real' => 'float',
            'float' => 'float',
            'float4' => 'float',
            'float8' => 'float',
        ];

        if (is_iterable($data)) {

            foreach ($data as $key => &$value) {
                if (is_integer($key))
                    $fieldNumber = $key;
                else
                    $fieldNumber = pg_field_num($this->result, $key);

                // That's a limitation of PQfnumber in libpq-exec
                if ($fieldNumber === -1) {
                    if (is_string($key) and !ctype_lower($key))
                        $fieldNumber = pg_field_num($this->result, sprintf('"%s"', $key));
                }

                $fieldType = pg_field_type($this->result, $fieldNumber);

                if ($this->Options->isConvertNumeric()) {
                    if (array_key_exists($fieldType, $numericMap)) {
                        if (!is_null($value)) {
                            $value = ($numericMap[$fieldType] == 'integer' ? intval($value) : floatval($value));
                        }
                    }
                }
                if ($this->Options->isConvertBoolean()) {
                    if ($fieldType == 'bool') {
                        if ($value == 't')
                            $value = true;
                        else if ($value == 'f')
                            $value = false;
                    }
                }
            }
        }
    }

    /**
     * Closes the non-persistent connection to a PostgreSQL database associated with the given connection resource
     *
     * @return bool
     */
    protected function _disconnect(): bool
    {
        return pg_close($this->resourceLink);
    }

    /**
     * @inheritDoc
     */
    protected function _dump(string $preparedQuery, string $fileName, string $delimiter, string $nullString, bool $showHeader, string $tmpPath)
    {
        $file = realpath($tmpPath) . DIRECTORY_SEPARATOR . $fileName . "." . DBD::CSV_EXTENSION;

        file_put_contents($file, "");
        chmod($file, 0666);

        $showHeader = $showHeader ? 'true' : 'false';

        if ($this->_query("COPY ($preparedQuery) TO '{$file}' (FORMAT csv, DELIMITER  E'{$delimiter}', NULL  E'$nullString', HEADER {$showHeader})") === false)
            throw new DBDException ($this->_errorMessage(), $preparedQuery);

        return $file;
    }

    /**
     * Returns the last error message for a given connection.
     *
     * @return string
     */
    protected function _errorMessage(): string
    {
        if ($this->resourceLink)
            return pg_last_error($this->resourceLink);
        else
            return pg_last_error();
    }

    /**
     * Escapes a string for querying the database.
     *
     * @param mixed $value
     *
     * @return string
     */
    protected function _escape($value): string
    {
        if (!isset($value)) {
            return "NULL";
        }
        /*		if(is_numeric($value)) {
                    return $value;
                }*/
        if (is_bool($value)) {
            return ($value) ? "TRUE" : "FALSE";
        }
        $str = pg_escape_string((string)$value);

        return "'$str'";
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
        try {
            $return = pg_execute($this->resourceLink, $uniqueName, $arguments);
        } catch (Exception $e) {
            return false;
        }

        return $return;
    }

    /**
     * Returns an array that corresponds to the fetched row (record).
     *
     * @return array
     */
    protected function _fetchArray()
    {
        return pg_fetch_array($this->result, 0, PGSQL_NUM);
    }

    /**
     * Returns an associative array that corresponds to the fetched row (records).
     *
     * @return array
     */
    protected function _fetchAssoc()
    {
        return pg_fetch_assoc($this->result);
    }

    /**
     * @param $uniqueName
     * @param $statement
     *
     * @return bool|null
     * @see MSSQL::_prepare
     * @see MySQL::_prepare
     * @see OData::_prepare
     * @see Pg::_prepare
     * @inheritDoc
     */
    protected function _prepare(string $uniqueName, string $statement): bool
    {
        try {
            $return = pg_prepare($this->resourceLink, $uniqueName, $statement);
        } catch (Exception $e) {
            return false;
        }

        return $return !== false;
    }

    /**
     * Sends ROLLBACK; command
     *
     * @return bool
     */
    protected function _rollback(): bool
    {
        return $this->_query("ROLLBACK;") !== null;
    }

    /**
     * @return void
     */
    protected function _setApplicationName(): void
    {
        if (!$this->applicationNameIsSet)
            $this->_query(sprintf("SET application_name TO '%s'", pg_escape_string($this->Options->getApplicationName())));

        $this->applicationNameIsSet = true;
    }

    /**
     * @param string|null $binaryString
     *
     * @return string|null
     */
    protected function _binaryEscape(?string $binaryString): ?string
    {
        if (!is_null($binaryString)) {
            $binaryString = pg_escape_bytea($binaryString);
        }

        return $binaryString;
    }
}
