<?php
/**
 * @author    Nurlan Mukhanov <nurike@gmail.com>
 * @copyright 2009-2022 Nurlan Mukhanov
 * @license   https://en.wikipedia.org/wiki/MIT_License MIT License
 * @link      https://github.com/Falseclock/dbd-php
 * @noinspection SqlNoDataSourceInspection
 */

declare(strict_types=1);

namespace DBD\Tests\Pg;

use DBD\Common\CRUD;
use DBD\Common\DBDException;
use DBD\Entity\Common\EntityException;
use DBD\Tests\Entities\City;
use DBD\Tests\Entities\Country;
use DBD\Tests\Entities\TestBaseJson;
use DBD\Tests\Entities\TestBaseJsonMap;

class PgEntityTest extends PgAbstractTest
{
    /**
     * @throws EntityException
     * @throws DBDException
     */
    public function testForJsonConversion()
    {
        $this->db->getOptions()->setConvertNumeric(true);
        $this->db->do(sprintf("DROP TABLE IF EXISTS %s", TestBaseJson::TABLE));
        $array = ['foo' => true, 'bar' => false];

        $entity = new TestBaseJson();
        $entity->value = $array;
        $entity->id = 1;

        $this->assertException(DBDException::class, function () use ($entity) {
            $this->db->entityUpdate($entity);
        });

        self::assertIsString($entity->value);
        self::assertEquals(json_encode($array, JSON_UNESCAPED_UNICODE), $entity->value);

        //--------------------------------------------------
        $this->db->do(sprintf("CREATE TABLE IF NOT EXISTS %s (%s serial, %s json)", TestBaseJson::TABLE, TestBaseJsonMap::me()->id->name, TestBaseJsonMap::me()->value->name));

        unset($entity->id);
        $this->db->entityInsert($entity);
        self::assertSame(1, $entity->id);

        $array = [1, 2, 3, 4, 5];
        $entity->value = $array;
        $this->db->entityUpdate($entity);
        self::assertSame($array, $entity->value);

        // get from database and compare with current
        $check = new TestBaseJson();
        $check->id = 1;
        $this->db->entitySelect($check);

        self::assertSame($check->value, $entity->value);

        $this->db->do(sprintf("DROP TABLE IF EXISTS %s", TestBaseJson::TABLE));
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
}
