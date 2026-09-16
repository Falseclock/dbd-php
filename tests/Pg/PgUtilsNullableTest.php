<?php
/**
 * @author    Nurlan Mukhanov <nurike@gmail.com>
 * @copyright 2020 Nurlan Mukhanov
 * @license   https://en.wikipedia.org/wiki/MIT_License MIT License
 * @link      https://github.com/Falseclock/dbd-php
 * @noinspection SqlNoDataSourceInspection
 * @noinspection SqlResolve
 */

declare(strict_types=1);

namespace DBD\Tests\Pg;

use DBD\Common\DBDException;
use DBD\Entity\Column;
use DBD\Entity\Common\EntityException;
use DBD\Entity\Table;
use DBD\Helpers\PgUtils;

/**
 * Regression tests for PgUtils::tableStructure().
 *
 * ext-pgsql returns PostgreSQL booleans as the strings 't' / 'f' unless Options::convertBoolean is on,
 * so nullability must be derived from the PostgreSQL boolean representation explicitly.
 */
class PgUtilsNullableTest extends PgAbstractTest
{
    private const TABLE = 'pgutils_nullable_test';

    protected function tearDown(): void
    {
        $this->db->do("DROP TABLE IF EXISTS public." . self::TABLE);
        parent::tearDown();
    }

    /**
     * @throws DBDException
     * @throws EntityException
     */
    public function testNullableIsParsedFromPostgresBooleanStrings(): void
    {
        $this->createFixture(true);
        $this->db->getOptions()->setConvertBoolean(false);

        $this->assertNullability((new PgUtils($this->db))->tableStructure(self::TABLE, 'public'));
    }

    /**
     * @throws DBDException
     * @throws EntityException
     */
    public function testNullableIsParsedWhenBooleanConversionIsEnabled(): void
    {
        $this->createFixture(true);
        $this->db->getOptions()->setConvertBoolean(true);

        $this->assertNullability((new PgUtils($this->db))->tableStructure(self::TABLE, 'public'));
    }

    /**
     * @throws DBDException
     * @throws EntityException
     */
    public function testTableWithoutCommentIsDescribedWithEmptyAnnotation(): void
    {
        $this->createFixture(false);

        $table = (new PgUtils($this->db))->tableStructure(self::TABLE, 'public');

        self::assertSame('', $table->annotation);
        self::assertCount(3, $table->columns);
    }

    /**
     * @throws DBDException
     */
    private function createFixture(bool $withComment): void
    {
        $this->db->do("DROP TABLE IF EXISTS public." . self::TABLE);
        $this->db->do("
            CREATE TABLE public." . self::TABLE . "
            (
                id             SERIAL PRIMARY KEY,
                required_value TEXT NOT NULL,
                optional_value TEXT NULL
            )
        ");

        if ($withComment) {
            $this->db->do("COMMENT ON TABLE public." . self::TABLE . " IS 'nullable fixture'");
        }
    }

    private function assertNullability(Table $table): void
    {
        /** @var Column[] $columns */
        $columns = [];
        foreach ($table->columns as $column) {
            $columns[$column->name] = $column;
        }

        self::assertSame(['id', 'required_value', 'optional_value'], array_keys($columns));

        self::assertFalse($columns['id']->nullable);
        self::assertFalse($columns['required_value']->nullable);
        self::assertTrue($columns['optional_value']->nullable);

        self::assertTrue($columns['id']->key);
        self::assertFalse($columns['required_value']->key);
        self::assertFalse($columns['optional_value']->key);
        self::assertCount(1, $table->keys);

        self::assertSame('nullable fixture', $table->annotation);
    }
}
