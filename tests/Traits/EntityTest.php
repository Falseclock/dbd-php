<?php
/**
 * @author    Nurlan Mukhanov <nurike@gmail.com>
 * @copyright 2009-2022 Nurlan Mukhanov
 * @license   https://en.wikipedia.org/wiki/MIT_License MIT License
 * @link      https://github.com/Falseclock/dbd-php
 * @noinspection PhpUnused
 */

declare(strict_types=1);

namespace DBD\Tests\Traits;

use DBD\Common\CRUD;
use DBD\Common\DBDException;
use DBD\Entity\Common\EntityException;
use DBD\Tests\Entities\City;
use DBD\Tests\Entities\Country;
use DBD\Tests\Entities\TestBase;
use DBD\Tests\Entities\TestBaseJson;
use DBD\Tests\Entities\TestBaseNoAuto;
use DBD\Tests\Entities\TestBaseNoPK;
use DBD\Tests\Entities\TestBaseNullable;
use DBD\Tests\Entities\TestBaseNullable2;
use DBD\Tests\Entities\TestBaseNullable2Map;
use DBD\Tests\Entities\TestBaseNullableMap;

trait EntityTest
{
    public function testForJsonConversion()
    {
        $array = ['foo' => true, 'bar' => false];

        $entity = new TestBaseJson();
        $entity->value = $array;
        $entity->id = 1;

        $this->assertException(DBDException::class, function () use ($entity) {
            $this->db->entityUpdate($entity);
        });

        self::assertIsString($entity->value);
        self::assertEquals(json_encode($array, JSON_UNESCAPED_UNICODE), $entity->value);
        self::assertSame(1, $entity->id);
    }

    /**
     * @throws DBDException
     * @noinspection SqlResolve
     */
    public function testForConstraint()
    {
        $this->db->do("CREATE TABLE IF NOT EXISTS country (country_id int, country_name varchar(128))");
        $this->db->do("CREATE TABLE IF NOT EXISTS city (city_id int, country_id int, city_name varchar(128))");

        $country = new Country();
        $country->id = 1;
        $country->name = "Universe";
        $city = new City();
        $city->id = 2;
        $city->name = "Nowhere";
        $city->Country = $country;

        $this->assertException(DBDException::class, function () use ($city) {
            $this->db->entityUpdate($city);
        }, CRUD::ERROR_ENTITY_NO_UPDATES);

        $this->db->entityInsert($city);
        $this->db->entityInsert($city);

        $this->assertException(DBDException::class, function () use ($city) {
            $this->db->entityUpdate($city);
        }, CRUD::ERROR_ENTITY_TOO_MANY_UPDATES);

        $this->db->do("DROP TABLE country");
        $this->db->do("DROP TABLE city");
    }

    /**
     * @throws DBDException
     * @noinspection PhpRedundantOptionalArgumentInspection
     * @noinspection SqlResolve
     */
    public function testEntitySelect()
    {
        $this->db->do("CREATE TABLE IF NOT EXISTS city (city_id int, country_id int, city_name varchar(128))");

        $city = new City();
        $city->id = 1;

        self::assertNull($this->db->entitySelect($city, false));

        $city = new City();
        $city->id = 1;

        $this->assertException(DBDException::class, function () use ($city) {
            $this->db->entitySelect($city, true);
        }, sprintf(CRUD::ERROR_ENTITY_NOT_FOUND, City::class));

        $this->db->do("DROP TABLE city");
    }

    /**
     * @throws DBDException
     * @throws EntityException
     * @noinspection SqlType
     */
    public function testEntityBase()
    {
        $this->options->setConvertNumeric(true);
        $this->options->setConvertBoolean(true);

        /** @var TestBaseNullableMap $map */
        $map = TestBaseNullable::map();

        $this->db->do(sprintf("DROP TABLE IF EXISTS %s.%s", TestBaseNullable::SCHEME, TestBaseNullable::TABLE));
        $this->db->do(sprintf("CREATE TABLE %s.%s (%s serial, %s text)", TestBaseNullable::SCHEME, TestBaseNullable::TABLE, $map->id->name, $map->name->name));

        $i = 1;
        while ($i < 11) {
            $entity = new TestBaseNullable();
            $entity->name = substr(str_shuffle(str_repeat($x = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', intval(ceil(10 / strlen($x))))), 1, 10);

            $this->db->entityInsert($entity);
            self::assertSame($i, $entity->id);

            $i++;
        }
        self::assertSame(10, $this->db->select("SELECT count(*) FROM " . TestBaseNullable::TABLE));
        $sth = $this->db->prepare("SELECT * FROM " . TestBaseNullable::TABLE);
        $sth->execute();
        while ($row = $sth->fetchRow()) {
            $entity = new TestBaseNullable($row);
            self::assertNotNull($entity->name);
            self::assertNotNull($entity->id);

            $entityInitial = clone $entity;

            $this->db->entitySelect($entity);
            self::assertEquals($entityInitial, $entity);

            $entity->name = "updated";

            $this->db->entityUpdate($entity);

            self::assertSame("updated", $entity->name);

            self::assertTrue($this->db->entityDelete($entity));
        }

        // test for return false
        $entity = new TestBaseNullable();
        $entity->id = 123456789;
        $result = $this->db->entityDelete($entity);

        self::assertFalse($result);
    }

    /**
     * @throws DBDException
     * @throws EntityException
     */
    public function testEntityBaseDefaultValueInsert()
    {
        /** @var TestBaseNullableMap $map */
        $map = TestBaseNullable::map();

        $this->db->do("DROP TABLE IF EXISTS " . TestBaseNullable::TABLE);
        $this->db->do("CREATE TABLE " . TestBaseNullable::TABLE . " (" . $map->id->name . " serial, " . $map->name->name . " text)");

        $i = 0;
        while ($i < 10) {
            $entity = new TestBaseNullable();
            $this->db->entityInsert($entity);
            $i++;
        }

        $sth = $this->db->prepare("SELECT * FROM " . TestBaseNullable::TABLE . " WHERE " . $map->name->name . "=?");
        $sth->execute($map->name->defaultValue);

        self::assertCount(10, $sth->fetchRowSet());
    }

    /**
     * @throws DBDException
     */
    public function testEntityBaseNoAutoInsert()
    {
        $this->options->setConvertNumeric(true);
        $this->options->setConvertBoolean(true);

        $entity = new TestBaseNoAuto();

        self::expectException(DBDException::class);
        $this->db->entityInsert($entity);
    }

    /**
     * @throws DBDException
     * @throws EntityException
     */
    public function testEntityBaseNullableValueInsert()
    {
        /** @var TestBaseNullable2Map $map */
        $map = TestBaseNullable2::map();

        $this->db->do("DROP TABLE IF EXISTS " . TestBaseNullable2::TABLE);
        $this->db->do("CREATE TABLE " . TestBaseNullable2::TABLE . " (" . $map->id->name . " serial, " . $map->name->name . " text, " . $map->name2->name . " text)");

        $i = 0;
        while ($i < 10) {
            $entity = new TestBaseNullable2();
            $this->db->entityInsert($entity);
            $i++;
        }

        $sth = $this->db->prepare("SELECT * FROM " . TestBaseNullable2::TABLE . " WHERE " . $map->name->name . " IS NULL");
        $sth->execute();

        self::assertCount(10, $sth->fetchRowSet());
    }

    /**
     * @throws EntityException
     */
    public function testGetPrimaryKeysForEntity()
    {
        $entity = new TestBaseNoPK();

        $this->assertException(DBDException::class, function () use ($entity) {
            $this->db->entityDelete($entity);
        }, sprintf(CRUD::ERROR_ENTITY_NO_PK, TestBaseNoPK::class));

        $this->assertException(DBDException::class, function () use ($entity) {
            $this->db->entityUpdate($entity);
        }, sprintf(CRUD::ERROR_ENTITY_NO_PK, TestBaseNoPK::class));

        $this->assertException(DBDException::class, function () use ($entity) {
            $this->db->entitySelect($entity);
        }, sprintf(CRUD::ERROR_ENTITY_NO_PK, TestBaseNoPK::class));

        $entity = new TestBase();
        $keys = $entity::map()->getPrimaryKey();
        $key = array_shift($keys);

        $this->assertException(DBDException::class, function () use ($entity) {
            $this->db->entityDelete($entity);
        }, sprintf(CRUD::ERROR_PK_IS_NULL, TestBase::class, $key->name));

        $this->assertException(DBDException::class, function () use ($entity) {
            $this->db->entityUpdate($entity);
        }, sprintf(CRUD::ERROR_PK_IS_NULL, TestBase::class, $key->name));

        $this->assertException(DBDException::class, function () use ($entity) {
            $this->db->entitySelect($entity);
        }, sprintf(CRUD::ERROR_PK_IS_NULL, TestBase::class, $key->name));
    }
}
