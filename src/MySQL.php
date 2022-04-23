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
use DBD\Common\CRUD;
use DBD\Common\DBDException;
use DBD\Helpers\Helper;
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
        return mysqli_begin_transaction($this->resourceLink);
    }

    protected function _commit(): bool
    {
        return mysqli_commit($this->resourceLink);
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

    protected function _convertTypes(&$data): void
    {
        // TODO: Implement _convertTypes() method.
    }

    protected function _disconnect(): bool
    {
        return mysqli_close($this->resourceLink);
    }

    protected function _errorMessage(): string
    {
        return mysqli_error($this->resourceLink);
    }

    protected function _escape($value): string
    {
        if (is_null($value))
            return "NULL";

        $str = mysqli_real_escape_string($this->resourceLink, (string)$value);

        return "'$str'";
    }

    /**
     * @param string $uniqueName
     * @param array $arguments
     *
     * @return mixed
     * @see MSSQL::_executeNamed
     * @see MySQL::_executeNamed
     * @see OData::_executeNamed
     * @see Pg::_executeNamed
     */
    protected function _executeNamed(string $uniqueName, array $arguments)
    {
        // TODO: Implement _execute() method.
    }

    /**
     * @return array|bool
     */
    protected function _fetchArray()
    {
        return mysqli_fetch_array($this->resourceLink);
    }

    /**
     * @return array|bool
     */
    protected function _fetchAssoc()
    {
        return mysqli_fetch_assoc($this->result);
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

    protected function _rollback(): bool
    {
        return mysqli_rollback($this->resourceLink);
    }

    /**
     * @param string|null $binaryString
     *
     * @return string|null
     */
    protected function _escapeBinary(?string $binaryString): ?string
    {
        // TODO: Implement _binaryEscape() method.
    }

    protected function replaceBind(string &$preparedQuery, Bind $bind): void
    {
        // TODO: Implement replaceBind() method.
    }

    protected function _inTransaction(): bool
    {
        // TODO: Implement inTransaction() method.
    }
}
