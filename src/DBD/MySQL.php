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

/**
 * Class MySQL
 *
 * @package DBD
 */
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

    protected function _compileInsert(string $table, array $params, string $return = ""): string
    {
        return "INSERT INTO $table ({$params['COLUMNS']}) VALUES ({$params['VALUES']})";
    }

    protected function _compileUpdate(string $table, array $params, string $where, ?string $return = ""): string
    {
        return "UPDATE $table SET {$params['COLUMNS']}" . ($where ? " WHERE $where" : "");
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

    protected function _escape($value): string
    {
        if (!isset($value) or $value === null) {
            return "NULL";
        }
        $str = mysqli_real_escape_string($this->resourceLink, $value);

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
	protected function _execute($uniqueName, $arguments) {
		// TODO: Implement _execute() method.
    }

    protected function _fetchArray(): array
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
     * @see MSSQL::_prepare
     * @see MySQL::_prepare
     * @see OData::_prepare
     * @see Pg::_prepare
     */
    protected function _prepare(string $uniqueName, string $statement): bool
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
    protected function _binaryEscape(?string $binaryString): ?string
    {
        // TODO: Implement _binaryEscape() method.
	}
}
