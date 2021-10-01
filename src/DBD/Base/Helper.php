<?php
/**
 * Helper
 *
 * @author       Nurlan Mukhanov <nurike@gmail.com>
 * @copyright    2020 Nurlan Mukhanov
 * @license      https://en.wikipedia.org/wiki/MIT_License MIT License
 * @link         https://github.com/Falseclock/dbd-php
 * @noinspection PhpComposerExtensionStubsInspection
 */

declare(strict_types=1);

namespace DBD\Base;

use DBD\Common\DBDException;
use DBD\DBD;
use DBD\Utils\InsertArguments;
use DBD\Utils\PrepareArguments;
use DBD\Utils\Caller;
use DBD\Utils\UpdateArguments;
use Exception;
use ReflectionClass;

final class Helper
{
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

        } catch (Exception $e) {
            throw new DBDException($e->getMessage());
        }
        // @codeCoverageIgnoreEnd
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
                        throw new DBDException("Unknown format of record for insert");
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
        if (is_bool($value))
            $value = ($value) ? 'TRUE' : 'FALSE';
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
                        throw new DBDException("Unknown format of record for update");
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
        if (!isset($maxExecutionTime)) {
            $maxExecutionTime = Debug::$maxExecutionTime;
        }

        $value = floor($cost / $maxExecutionTime) + 1;

        if ($value > 6)
            return 6;

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

        foreach ($ARGS as $arg) {
            if (is_array($arg)) {
                foreach (self::arrayFlatten($arg) as $value)
                    $args[] = $value;
            } else {
                $args[] = $arg;
            }
        }

        return $args;
    }

    /**
     * @param array $array
     * @return array
     */
    private static function arrayFlatten(array $array): array
    {
        $result = [];
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $arrayList = self::arrayFlatten($value);
                foreach ($arrayList as $listItem) {
                    $result[] = $listItem;
                }
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * @param string $query
     * @return string
     * @throws DBDException
     */
    public static function getQueryType(string $query): string
    {
        preg_match('/^(\s*?--.*\n)?\s*(SELECT|UPDATE|DELETE|INSERT)\s+/', $query, $matches);

        switch (strtoupper(trim($matches[2]))) {
            case CRUD::CREATE:
                return CRUD::CREATE;
            case CRUD::READ:
                return CRUD::READ;
            case CRUD::UPDATE:
                return CRUD::UPDATE;
            case CRUD::DELETE:
                return CRUD::DELETE;
            default:
                throw new DBDException("non SQL query: $query");
        }
    }
}
