<?php
/**
 * @author       Nurlan Mukhanov <nurike@gmail.com>
 * @copyright    2020 Nurlan Mukhanov
 * @license      https://en.wikipedia.org/wiki/MIT_License MIT License
 * @link         https://github.com/Falseclock/dbd-php
 * @noinspection PhpComposerExtensionStubsInspection
 */

declare(strict_types=1);

namespace DBD\Helpers;

use DBD\Common\CRUD;
use DBD\Common\DBDException;
use DBD\Common\Debug;
use DBD\DBD;
use RecursiveArrayIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use ReflectionException;

final class Helper
{
    public static $measureStep = 6.0;

    /**
     * @param $context
     *
     * @return Caller
     * @throws DBDException
     */
    final public static function caller($context): Caller
    {
        try {
            $debug = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

            // working directory
            $wd = is_link($_SERVER["DOCUMENT_ROOT"]) ? readlink($_SERVER["DOCUMENT_ROOT"]) : $_SERVER["DOCUMENT_ROOT"];
            $wd = str_replace(DIRECTORY_SEPARATOR, "/", $wd);

            $myFilename = $debug[0]['file'];
            $myFilename = str_replace(DIRECTORY_SEPARATOR, "/", $myFilename);
            $myFilename = str_replace($wd, '', $myFilename);

            $child = (new ReflectionClass($context))->getShortName();

            foreach ($debug as $call) {
                // our filename
                if (isset($call['file'])) {
                    $call['file'] = str_replace(DIRECTORY_SEPARATOR, "/", $call['file']);
                    $call['file'] = str_replace($wd, '', $call['file']);

                    if ($myFilename != $call['file'] && !preg_match('/' . $child . '\.\w+$/', $call['file'])) {
                        return new Caller($call['file'], $call['line'], $call['function']);
                    }
                }
            }

            // @codeCoverageIgnoreStart
            return new Caller("unknown file", 0, "unknown function");
            // @codeCoverageIgnoreEnd
        } catch (ReflectionException $e) {
            throw new DBDException($e->getMessage());
        }
    }

    /**
     * @param string $statement
     *
     * @return string
     */
    final public static function cleanSql(string $statement): string
    {
        $array = preg_split('/\R/u', $statement);

        foreach ($array as $idx => $line) {
            //$array[$idx] = trim($array[$idx], "\s\t\n\r");
            if (!$line || preg_match('/^[\s\t]*?$/u', $line)) {
                unset($array[$idx]);
                continue;
            }
            if (preg_match('/^\s*?(UNION|DROP|CREATE|DELETE|UPDATE|SELECT|FROM|WHERE|JOIN|LIMIT|OFFSET|ORDER|GROUP|HAVING)/i', $line)) {
                $array[$idx] = ltrim($line);
            } else {
                $array[$idx] = "    " . ltrim($line);
            }
        }

        return implode("\n", $array);
    }

    /**
     * @param array $data
     * @param DBD $driver
     * @return InsertArguments
     * @throws DBDException
     */
    final public static function compileInsertArguments(array $data, DBD $driver): InsertArguments
    {
        $columns = [];
        $values = [];
        $arguments = [];

        $placeHolder = $driver->getOptions()->getPlaceHolder();
        $format = $driver::CAST_FORMAT_INSERT;

        foreach ($data as $columnName => $columnValue) {
            $columns[] = $columnName;

            // Identifying value type
            if (is_array($columnValue)) {
                switch (count($columnValue)) {
                    case 1:
                        $values[] = $placeHolder;
                        self::booleanToString($columnValue[0]);
                        $arguments[] = $columnValue[0];
                        break;
                    case 2:
                        self::booleanToString($columnValue[0]);
                        $values[] = isset($format) ? sprintf($format, $columnValue[1]) : $placeHolder;
                        $arguments[] = $columnValue[0];
                        break;
                    default:
                        throw new DBDException(CRUD::ERROR_UNKNOWN_INSERT_FORMAT);
                }
            } else {
                self::booleanToString($columnValue);
                $arguments[] = $columnValue;
                $values[] = $placeHolder;
            }
        }

        return new InsertArguments($columns, $values, $arguments);
    }

    /**
     * Converts boolean to string value
     *
     * @param mixed $value
     */
    private static function booleanToString(&$value): void
    {
        if (is_bool($value)) {
            $value = ($value) ? 'TRUE' : 'FALSE';
        }
    }

    /**
     * Parses array of values for update
     *
     * @param array $data
     * @param DBD $driver
     *
     * @return UpdateArguments
     * @throws DBDException
     */
    final public static function compileUpdateArgs(array $data, DBD $driver): UpdateArguments
    {
        $defaultFormat = "%s = ?";
        $format = $driver::CAST_FORMAT_UPDATE;

        $columns = [];
        $args = [];

        foreach ($data as $columnName => $columnValue) {
            if (is_array($columnValue)) {
                switch (count($columnValue)) {
                    case 1:
                        $columns[] = sprintf($defaultFormat, $columnName);
                        $args[] = $columnValue[0];
                        break;
                    case 2:
                        $columns[] = sprintf($format ?: $defaultFormat, $columnName, $columnValue[1]);
                        $args[] = $columnValue[0];
                        break;
                    default:
                        throw new DBDException(CRUD::ERROR_UNKNOWN_UPDATE_FORMAT);
                }
            } else {
                $columns[] = sprintf($defaultFormat, $columnName);
                $args[] = $columnValue;
            }
        }

        return new UpdateArguments($columns, $args);
    }

    /**
     * @param int|float $cost
     * @param int|null $maxExecutionTime
     *
     * @return float
     */
    final public static function debugMark(float $cost, int $maxExecutionTime = null): float
    {
        if (is_null($maxExecutionTime)) {
            $maxExecutionTime = Debug::$maxExecutionTime;
        }

        $value = floor($cost / $maxExecutionTime) + 1;

        if ($value > self::$measureStep) {
            return self::$measureStep;
        }

        return $value;
    }

    /**
     * @param array $ARGS
     *
     * @return PrepareArguments
     */
    final public static function prepareArguments(array $ARGS): PrepareArguments
    {
        if (count($ARGS) == 1 and is_array($ARGS[0])) {
            $ARGS = $ARGS[0];
        }

        // Shift query from passed arguments. Query is always first
        $statement = array_shift($ARGS);

        // Build array of arguments
        $args = self::parseArguments($ARGS);

        return new PrepareArguments($statement, $args);
    }

    /**
     * @param array $ARGS
     *
     * @return array
     */
    final public static function parseArguments(array $ARGS): array
    {
        $args = [];

        $iterator = new RecursiveIteratorIterator(new RecursiveArrayIterator($ARGS));
        foreach ($iterator as $value) {
            $args[] = $value;
        }

        return $args;
    }

    /**
     * @param string $query
     * @return string
     * @throws DBDException
     * @see HelperTest::testGetQueryType()
     */
    public static function getQueryType(string $query): string
    {
        $query = preg_replace('/[ \t]*\/\*.*?\*\/[ \t]*[\r\n]?/sm', '', $query);
        $query = preg_replace('/--\s.*$/m', '', $query);
        $query = trim($query);

        preg_match('/^(SELECT|UPDATE|DELETE|INSERT|WITH)\s+/', $query, $matches);

        if (count($matches) == 0) {
            throw new DBDException(sprintf(CRUD::ERROR_NON_SQL_QUERY, $query));
        }

        switch (strtoupper(trim($matches[1]))) {
            case CRUD::CREATE:
                return CRUD::CREATE;
            case CRUD::READ:
                return CRUD::READ;
            case CRUD::UPDATE:
                return CRUD::UPDATE;
            case CRUD::DELETE:
                return CRUD::DELETE;
            default:
                throw new DBDException(sprintf(CRUD::ERROR_UNIDENTIFIABLE_QUERY, $query));
        }
    }
}
