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

use DBD\Common\DBDException as Exception;

/**
 * Class MSSQL
 *
 * @package DBD
 */
class MSSQL extends DBD
{
	const SQLSRV_CURSOR_CLIENT_BUFFERED = 'buffered';
	const SQLSRV_CURSOR_DYNAMIC         = 'dynamic';
	const SQLSRV_CURSOR_FORWARD         = 'forward';
	const SQLSRV_CURSOR_KEYSET          = 'keyset';
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

    protected function _compileInsert(string $table, array $params, string $return = ""): string
    {
        return "INSERT INTO $table ({$params['COLUMNS']}) VALUES ({$params['VALUES']})";
    }

    protected function _compileUpdate(string $table, array $params, string $where, string $return = ""): string
    {
        return "UPDATE $table SET {$params['COLUMNS']}" . ($where ? " WHERE $where" : "");
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

    protected function _convertTypes(&$data): void
    {
        // TODO: Implement _convertTypes() method.
    }

    protected function _disconnect(): bool
    {
        return sqlsrv_close($this->resourceLink);
    }

    protected function _errorMessage(): string
    {
        $errors = sqlsrv_errors();

        return preg_replace('/^(\[.*\])+?/', '', $errors[0]['message']) . " SQL State: " . $errors[0]['SQLSTATE'] . ". Code: " . $errors[0]['code'];
    }

    protected function _escape($str): string
    {
        if (!isset($str) or $str === null) {
            return "NULL";
        }

        if (is_numeric($str))
            return $str;

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
		foreach($nonDisplayAble as $regex)
			$str = preg_replace($regex, '', $str);

		$str = str_replace("'", "''", $str);

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
        // TODO: Implement _execute() method.
    }

    protected function _fetchArray(): array
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
     * @see MSSQL::_prepare
     * @see MySQL::_prepare
     * @see OData::_prepare
     * @see Pg::_prepare
     */
    protected function _prepare($uniqueName, $statement): bool
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
