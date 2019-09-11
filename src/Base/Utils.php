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
 *   FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE     *
 *   AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER          *
 *   LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,   *
 *   OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE   *
 *   SOFTWARE.                                                                       *
 ************************************************************************************/

namespace DBD\Base;

use DBD\DBD;
use DBD\Pg;
use Falseclock\DBD\Common\DBDException;
use Falseclock\DBD\Common\DBDException as Exception;
use Falseclock\DBD\Entity\Column;
use Falseclock\DBD\Entity\Primitive;
use Psr\SimpleCache\InvalidArgumentException;
use ReflectionException;

class Utils
{
	/**
	 * Returns structure of table
	 *
	 * @param DBD    $db
	 * @param string $table
	 * @param string $scheme
	 *
	 * @return Column[]
	 * @throws Exception
	 * @throws InvalidArgumentException
	 * @throws ReflectionException
	 */
	public static function tableStructure(DBD $db, string $table, string $scheme) {
		switch(true) {
			case $db instanceof Pg:
				return self::pgTableStructure($db, $table, $scheme);
				break;
			default:
				throw new Exception("Not implemented for this driver yet");
		}
	}

	/**
	 * @param string $type
	 *
	 * @return Primitive
	 * @throws Exception
	 */
	private static function getPgPrimitive(string $type) {
		switch(strtolower(trim($type))) {

			case 'bytea':
				return Primitive::Binary();
				break;

			case 'boolean':
			case 'bool':
				return Primitive::Boolean();
				break;

			case 'date':
			case 'timestamp':
				return Primitive::Date();
				break;

			case 'timestamptz':
				return Primitive::DateTimeOffset();
				break;

			case 'numeric':
			case 'decimal':
				return Primitive::Decimal();
				break;

			case 'float8':
				return Primitive::Double();
				break;

			case 'interval':
				return Primitive::Duration();
				break;

			case 'uuid':
				return Primitive::Guid();
				break;

			case 'int2':
			case 'smallint':
			case 'smallserial':
			case 'serial2':
				return Primitive::Int16();
				break;

			case 'int':
			case 'int4':
			case 'integer':
			case 'serial4':
			case 'serial':
				return Primitive::Int32();
				break;

			case 'int8':
			case 'bigint':
			case 'bigserial':
			case 'serial8':
				return Primitive::Int64();
				break;

			case 'float4':
			case 'real':
				return Primitive::Single();
				break;

			case 'varchar':
			case 'text':
			case 'cidr':
			case 'inet':
			case 'json':
			case 'jsonb':
			case 'macaddr':
			case 'macaddr8':
			case 'char':
			case 'tsquery':
			case 'tsvector':
			case 'xml':
				return Primitive::String();
				break;
		}

		throw new DBDException("Not described type found: {$type}");
	}

	/**
	 * @param DBD    $db
	 * @param string $table
	 * @param string $schema
	 *
	 * @return Column[]
	 * @throws Exception
	 * @throws InvalidArgumentException
	 * @throws ReflectionException
	 */
	private static function pgTableStructure(DBD $db, string $table, string $schema) {
		// Postgres uses dot symbol to separate schema and table
		if(!isset($schema)) {
			//Get the first occurrence of a character.
			$dotPosition = strpos($table, '.');
			if($dotPosition === false) {
				throw new Exception("No schema provided");
			}
			$initialTable = $table;
			$schema = substr($initialTable, 0, $dotPosition);
			$table = substr($initialTable, $dotPosition + 1);
		}
		$regClass = "{$schema}.{$table}";

		$sth = $db->prepare("
			SELECT
				CASE WHEN ordinal_position = ANY(i.indkey) THEN TRUE ELSE FALSE END as is_primary,
				ordinal_position,
				cols.column_name,
				CASE WHEN is_nullable = 'NO' THEN FALSE WHEN is_nullable = 'YES' THEN TRUE ELSE NULL END AS is_nullable,
				data_type,
				udt_name,
				character_maximum_length,
				numeric_precision,
				numeric_scale,
				datetime_precision,
				column_default,
				pg_catalog.col_description(?::regclass::oid, cols.ordinal_position::INT)
			FROM
				information_schema.columns cols
				LEFT JOIN pg_index i ON i.indrelid = ?::regclass::oid
			WHERE
				cols.table_name = ? AND
				cols.table_schema = ?'
			ORDER BY
				ordinal_position
		"
		);
		$sth->execute($regClass, $regClass, $table, $schema);

		if($sth->rows()) {
			$columns = [];
			while($row = $sth->fetchRow()) {
				$column = new Column();
				$column->name = $row['column_name'];

				if(isset($row['is_nullable'])) {
					if($row['is_nullable'] == 'f' || $row['is_nullable'] == false)
						$column->nullable = false;
					else
						$column->nullable = false;
				}

				if(isset($row['is_primary'])) {
					if($row['is_primary'] == 'f' || $row['is_primary'] == false)
						$column->key = false;
					else
						$column->key = true;
				}

				if(isset($row['character_maximum_length']))
					$column->maxLength = $row['character_maximum_length'];

				if(isset($row['numeric_precision']))
					$column->precision = $row['numeric_precision'];

				if(isset($row['numeric_scale']))
					$column->scale = $row['numeric_scale'];

				if(isset($row['datetime_precision']))
					$column->precision = $row['datetime_precision'];

				if(isset($row['column_default']))
					$column->defaultValue = $row['column_default'];

				if(isset($row['column_comment']))
					$column->annotation = $row['column_comment'];

				$column->type = self::getPgPrimitive($row['udt_name']);

				if(in_array($column->type->getValue(), [ Primitive::Int16, Primitive::Int32(), Primitive::Int64 ])) {
					$column->scale = null;
					$column->precision = null;
				}

				$columns[] = $column;
			}

			return $columns;
		}

		return [];
	}
}