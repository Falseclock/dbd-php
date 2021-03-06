<?php
/**
 * Pg
 *
 * @author    Nurlan Mukhanov <nurike@gmail.com>
 * @copyright 2020 Nurlan Mukhanov
 * @license   https://en.wikipedia.org/wiki/MIT_License MIT License
 * @link      https://github.com/Falseclock/dbd-php
 * @noinspection PhpComposerExtensionStubsInspection
 */

declare(strict_types=1);

namespace DBD;

use DBD\Base\Bind;
use DBD\Common\DBDException;
use DBD\Entity\Primitive;
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
     * @return bool
     */
    protected function _begin(): bool
    {
        return $this->_query("BEGIN") != null;
    }

    /**
     *
     * @inheritDoc
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
     * @param string|null $return
     *
     * @return string
     */
    protected function _compileInsert(string $table, array $params, ?string $return = ""): string
    {
        return "INSERT INTO $table ({$params['COLUMNS']}) VALUES ({$params['VALUES']})" . ($return ? " RETURNING {$return}" : "");
    }

    /**
     * Compiles UPDATE query
     *
     * @param string $table
     * @param array $params
     * @param string $where
     * @param string|null $return
     *
     * @return string
     */
    protected function _compileUpdate(string $table, array $params, string $where, ?string $return = ""): string
    {
        /** @noinspection SqlWithoutWhere */
        return "UPDATE $table SET {$params['COLUMNS']}" . ($where ? " WHERE $where" : "") . ($return ? " RETURNING {$return}" : "");
    }

    /**
     * Converts integer, double, float and boolean values to corresponding PHP types. By default Postgres returns them as string
     *
     * @param $data
     *
     * TODO: in case of fetchRowSet do not get each time and use static variable
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
     * @throws DBDException
     */
    protected function _dump(string $preparedQuery, string $fileName, string $delimiter, string $nullString, bool $showHeader, string $tmpPath): string
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
     * @param $uniqueName
     * @param $arguments
     *
     * @return mixed|null
     * @inheritDoc
     */
    protected function _executeNamed($uniqueName, $arguments)
    {
        try {
            $return = pg_execute($this->resourceLink, (string)$uniqueName, $arguments);
        } catch (Exception $e) {
            return null;
        }

        return $return ?: null;
    }

    /**
     * Returns an array that corresponds to the fetched row (record).
     *
     * @return array|bool
     * @inheritDoc
     */
    protected function _fetchArray()
    {
        return pg_fetch_array($this->result, 0, PGSQL_NUM);
    }

    /**
     * Returns an associative array that corresponds to the fetched row (records).
     *
     * @return array|bool
     * @inheritDoc
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
     * @see MSSQL::_prepareNamed
     * @see MySQL::_prepareNamed
     * @see OData::_prepareNamed
     * @see Pg::_prepareNamed
     * @inheritDoc
     */
    protected function _prepareNamed(string $uniqueName, string $statement): bool
    {
        $return = false;
        try {
            $return = pg_prepare($this->resourceLink, $uniqueName, $statement);
        } catch (Exception $e) {
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
        return $this->_query("ROLLBACK") !== null;
    }

    /**
     * @param string $preparedQuery
     * @param Bind $bind
     * @inheritDoc
     */
    protected function replaceBind(string &$preparedQuery, Bind $bind): void
    {
        switch ($bind->type) {
            case Primitive::Int16:
            case Primitive::Int32:
            case Primitive::Int64:
                if (is_array($bind->value))
                    $preparedQuery = $this->_replaceBind($bind->name, implode(',', $bind->value), $preparedQuery);
                else
                    $preparedQuery = $this->_replaceBind($bind->name, $bind->value, $preparedQuery);
                break;
            case Primitive::Binary:
                $binary = $this->_escapeBinary($bind->value);
                $preparedQuery = $this->_replaceBind($bind->name, "'{$binary}'", $preparedQuery);
                break;
            default:
                $preparedQuery = $this->_replaceBind($bind->name, $this->_escape($bind->value), $preparedQuery);
        }
    }

    /**
     * @param $name
     * @param $value
     * @param $subject
     * @return string|string[]|null
     */
    private function _replaceBind($name, $value, $subject)
    {
        return preg_replace('~' . $name . '(::\w+)?(\s|\t|]|\))~', "{$value}$1$2", $subject);
    }

    /**
     * @param string|null $binaryString
     *
     * @return string|null
     */
    protected function _escapeBinary(?string $binaryString): ?string
    {
        if (!is_null($binaryString))
            $binaryString = pg_escape_bytea($binaryString);

        return $binaryString;
    }

    /**
     * Escapes a string for querying the database.
     *
     * @param mixed $string
     * @inheritDoc
     * @return string
     */
    protected function _escape($string): string
    {
        if (!isset($string))
            return "NULL";

        if (is_bool($string))
            return ($string) ? "TRUE" : "FALSE";

        $string = pg_escape_string((string)$string);

        return "'$string'";
    }
}
