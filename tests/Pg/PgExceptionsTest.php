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
use DBD\Tests\Common\BadCacheDriver;
use DBD\Tests\Entities\TestBase;
use DBD\Tests\Entities\TestBaseNoPK;

class PgExceptionsTest extends PgAbstractTest
{
    /**
     * @throws DBDException
     */
    public function testFetchRowSet()
    {
        $sth = $this->db->prepare("SELECT *  FROM (VALUES ('Hello world',1),('Hello world',2),('Hello world',3),('Hello world',1)) t1 (col1,col2)");
        $sth->execute();

        $this->assertException(DBDException::class, function () use ($sth) {
            $sth->fetchRowSet('col2');
        });

        $sth->cache(__METHOD__);
        $sth->execute();

        $this->assertException(DBDException::class, function () use ($sth) {
            $sth->fetchRowSet('col2');
        });
    }

    /**
     * @throws DBDException
     */
    public function testExecuteExceptions()
    {
        $this->memcache = new BadCacheDriver([]);
        $this->config->setCacheDriver($this->memcache);

        $sth = $this->db->prepare("SELECT 1");
        $sth->cache(__METHOD__, "1s");

        $this->assertException(DBDException::class, function () use ($sth) {
            $sth->execute();
        });
    }

    public function testGetPreparedQuery()
    {
        $this->assertException(DBDException::class, function () {
            $this->db->execute();
        }, CRUD::ERROR_NOT_PREPARED);
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
