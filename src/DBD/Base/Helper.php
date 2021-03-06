<?php
/*************************************************************************************
 *   MIT License                                                                     *
 *                                                                                   *
 *   Copyright (C) 2009-2019 by Nurlan Mukhanov <nurike@gmail.com>                   *
 *                                                                                   *
 *   Permission is hereby granted, free of charge, to any person obtaining a copy    *
 *   of this software and associated documentation files (the "Software"), to deal   *
 *   in the Software without restriction, including without limitation the rights    *
 *   to use, copy, modify, merge, publish, distribute, sublicense, and/or sell       *
 *   copies of the Software, and to permit persons to whom the Software is           *
 *   furnished to do so, subject to the following conditions:                        *
 *                                                                                   *
 *   The above copyright notice and this permission notice shall be included in all  *
 *   copies or substantial portions of the Software.                                 *
 *                                                                                   *
 *   THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR      *
 *   IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,        *
 *   FITNESS FOR A PARTICULAR PURPOSE AND NON-INFRINGEMENT. IN NO EVENT SHALL THE    *
 *   AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER          *
 *   LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,   *
 *   OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE   *
 *   SOFTWARE.                                                                       *
 ************************************************************************************/

namespace DBD\Base;

use DBD\Common\DBDException;
use DBD\DBD;
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
	final public static function caller($context): array {
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

			foreach($debug as $ind => $call) {
				// our filename
				if(isset($call['file'])) {
					$call['file'] = str_replace(DIRECTORY_SEPARATOR, "/", $call['file']);
					$call['file'] = str_replace($wd, '', $call['file']);

					if($myFilename != $call['file'] && !preg_match('/' . $child . '\.\w+$/', $call['file'])) {
						$return[] = [
							'file'     => $call['file'],
							'line'     => $call['line'],
							'function' => $call['function'],
						];
					}
				}
			}

			return $return;
		}
		catch(Exception $e) {
			throw new DBDException($e->getMessage());
		}
	}

	/**
	 * @param string $statement
	 *
	 * @return string
	 */
	final public static function cleanSql(string $statement): string {
		$array = preg_split('/\R/u', $statement);

		foreach($array as $idx => $line) {
			//$array[$idx] = trim($array[$idx], "\s\t\n\r");
			if(!$array[$idx] || preg_match('/^[\s\t]*?$/u', $array[$idx])) {
				unset($array[$idx]);
				continue;
			}
			if(preg_match('/^\s*?(UNION|CREATE|DELETE|UPDATE|SELECT|FROM|WHERE|JOIN|LIMIT|OFFSET|ORDER|GROUP)/i', $array[$idx])) {
				$array[$idx] = ltrim($array[$idx]);
			}
			else {
				$array[$idx] = "    " . ltrim($array[$idx]);
			}
		}

		return implode("\n", $array);
	}

	/**
	 * @param            $data
	 * @param DBD        $driver
	 * @param Options    $options
	 *
	 * @return array
	 * @throws DBDException
	 */
	final public static function compileInsertArgs(array $data, DBD $driver, Options $options): array {

		$className = get_class($driver);

		$columns = [];
		$values = [];
		$args = [];

		$defaultFormat = $options->getPlaceHolder();
		$format = null;

		if(defined("{$className}::CAST_FORMAT_INSERT")) {
			/** @noinspection PhpPossiblePolymorphicInvocationInspection */
			$format = $driver::CAST_FORMAT_INSERT;
		}

		foreach($data as $columnName => $columnValue) {
			$columns[] = $columnName;

			// Identifying value type
			if(is_array($columnValue)) {
				switch(count($columnValue)) {
					case 1:
						$values[] = $defaultFormat;
						self::booleanToString($columnValue[0]);
						$args[] = $columnValue[0];
						break;
					case 2:
						self::booleanToString($columnValue[0]);
						$values[] = isset($format) ? sprintf($format, $columnValue[1]) : $defaultFormat;
						$args[] = $columnValue[0];
						break;
					default:
						throw new DBDException("Unknown format of record for insert");
				}
			}
			else {
				self::booleanToString($columnValue);
				$args[] = $columnValue;
				$values[] = $defaultFormat;
			}
		}

		return [
			'COLUMNS' => implode(", ", $columns),
			'VALUES'  => implode(", ", $values),
			'ARGS'    => $args,
		];
	}

	/**
	 * Parses array of values for update
	 *
	 * @param          $data
	 * @param DBD      $driver
	 *
	 * @return array
	 * @throws DBDException
	 */
	final public static function compileUpdateArgs(array $data, DBD $driver): array {
		$className = get_class($driver);
		$defaultFormat = "%s = ?";
		$format = null;

		if(defined("{$className}::CAST_FORMAT_UPDATE")) {
			/** @noinspection PhpPossiblePolymorphicInvocationInspection */
			$format = $driver::CAST_FORMAT_UPDATE;
		}

		$columns = [];
		$args = [];

		foreach($data as $columnName => $columnValue) {
			if(is_array($columnValue)) {
				switch(count($columnValue)) {
					case 1:
						$columns[] = sprintf($defaultFormat, $columnName);
						$args[] = $columnValue[0];
						break;
					case 2:
						$columns[] = sprintf($format ? $format : $defaultFormat, $columnName, $columnValue[1]);
						$args[] = $columnValue[0];
						break;
					default:
						throw new DBDException("Unknown format of record for update");
				}
			}
			else {
				$columns[] = sprintf($defaultFormat, $columnName);
				$args[] = $columnValue;
			}
		}

		return [
			'COLUMNS' => implode(", ", $columns),
			'ARGS'    => $args,
		];
	}

	/**
	 * @param int|float $cost
	 * @param int|null  $maxExecutionTime
	 *
	 * @return float
	 */
	final public static function debugMark(float $cost, int $maxExecutionTime = null): float {
		if(!isset($maxExecutionTime)) {
			$maxExecutionTime = Debug::$maxExecutionTime;
		}

		$value = floor($cost / $maxExecutionTime) + 1;

		if($value > 6)
			return 6;

		return $value;
	}

	/**
	 * @param array $ARGS
	 *
	 * @return array
	 */
	final public static function parseArgs(array $ARGS): array {
		$args = [];

		foreach($ARGS as $arg) {
			if(is_array($arg)) {
				foreach(self::arrayFlatten($arg) as $value)
					$args[] = $value;
			}
			else {
				$args[] = $arg;
			}
		}

		return $args;
	}

	/**
	 * @param array $ARGS
	 *
	 * @return array
	 */
	final public static function prepareArgs(array $ARGS): array {
		if(count($ARGS) == 1 and is_array($ARGS[0])) {
			$ARGS = $ARGS[0];
		}
		// Shift query from passed arguments. Query is always first
		$statement = array_shift($ARGS);
		// Build array of arguments
		$args = self::parseArgs($ARGS);

		return [
			$statement,
			$args,
		];
	}

	private static function arrayFlatten($array) {
		if(!is_array($array)) {
			return false;
		}
		$result = [];
		foreach($array as $key => $value) {
			if(is_array($value)) {
				$arrayList = self::arrayFlatten($value);
				foreach($arrayList as $listItem) {
					$result[] = $listItem;
				}
			}
			else {
				$result[$key] = $value;
			}
		}

		return $result;
	}

	/**
	 * Converts boolean to string value
	 *
	 * @param mixed $value
	 */
	private static function booleanToString(&$value) {
		if(is_bool($value)) {
			$value = ($value) ? 'TRUE' : 'FALSE';
		}
	}
}