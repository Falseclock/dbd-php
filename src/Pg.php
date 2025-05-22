<?php
/**
 * PostgreSQL driver
 *
 * @author    Nurlan Mukhanov <nurike@gmail.com>
 * @copyright 2020 Nurlan Mukhanov
 * @license   https://en.wikipedia.org/wiki/MIT_License MIT License
 * @link      https://github.com/Falseclock/dbd-php
 * @noinspection PhpComposerExtensionStubsInspection
 */

declare(strict_types=1);

namespace DBD;

use DBD\Common\Bind;
use DBD\Common\DBDException;
use DBD\Entity\Primitives\NumericPrimitives;
use DBD\Entity\Primitives\StringPrimitives;
use DBD\Helpers\ConversionMap;
use DBD\Helpers\InsertArguments;
use DBD\Helpers\UpdateArguments;
use DBD\Tests\Pg\PgNamedTest;
use DBD\Tests\Pg\PgQueryTest;
use DBD\Tests\Pg\PgTransactionTest;
use Exception;
use Throwable;

class Pg extends DBD
{
    public const CAST_FORMAT_INSERT = "?::%s";
    public const CAST_FORMAT_UPDATE = "%s = ?::%s";

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
        $dsn .= "application_name={$this->Options->getApplicationName()}";

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
     * @see PgConnectionTest
     */
    protected function _connect(): void
    {
        try {
            $this->resourceLink = pg_connect($this->Config->getDsn()) or throw new DBDException(sprintf("Connection to %s:%s with username '%s' failed",$this->Config->getHost(), $this->Config->getPort(), $this->Config->getUsername()));
        } catch (Throwable $t) {
            throw new DBDException($t->getMessage());
        }
    }

    /**
     * @return bool
     * @throws DBDException
     * @see PgTransactionTest::testInTransaction()
     */
    protected function _inTransaction(): bool
    {
        switch ($this->getTransactionState()) {
            case PGSQL_TRANSACTION_IDLE:
                return false;
            case PGSQL_TRANSACTION_INTRANS:
                // @codeCoverageIgnoreStart
            case PGSQL_TRANSACTION_INERROR:
            case PGSQL_TRANSACTION_ACTIVE:
                // @codeCoverageIgnoreEnd
                return true;
            // @codeCoverageIgnoreStart
            case PGSQL_TRANSACTION_UNKNOWN:
            default:
                throw new DBDException ("Transaction state is unknown");
            // @codeCoverageIgnoreEnd
        }
    }

    /**
     * Returns current transaction status
     *
     * @return int
     */
    private function getTransactionState(): int
    {
        return pg_transaction_status($this->resourceLink);
    }

    /**
     * Sends BEGIN; command
     *
     * @return bool
     * @throws DBDException
     * @inheritdoc
     * @see PgTransactionTest::testBegin()
     */
    protected function _begin(): bool
    {
        switch ($this->getTransactionState()) {
            case PGSQL_TRANSACTION_IDLE:
                return $this->_query("BEGIN") != null;
            case PGSQL_TRANSACTION_INTRANS:
                throw new DBDException ("Connection is idle, in a valid transaction block");
            case PGSQL_TRANSACTION_INERROR:
                throw new DBDException ("Connection is idle, in a failed transaction block");
            // @codeCoverageIgnoreStart
            case PGSQL_TRANSACTION_ACTIVE:
                throw new DBDException ("Transaction command is in progress and not yet completed");
            case PGSQL_TRANSACTION_UNKNOWN:
            default:
                throw new DBDException ("Transaction state is unknown");
            // @codeCoverageIgnoreEnd
        }
    }

    /**
     * @param $statement
     *
     * @return false|resource
     * @inheritDoc
     * @see PgQueryTest
     */
    protected function _query($statement)
    {
        try {
            return pg_query($this->resourceLink, $statement);
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Sends COMMIT; command
     *
     * @return bool
     * @throws DBDException
     * @see PgTransactionTest::testCommit()
     */
    protected function _commit(): bool
    {
        switch ($this->getTransactionState()) {
            case PGSQL_TRANSACTION_INTRANS:
                return $this->_query("COMMIT") !== null;
            case PGSQL_TRANSACTION_INERROR:
                throw new DBDException ("Commit not possible, in a failed transaction block");
            // @codeCoverageIgnoreStart
            case PGSQL_TRANSACTION_IDLE:
                throw new DBDException ("No transaction to commit");
            case PGSQL_TRANSACTION_ACTIVE:
                throw new DBDException ("Transaction command is in progress and not yet completed");
            case PGSQL_TRANSACTION_UNKNOWN:
            default:
                throw new DBDException ("Transaction state is unknown");
            // @codeCoverageIgnoreEnd
        }
    }

    /**
     * Sends ROLLBACK; command
     *
     * @return bool
     * @throws DBDException
     * @see PgTransactionTest::testRollback()
     */
    protected function _rollback(): bool
    {
        switch ($this->getTransactionState()) {
            case PGSQL_TRANSACTION_INERROR:
            case PGSQL_TRANSACTION_INTRANS:
                return $this->_query("ROLLBACK") !== null;
            case PGSQL_TRANSACTION_IDLE:
                throw new DBDException ("There is no transaction in progress");
            // @codeCoverageIgnoreStart
            case PGSQL_TRANSACTION_ACTIVE:
                throw new DBDException ("Transaction command is in progress and not yet completed");
            case PGSQL_TRANSACTION_UNKNOWN:
            default:
                throw new DBDException ("Transaction state is unknown");
            // @codeCoverageIgnoreEnd
        }
    }

    /**
     * Returns the number of tuples (instances/records/rows) affected by INSERT, UPDATE, and DELETE queries.
     *
     * @return int
     * @see PgRowsTest
     */
    protected function _rows(): int
    {
        return pg_affected_rows($this->result);
    }

    /**
     * Compiles INSERT query
     *
     * @param string $table
     * @param InsertArguments $insert
     * @param string|null $return
     *
     * @return string
     * @inheritdoc
     * @see PgInsertTest::testInsert()
     * @noinspection SqlNoDataSourceInspection
     */
    protected function _compileInsert(string $table, InsertArguments $insert, ?string $return = null): string
    {
        $return = $return ? sprintf(" RETURNING %s", $return) : null;

        return "INSERT INTO " . $table . " (" . implode(", ", $insert->columns) . ") VALUES (" . implode(", ", $insert->values) . ") " . $return;
    }

    /**
     * Compiles UPDATE query
     *
     * @param string $table
     * @param UpdateArguments $updateArguments
     * @param string|null $where
     * @param string|null $return
     *
     * @return string
     * @inheritdoc
     * @noinspection SqlWithoutWhere
     * @see PgUpdateTest
     */
    protected function _compileUpdate(string $table, UpdateArguments $updateArguments, ?string $where = null, ?string $return = null): string
    {
        $return = $return ? sprintf(" RETURNING %s", $return) : null;
        $where = $where ? sprintf(" WHERE %s", $where) : null;

        return "UPDATE " . $table . " SET " . implode(", ", $updateArguments->columns) . $where . $return;
    }

    /**
     * Converts integer, double, float and boolean values to corresponding PHP types. By default Postgres returns them as string
     *
     * @param $data
     * @inheritdoc
     * @see PgConvertTypesTest::testConvertTypes()
     */
    protected function _convertTypes(&$data): void
    {
        if (is_iterable($data)) {
            if (is_null($this->conversionMap))
                $this->buildConversionMap($data);

            foreach ($data as $key => &$value) {
                if ($this->Options->isConvertNumeric()) {
                    if (in_array($key, $this->conversionMap->floats))
                        if (!is_null($value))
                            $value = floatval($value);
                    if (in_array($key, $this->conversionMap->integers))
                        if (!is_null($value))
                            $value = intval($value);
                }
                if ($this->Options->isConvertBoolean()) {
                    if (in_array($key, $this->conversionMap->booleans)) {
                        if ($value === 't')
                            $value = true;
                        else if ($value === 'f')
                            $value = false;
                    }
                }
            }
        }
    }

    /**
     * @param array $resultRow
     * @return void
     * @see https://github.com/postgres/postgres/blob/1722d5eb05d8e5d2e064cd1798abcae4f296ca9d/src/include/catalog/pg_type.dat#L32
     */
    private function buildConversionMap(array $resultRow): void
    {
        // OID-ы основных типов (с учётом массивов)
        static $boolOid   = 16;
        static $intOids   = [20, 21, 23, 26, 1005, 1007, 1016];
        static $floatOids = [700, 701, 1021, 1022, 1700, 1231];

        // Собираем все OID-ы полей *одним проходом* и больше к БД не ходим
        $fieldOids = [];
        for ($i = 0, $n = pg_num_fields($this->result); $i < $n; $i++) {
            // pg_field_type_oid() не делает лишний запрос – берёт OID из самого результата
            $fieldOids[$i] = pg_field_type_oid($this->result, $i);
        }

        $this->conversionMap = new ConversionMap();

        foreach ($resultRow as $key => $value) {
            $fieldNumber = is_int($key)
                ? $key
                : pg_field_num($this->result, $key);

            if ($fieldNumber === -1 && is_string($key) && !ctype_lower($key)) {
                $fieldNumber = pg_field_num($this->result, sprintf('"%s"', $key));
            }

            $oid = $fieldOids[$fieldNumber] ?? null;

            if ($oid === $boolOid) {
                $this->conversionMap->addBoolean((string)$key);
            } elseif (in_array($oid, $intOids, true)) {
                $this->conversionMap->addInteger((string)$key);
            } elseif (in_array($oid, $floatOids, true)) {
                $this->conversionMap->addFloat((string)$key);
            }
        }
    }

    /**
     * Closes the non-persistent connection to a PostgreSQL database associated with the given connection resource
     *
     * @return bool
     * @see PgConnectionTest
     * @inheritdoc
     */
    protected function _disconnect(): bool
    {
        return pg_close($this->resourceLink);
    }

    /**
     * Returns the last error message for a given connection.
     *
     * @return string
     */
    protected function _errorMessage(): string
    {
        if ($this->resourceLink) {
            return pg_last_error($this->resourceLink);
        } else {
            // @codeCoverageIgnoreStart
            return pg_last_error();
            // @codeCoverageIgnoreEnd
        }
    }

    /**
     * @param string $uniqueName
     * @param array $arguments
     *
     * @return resource|null
     * @inheritDoc
     * @see PgNamedTest
     */
    protected function _executeNamed(string $uniqueName, array $arguments)
    {
        try {
            $resource = pg_execute($this->resourceLink, $uniqueName, $arguments);
            // @codeCoverageIgnoreStart
        } catch (Exception $e) {
            return null;
        }
        // @codeCoverageIgnoreEnd

        return $resource ?: null;
    }

    /**
     * Returns an array that corresponds to the fetched row (record).
     *
     * @return array|bool
     * @inheritDoc
     */
    protected function _fetchArray()
    {
        return pg_fetch_array($this->result, 0, PGSQL_NUM) ?: false;
    }

    /**
     * Returns an associative array that corresponds to the fetched row (records).
     *
     * @return array|bool
     * @inheritDoc
     */
    protected function _fetchAssoc()
    {
        return pg_fetch_assoc($this->result) ?: false;
    }

    /**
     * @param $uniqueName
     * @param $statement
     *
     * @return bool|null
     * @see Pg::_prepareNamed()
     * @see MySQL::_prepareNamed()
     * @see OData::_prepareNamed()
     * @see MSSQL::_prepareNamed()
     * @inheritDoc
     * @see PgNamedTest
     */
    protected function _prepareNamed(string $uniqueName, string $statement): bool
    {
        try {
            $return = pg_prepare($this->resourceLink, $uniqueName, $statement);
            // @codeCoverageIgnoreStart
        } catch (Exception $e) {
            return false;
        }
        // @codeCoverageIgnoreEnd

        return $return !== false;
    }

    /**
     * @param string $preparedQuery
     * @param Bind $bind
     * @inheritDoc
     * @throws DBDException
     */
    protected function replaceBind(string &$preparedQuery, Bind $bind): void
    {
        switch ($bind->type) {
            case NumericPrimitives::Int16:
            case NumericPrimitives::Int32:
            case NumericPrimitives::Int64:
            case NumericPrimitives::Double:
            case NumericPrimitives::FLOAT:
                if (is_array($bind->value)) {
                    $bind->value = array_map(function ($value) {
                        return (float)$value;
                    }, $bind->value);
                    $preparedQuery = $this->_replaceBind($bind->name, implode(',', $bind->value ?? 'NULL'), $preparedQuery);
                } else {
                    $preparedQuery = $this->_replaceBind($bind->name, (float)$bind->value ?? 'NULL', $preparedQuery);
                }
                break;
            case StringPrimitives::Binary:
                $binary = $this->escapeBinary($bind->value);
                $preparedQuery = $this->_replaceBind($bind->name, $binary ? "'$binary'" : 'NULL', $preparedQuery);
                break;
            default:
                if (is_array($bind->value)) {
                    $value = array_map(array($this, 'escape'), $bind->value);
                    $preparedQuery = $this->_replaceBind($bind->name, implode(',', $value), $preparedQuery);
                } else {
                    $preparedQuery = $this->_replaceBind($bind->name, $bind->value ? $this->escape($bind->value) : 'NULL', $preparedQuery);
                }
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
        return preg_replace('~' . $name . '(::\w+)?(\W)~', sprintf("%s$1$2", $value), $subject);
    }

    /**
     * @param string|null $binaryString
     *
     * @return string|null
     * @inheritdoc
     * @see PgEscapeTest
     */
    protected function _escapeBinary(?string $binaryString): ?string
    {
        if (!is_null($binaryString))
            $binaryString = pg_escape_bytea($binaryString);

        return $binaryString;
    }

    /**
     * Escapes a string for query
     *
     * @param mixed $value
     * @return string
     * @inheritDoc
     * @see PgEscapeTest
     */
    protected function _escape($value): string
    {
        if (is_null($value))
            return "NULL";

        if (is_bool($value))
            return ($value) ? "TRUE" : "FALSE";

        $value = pg_escape_string((string)$value);

        return "'$value'";
    }

    /**
     * @return bool
     * @inheritDoc
     */
    protected function isConnected(): bool
    {
        return is_resource($this->resourceLink) || is_object($this->resourceLink);
    }
}
