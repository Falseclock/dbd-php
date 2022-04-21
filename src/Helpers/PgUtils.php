<?php
/**
 * @author    Nurlan Mukhanov <nurike@gmail.com>
 * @copyright 2020 Nurlan Mukhanov
 * @license   https://en.wikipedia.org/wiki/MIT_License MIT License
 * @link      https://github.com/Falseclock/dbd-php
 * @noinspection SqlNoDataSourceInspection
 */

declare(strict_types=1);

namespace DBD\Helpers;

use DBD\Common\DBDException;
use DBD\DBD;
use DBD\Entity\Column;
use DBD\Entity\Common\EntityException;
use DBD\Entity\Constraint;
use DBD\Entity\Key;
use DBD\Entity\Primitive;
use DBD\Entity\Primitives\NumericPrimitives;
use DBD\Entity\Table;

/**
 * TODO: check against diff PostgreSQL versions
 */
class PgUtils extends UtilsImpl
{
    /** @var DBD $db */
    private $db;

    /**
     * PgUtils constructor.
     *
     * @param DBD $dbDriver
     */
    public function __construct(DBD $dbDriver)
    {
        $this->db = $dbDriver;
    }

    /**
     * @param Table $table
     *
     * @return Constraint[]
     * @throws DBDException
     * @throws EntityException
     */
    public function getTableConstraints(Table $table): array
    {
        $constraints = [];
        $sth = $this->db->prepare("
			SELECT
				kcu.column_name,
				ccu.table_schema AS foreign_table_schema,
				ccu.table_name   AS foreign_table_name,
				ccu.column_name  AS foreign_column_name
			FROM
				information_schema.table_constraints AS tc
				JOIN information_schema.key_column_usage AS kcu
					 ON tc.constraint_name = kcu.constraint_name
						 AND tc.table_schema = kcu.table_schema
				JOIN information_schema.constraint_column_usage AS ccu
					 ON ccu.constraint_name = tc.constraint_name
						 --AND ccu.table_schema = tc.table_schema
			WHERE
				tc.constraint_type = 'FOREIGN KEY' AND
				tc.table_name = ? AND
				tc.table_schema = ?
		"
        );
        $sth->execute($table->name, $table->scheme);

        if ($sth->rows()) {
            while ($row = $sth->fetchRow()) {
                $constraint = new Constraint();
                $constraint->localColumn = $this->getColumnByName($table->columns, $row['column_name']);
                // If table refers itself
                if ($table->name == $row['foreign_table_name'] and $table->scheme == $row['foreign_table_schema']) {
                    $constraint->foreignTable = $table;
                } else {
                    $constraint->foreignTable = $this->tableStructure($row['foreign_table_name'], $row['foreign_table_schema']);
                }
                $constraint->foreignColumn = $this->getColumnByName($constraint->foreignTable->columns, $row['foreign_column_name']);

                $constraints[] = $constraint;
            }
        }

        return $constraints;
    }

    /**
     * @param Column[] $columns
     * @param          $name
     *
     * @return Column
     */
    private function getColumnByName(iterable $columns, $name): Column
    {
        foreach ($columns as $column) {
            if ($column->name == $name) {
                return $column;
            }
        }
    }

    /**
     * @param string $tableName
     * @param string $schemeName
     *
     * @return Table
     * @throws DBDException
     * @throws EntityException
     */
    public function tableStructure(string $tableName, string $schemeName): Table
    {
        $table = new Table();
        $table->name = $tableName;
        $table->scheme = $schemeName;

        $table->annotation = $this->db->select("SELECT obj_description(CONCAT(?::text, '.', ?::text)::REGCLASS)", $table->scheme, $table->name);

        $sth = $this->db->prepare("
			SELECT
				CASE WHEN ordinal_position = ANY (i.indkey) THEN TRUE ELSE FALSE END                     AS is_primary,
				ordinal_position,
				cols.column_name,
				CASE WHEN is_nullable = 'NO' THEN FALSE WHEN is_nullable = 'YES' THEN TRUE END AS is_nullable,
				data_type,
				udt_name,
				character_maximum_length,
				numeric_precision,
				numeric_scale,
				datetime_precision,
				column_default,
				pg_catalog.col_description(CONCAT(cols.table_schema, '.', cols.table_name)::REGCLASS::OID, cols.ordinal_position::INT) AS column_comment
			FROM
				information_schema.columns cols
				LEFT JOIN pg_index i ON i.indrelid = CONCAT(cols.table_schema, '.', cols.table_name)::REGCLASS::OID AND i.indisprimary
			WHERE
				cols.table_name = ? AND
				cols.table_schema = ?
			ORDER BY
				ordinal_position
		"
        );
        $sth->execute($table->name, $table->scheme);
        $columns = [];

        if ($sth->rows()) {
            while ($row = $sth->fetchRow()) {
                $column = new Column($row['column_name']);

                if (isset($row['is_nullable'])) {
                    $column->nullable = false;
                }

                if (isset($row['character_maximum_length']))
                    $column->maxLength = $row['character_maximum_length'];

                if (isset($row['numeric_precision']))
                    $column->precision = $row['numeric_precision'];

                if (isset($row['numeric_scale']))
                    $column->scale = $row['numeric_scale'];

                if (isset($row['datetime_precision']))
                    $column->precision = $row['datetime_precision'];

                if (isset($row['column_default']))
                    $column->defaultValue = $row['column_default'];

                if (isset($row['column_comment']))
                    $column->annotation = $row['column_comment'];

                $column->type = Primitive::fromType($row['udt_name']);
                $column->originType = $row['udt_name'];

                if (in_array($column->type->getValue(), [NumericPrimitives::Int16, Primitive::Int32(), NumericPrimitives::Int64])) {
                    $column->scale = null;
                    $column->precision = null;
                }

                if (isset($row['is_primary']) && $row['is_primary'] == "t") {
                    $column->key = true;
                    $table->keys[] = new Key($column);
                } else {
                    $column->key = false;
                }
                $columns[] = $column;
            }
        }

        $table->columns = $columns;

        $table->constraints = $this->getTableConstraints($table);

        return $table;
    }
}
