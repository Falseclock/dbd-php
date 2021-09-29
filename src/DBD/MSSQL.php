<?php
/**
 * MSSQL
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
use DBD\Common\DBDException as Exception;
use DBD\Utils\InsertArguments;
use DBD\Utils\UpdateArguments;

class MSSQL extends DBD
{
    const SQLSRV_CURSOR_CLIENT_BUFFERED = 'buffered';
    const SQLSRV_CURSOR_DYNAMIC = 'dynamic';
    const SQLSRV_CURSOR_FORWARD = 'forward';
    const SQLSRV_CURSOR_KEYSET = 'keyset';
    const SQLSRV_CURSOR_STATIC = 'static';
    //
    protected $connectionInfo = [];
    protected $cursorType = null;

    /**
     *
     * @return MSSQL
     * @throws Exception
     */
    public function connect(): DBD
    {

        if ($this->Config->getDatabase())
            $this->connectionInfo['Database'] = $this->Config->getDatabase();

        if ($this->Config->getUsername())
            $this->connectionInfo['UID'] = $this->Config->getUsername();

        if ($this->Config->getPassword() != null)
            $this->connectionInfo['PWD'] = $this->Config->getPassword();

        if ($this->Options->isOnDemand() == false) {
            $this->_connect();
        }

        return $this;
    }

    /**
     * Do real connection. Can be invoked if OnDemand is set to TRUE
     *
     * @return void
     * @throws Exception
     */
    protected function _connect(): void
    {
        $this->resourceLink = sqlsrv_connect($this->Config->getHost(), $this->connectionInfo);

        if (!$this->resourceLink)
            throw new Exception($this->_errorMessage());
    }

    protected function _errorMessage(): string
    {
        $errors = sqlsrv_errors();

        return preg_replace('/^(\[.*\])+?/', '', $errors[0]['message']) . " SQL State: " . $errors[0]['SQLSTATE'] . ". Code: " . $errors[0]['code'];
    }

    protected function _rows(): int
    {
        $return = sqlsrv_rows_affected($this->result);

        if ($return === false) {
            throw new Exception($this->_errorMessage(), $this->query);
        }
        if ($return === -1) {
            return 0;
        }

        return $return;
    }

    protected function _begin(): bool
    {
        return sqlsrv_begin_transaction($this->resourceLink);
    }

    protected function _commit(): bool
    {
        return sqlsrv_commit($this->resourceLink);
    }

    protected function _compileInsert(string $table, InsertArguments $insert, ?string $return = null): string
    {
        return sprintf("INSERT INTO %s (%s) VALUES (%s)", $table, implode(", ", $insert->columns), implode(", ", $insert->values));
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
        return sqlsrv_close($this->resourceLink);
    }

    protected function _escape($value): string
    {
        if (!isset($value))
            return "NULL";

        if (is_numeric($value))
            return $value;

        $nonDisplayAble = [
            '/%0[0-8bcef]/',
            // url encoded 00-08, 11, 12, 14, 15
            '/%1[0-9a-f]/',
            // url encoded 16-31
            '/[\x00-\x08]/',
            // 00-08
            '/\x0b/',
            // 11
            '/\x0c/',
            // 12
            '/[\x0e-\x1f]/'
            // 14-31
        ];
        foreach ($nonDisplayAble as $regex)
            $value = preg_replace($regex, '', $value);

        $value = str_replace("'", "''", $value);

        return "'$value'";
    }

    /**
     * @param $uniqueName
     * @param $arguments
     *
     * @return mixed
     * @see MSSQL::_executeNamed
     * @see MySQL::_executeNamed
     * @see OData::_executeNamed
     * @see Pg::_executeNamed
     */
    protected function _executeNamed($uniqueName, $arguments)
    {
        // TODO: Implement _execute() method.
    }

    /**
     * @return array|bool
     */
    protected function _fetchArray()
    {
        return sqlsrv_fetch_array($this->result, SQLSRV_FETCH_NUMERIC);
    }

    protected function _fetchAssoc()
    {
        return sqlsrv_fetch_array($this->result, SQLSRV_FETCH_ASSOC);
    }

    protected function _fieldName()
    {
        // TODO: Implement _fieldName() method.
    }

    protected function _fieldType()
    {
        // TODO: Implement _fieldType() method.
    }

    protected function _numFields()
    {
        return sqlsrv_num_fields($this->result);
    }

    /**
     * @param $uniqueName
     *
     * @param $statement
     *
     * @return mixed
     * @see MSSQL::_prepareNamed
     * @see MySQL::_prepareNamed
     * @see OData::_prepareNamed
     * @see Pg::_prepareNamed
     */
    protected function _prepareNamed(string $uniqueName, string $statement): bool
    {
        // TODO: Implement _prepare() method.
    }

    protected function _query($statement)
    {

        if ($this->cursorType !== null) {
            return @sqlsrv_query($this->resourceLink, $statement, [], ["Scrollable" => $this->cursorType]);
        } else {
            if (preg_match('/^(\s*?)select\s*?.*?\s*?from/is', $this->query)) {
                // TODO: make as selectable option
                return @sqlsrv_query($this->resourceLink, $statement, [], ["Scrollable" => MSSQL::SQLSRV_CURSOR_STATIC]);
            }
        }

        return @sqlsrv_query($this->resourceLink, $statement);
    }

    protected function _rollback(): bool
    {
        return sqlsrv_rollback($this->resourceLink);
    }

    /**
     * @inheritDoc
     */
    protected function _dump(string $preparedQuery, string $filePath, string $delimiter, string $nullString, bool $showHeader): void
    {
        // TODO: Implement _dump() method.
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
