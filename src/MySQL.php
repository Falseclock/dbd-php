<?php
/**
 * @author    Nurlan Mukhanov <nurike@gmail.com>
 * @copyright 2020 Nurlan Mukhanov
 * @license   https://en.wikipedia.org/wiki/MIT_License MIT License
 * @link      https://github.com/Falseclock/dbd-php
 * @noinspection PhpComposerExtensionStubsInspection
 * @noinspection SqlWithoutWhere
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
use mysqli;
use mysqli_result;
use Throwable;

class MySQL extends DBD
{
    /** @var mysqli */
    protected $resourceLink;
    /** @var mysqli_result|bool Query result data */
    protected $result;
    /** @var bool */
    private $inTransaction = false;

    /**
     *
     * @return $this|DBD
     * @throws DBDException
     */
    public function connect(): DBD
    {
        if (!$this->Options->isOnDemand()) {
            $this->_connect();
        }

        return $this;
    }

    /**
     * @throws DBDException
     */
    protected function _connect(): void
    {
        try {
            $this->resourceLink = new mysqli($this->Config->getHost(),
                $this->Config->getUsername(),
                $this->Config->getPassword(),
                $this->Config->getDatabase(),
                $this->Config->getPort()
            );
        } catch (Throwable $t) {
            throw new DBDException($t->getMessage());
        }
        //mysqli_autocommit($this->resourceLink, false);
    }

    protected function _rows(): int
    {
        return mysqli_affected_rows($this->resourceLink);
    }

    protected function _begin(): bool
    {
        $result = mysqli_begin_transaction($this->resourceLink);

        if ($result)
            $this->inTransaction = true;

        return $result;
    }

    protected function _commit(): bool
    {
        $result = mysqli_commit($this->resourceLink);
        $this->inTransaction = true;

        return $result;
    }

    /**
     * @param string $table
     * @param InsertArguments $insert
     * @param string|null $return
     * @return string
     */
    protected function _compileInsert(string $table, InsertArguments $insert, ?string $return = null): string
    {
        return "INSERT INTO " . $table . " (" . implode(", ", $insert->columns) . ") VALUES (" . implode(", ", $insert->values) . ") ";
    }

    protected function _compileUpdate(string $table, UpdateArguments $updateArguments, ?string $where = null, ?string $return = null): string
    {
        return "UPDATE $table SET {$updateArguments['COLUMNS']}" . ($where ? " WHERE $where" : "");
    }

    /**
     * @param $data
     * @return void
     * @inheritDoc
     */
    protected function _convertTypes(&$data): void
    {
        if (is_iterable($data)) {
            if (is_null($this->conversionMap))
                $this->buildConversionMap();

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
                        if ((int)$value === 1)
                            $value = true;
                        else if ((int)$value === 0)
                            $value = false;
                    }
                }
            }
        }
    }

    /**
     * @return void
     */
    private function buildConversionMap(): void
    {
        $fields = mysqli_fetch_fields($this->result);

        $this->conversionMap = new ConversionMap();

        foreach ($fields as $field) {
            switch ($field->type) {
                case MYSQLI_TYPE_DECIMAL:
                case MYSQLI_TYPE_NEWDECIMAL:
                case MYSQLI_TYPE_FLOAT:
                case MYSQLI_TYPE_DOUBLE:
                    $this->conversionMap->addFloat((string)$field->name);
                    break;
                case MYSQLI_TYPE_BIT:
                case MYSQLI_TYPE_SHORT:
                case MYSQLI_TYPE_LONG:
                case MYSQLI_TYPE_LONGLONG:
                case MYSQLI_TYPE_INT24:
                case MYSQLI_TYPE_YEAR:
                case MYSQLI_TYPE_ENUM:
                    $this->conversionMap->addInteger((string)$field->name);
                    break;
                case MYSQLI_TYPE_TINY:
                    if ($field->length == 1)
                        $this->conversionMap->addBoolean((string)$field->name);
                    else
                        $this->conversionMap->addInteger((string)$field->name);
            }
        }
    }

    protected function _disconnect(): bool
    {
        $result = mysqli_close($this->resourceLink);

        return $result;
    }

    protected function _errorMessage(): string
    {
        return mysqli_error($this->resourceLink);
    }

    protected function _escape($value): string
    {
        if (is_null($value))
            return "NULL";

        if (is_bool($value))
            return $value ? 'TRUE' : 'FALSE';

        $str = mysqli_real_escape_string($this->resourceLink, (string)$value);

        return "'$str'";
    }

    /**
     * @param string $uniqueName
     * @param array $arguments
     *
     * @return mixed
     * @throws DBDException
     * @see MySQL::_executeNamed
     * @see OData::_executeNamed
     * @see Pg::_executeNamed
     * @see MSSQL::_executeNamed
     */
    protected function _executeNamed(string $uniqueName, array $arguments)
    {
        $query = $this->getPreparedQuery($arguments, true);

        return $this->_query($query);
    }

    /**
     * @throws DBDException
     */
    protected function _query($statement)
    {
        $result = mysqli_query($this->resourceLink, $statement);

        if ($result === false) {
            throw new DBDException($this->resourceLink->error);
        }

        return $result;
    }

    /**
     * @return array|bool
     */
    protected function _fetchArray()
    {
        return mysqli_fetch_array($this->result, MYSQLI_NUM);
    }

    /**
     * @return array|bool
     */
    protected function _fetchAssoc()
    {
        return mysqli_fetch_array($this->result, MYSQLI_ASSOC);
    }

    /**
     * @param string $uniqueName
     *
     * @param string $statement
     *
     * @return mixed
     * @see MSSQL::_prepareNamed
     * @see MySQL::_prepareNamed
     * @see OData::_prepareNamed
     * @see Pg::_prepareNamed
     */
    protected function _prepareNamed(string $uniqueName, string $statement): bool
    {
        self::$preparedStatements[$uniqueName] = $statement;

        return true;
    }

    protected function _rollback(): bool
    {
        $result = mysqli_rollback($this->resourceLink);
        $this->inTransaction = false;

        return $result;
    }

    /**
     * @param string|null $binaryString
     *
     * @return string|null
     */
    protected function _escapeBinary(?string $binaryString): ?string
    {
        $str = mysqli_real_escape_string($this->resourceLink, (string)$binaryString);

        return "'$str'";
    }

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

    protected function _inTransaction(): bool
    {
        return $this->inTransaction;
    }
}
