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

namespace DBD\Base;

use DBD\Common\DBDException;
use DBD\DBD;
use DBD\Utils\InsertArguments;
use DBD\Utils\PrepareArguments;
use DBD\Utils\UpdateArguments;
use Exception;
use ReflectionClass;

final class Helper
{
    /**
     * @param $context
     *
     * @return array
     * @throws DBDException
     */
    final public static function caller($context): array
    {
        try {
            $return = [];
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
                        $return[] = [
                            'file' => $call['file'],
                            'line' => $call['line'],
                            'function' => $call['function'],
                        ];
                    }
                }
            }

            return $return;
        } catch (Exception $e) {
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
            if (preg_match('/^\s*?(UNION|CREATE|DELETE|UPDATE|SELECT|FROM|WHERE|JOIN|LIMIT|OFFSET|ORDER|GROUP)/i', $line)) {
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
    final public static function compileInsertArgs(array $data, DBD $driver): InsertArguments
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
    private static function booleanToString(&$value)
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

    private static function arrayFlatten($array)
    {
        if (!is_array($array)) {
            return false;
        }
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
     * @param $query
     * @return string
     * @throws DBDException
     */
    public static function getQueryType($query): string
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
