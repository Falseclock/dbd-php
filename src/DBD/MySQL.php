<?php
/**
 * MySQL
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
use DBD\Utils\UpdateArguments;

class MySQL extends DBD
{
    /**
     *
     * @return $this|DBD
     */
    public function connect(): DBD
    {

        if ($this->Options->isOnDemand() == false) {
            $this->_connect();
        }

        return $this;
    }

    protected function _rows(): int
    {
        return mysqli_affected_rows($this->result);
    }

    protected function _begin(): bool
    {
        return mysqli_begin_transaction($this->resourceLink);
    }

    protected function _commit(): bool
    {
        return mysqli_commit($this->resourceLink);
    }

    protected function _compileInsert(string $table, array $params, ?string $return = ""): string
    {
        return "INSERT INTO $table ({$params['COLUMNS']}) VALUES ({$params['VALUES']})";
    }

    protected function _compileUpdate(string $table, UpdateArguments $updateArguments, ?string $where = null, ?string $return = null): string
    {
        return "UPDATE $table SET {$updateArguments['COLUMNS']}" . ($where ? " WHERE $where" : "");
    }

    protected function _connect(): void
    {
        $this->resourceLink = mysqli_connect($this->Config->getHost(),
            $this->Config->getUsername(),
            $this->Config->getPassword(),
            $this->Config->getDatabase(),
            $this->Config->getPort()
        );

        if (!$this->resourceLink)
            trigger_error("Can not connect to MySQL server: " . mysqli_connect_error(), E_USER_ERROR);

        mysqli_autocommit($this->resourceLink, false);
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

    protected function _escape($string): string
    {
        if (!isset($string))
            return "NULL";

        $str = mysqli_real_escape_string($this->resourceLink, $string);

        return "'$str'";
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
	protected function _executeNamed($uniqueName, $arguments) {
		// TODO: Implement _execute() method.
    }

    /**
     * @return array|bool
     */
    protected function _fetchArray()
    {
        return mysqli_fetch_array($this->resourceLink);
    }

    protected function _fetchAssoc()
    {
        return mysqli_fetch_assoc($this->resourceLink);
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
        return mysqli_query($this->resourceLink, $statement);
    }

    protected function _rollback(): bool
    {
        return mysqli_rollback($this->resourceLink);
    }

    /**
     * @inheritDoc
     */
    protected function _dump(string $preparedQuery, string $fileName, string $delimiter, string $nullString, bool $showHeader, string $tmpPath): string
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
