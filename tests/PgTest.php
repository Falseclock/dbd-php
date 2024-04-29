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

namespace DBD\Tests;

use DBD\Cache\MemCache;
use DBD\Common\Config;
use DBD\Common\CRUD;
use DBD\Common\DBDException;
use DBD\Common\Options;
use DBD\Entity\Common\EntityException;
use DBD\Pg;
use DBD\Tests\Entities\TestBaseNoAuto;
use DBD\Tests\Entities\TestBaseNullable;
use DBD\Tests\Entities\TestBaseNullable2;
use DBD\Tests\Entities\TestBaseNullable2Map;
use DBD\Tests\Entities\TestBaseNullableMap;
use DBD\Tests\Pg\PgAbstractTest;
use Psr\SimpleCache\InvalidArgumentException;

class PgTest extends PgAbstractTest
{
    /** @var Pg */
    protected $db;
    /** @var Config */
    protected $config;
    /**  @var MemCache */
    protected $memcache;
    /** @var Options */
    protected $options;

    /**
     * This should be last as transaction may be in fail state
     *
     * @throws DBDException
     *
     */
    public function testErrorQueryDirect()
    {
        $this->options->setPrepareExecute(true);
        $this->expectException(DBDException::class);
        $this->db->query("SELECT * FROM unknown_TABLE");
    }

    /**
     * @throws DBDException
     */
    public function testErrorQueryPrepare()
    {
        $this->options->setPrepareExecute(false);
        $this->expectException(DBDException::class);
        $this->db->query("SELECT * FROM unknown_TABLE");
    }

    /**
     * @throws DBDException
     */
    public function testCacheNoDriver()
    {
        $this->config->setCacheDriver(null);
        $this->db->prepare("SELECT 1");
        $this->db->cache(__METHOD__);

        self::expectNotToPerformAssertions();
    }

    /**
     * @throws DBDException
     */
    public function testCacheNoQuery()
    {
        $this->config->setCacheDriver($this->memcache);
        self::expectException(DBDException::class);
        self::expectExceptionMessage(CRUD::ERROR_STATEMENT_NOT_PREPARED);
        $this->db->cache(__METHOD__);
    }

    /**
     * @throws DBDException
     */
    public function testCacheNoSelect()
    {
        $this->config->setCacheDriver($this->memcache);

        self::expectException(DBDException::class);
        self::expectExceptionMessage(CRUD::ERROR_CACHING_NON_SELECT_QUERY);

        $sth = $this->db->prepare(" \t    \r\n\r\nDELETE FROM TEST WHERE SELECT");
        $sth->cache(__METHOD__);
    }

    public function testConstructWithoutOptions()
    {
        $db = new Pg($this->config);
        self::assertNotNull($db->getOptions());
    }

    /**
     * @throws DBDException
     * @noinspection SqlWithoutWhere
     */
    public function testDo()
    {
        $this->db->do("DROP TABLE IF EXISTS test_do");
        // Test regular
        self::assertSame(0, $this->db->do("CREATE TABLE test_do (id serial, test int)"));
        self::assertSame(1, $this->db->do("INSERT INTO test_do (test) VALUES (1)"));
        self::assertSame(3, $this->db->do("INSERT INTO test_do (test) VALUES (1),(1),(1)"));
        self::assertSame(4, $this->db->do("SELECT * FROM test_do"));
        self::assertSame(4, $this->db->do("UPDATE test_do SET test = 2"));
        self::assertSame(4, $this->db->do("SELECT * FROM test_do WHERE test = 2"));
        self::assertSame(4, $this->db->do("DELETE FROM test_do"));
        self::assertSame(0, $this->db->do("SELECT * FROM test_do"));

        // Test placeholder
        self::assertSame(1, $this->db->do("INSERT INTO test_do (test) VALUES (?)", 1));
        self::assertSame(3, $this->db->do("INSERT INTO test_do (test) VALUES (?),(?),(?)", 1, 1, 1));
        self::assertSame(4, $this->db->do("SELECT * FROM test_do"));
        self::assertSame(4, $this->db->do("UPDATE test_do SET test = ?", 2));
        self::assertSame(4, $this->db->do("SELECT * FROM test_do WHERE test = 2"));
        self::assertSame(4, $this->db->do("DELETE FROM test_do"));
        self::assertSame(0, $this->db->do("SELECT * FROM test_do"));

        self::assertSame(0, $this->db->do("DROP TABLE test_do"));

        self::expectException(DBDException::class);
        $this->db->do();
    }

    /**
     * @throws DBDException
     */
    public function testDriverDisconnection()
    {
        $this->config->setCacheDriver($this->memcache);
        $this->options->setConvertNumeric(true);
        $this->options->setConvertBoolean(true);
        $this->options->setUseDebug(true);

        $sth = $this->db->prepare("SELECT 1,2,3,4,5");
        $sth->cache(__METHOD__);
        $this->memcache->disconnect();
        self::expectException(DBDException::class);
        $sth->execute();
        $this->memcache->connect();
    }

    /**
     * @throws DBDException
     * @throws EntityException
     */
    public function testEntityBase()
    {
        $this->options->setConvertNumeric(true);
        $this->options->setConvertBoolean(true);

        /** @var TestBaseNullableMap $map */
        $map = TestBaseNullable::map();

        $this->db->do("DROP TABLE IF EXISTS " . TestBaseNullable::TABLE);
        $this->db->do("CREATE TABLE " . TestBaseNullable::TABLE . " (" . $map->id->name . " serial, " . $map->name->name . " text)");

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
     * @throws DBDException
     * @throws InvalidArgumentException
     */
    public function testExecuteFetchRowSetKeyWithCache()
    {
        $this->config->setCacheDriver($this->memcache);
        $this->options->setConvertNumeric(true);
        $this->options->setConvertBoolean(true);
        $this->options->setUseDebug(true);

        $this->db->do("DROP TABLE IF EXISTS testExecuteFetchRowSetKeyWithCache");
        $this->config->getCacheDriver()->delete(__METHOD__);

        $sth = $this->db->prepare("CREATE TABLE testExecuteFetchRowSetKeyWithCache AS SELECT id, id%2 > 0 AS bool_var from generate_series(1,10) id");
        $sth->execute();

        // taking from DB
        $sth = $this->db->prepare("SELECT * FROM testExecuteFetchRowSetKeyWithCache ORDER BY id");
        $sth->cache(__METHOD__);
        $sth->execute();

        $rows = $sth->fetchRowSet('id');
        self::assertCount(10, $rows);

        $id = 0;
        foreach ($rows as $id => $row) {
            self::assertSame($id, $row['id']);
        }

        self::assertSame(10, $id);

        // Execute again and data should be taken from cache
        $sth->execute();
        $rows = $sth->fetchRowSet('id');
        self::assertCount(10, $rows);

        foreach ($rows as $id => $row) {
            self::assertSame($id, $row['id']);
        }
        self::assertSame(10, $id);

        // drop cache and check again
        $this->config->getCacheDriver()->delete(__METHOD__);
        $sth->execute();

        $rows = $sth->fetchRowSet('id');
        self::assertCount(10, $rows);

        foreach ($rows as $id => $row) {
            self::assertSame($id, $row['id']);
        }
        self::assertSame(10, $id);
    }

    /**
     * @throws DBDException
     * @throws InvalidArgumentException
     */
    public function testExecuteFetchRowSetKeyWithCachePrepare()
    {
        $this->config->setCacheDriver($this->memcache);
        $this->options->setConvertNumeric(true);
        $this->options->setConvertBoolean(true);
        $this->options->setUseDebug(true);
        $this->options->setPrepareExecute(true);

        $this->db->do("DROP TABLE IF EXISTS testExecuteFetchRowSetKeyWithCachePrepare");
        $this->config->getCacheDriver()->delete(__METHOD__);

        $sth = $this->db->prepare("CREATE TABLE testExecuteFetchRowSetKeyWithCachePrepare AS SELECT id, id%2 > 0 AS bool_var from generate_series(1,10) id");
        $sth->execute();

        // taking from DB
        $sth = $this->db->prepare("SELECT * FROM testExecuteFetchRowSetKeyWithCachePrepare ORDER BY id");
        $sth->cache(__METHOD__);
        $sth->execute();

        $rows = $sth->fetchRowSet('id');
        self::assertCount(10, $rows);

        $id = 0;
        foreach ($rows as $id => $row) {
            self::assertSame($id, $row['id']);
        }

        self::assertSame(10, $id);

        // Execute again and data should be taken from cache
        $sth->execute();
        $rows = $sth->fetchRowSet('id');
        self::assertCount(10, $rows);

        foreach ($rows as $id => $row) {
            self::assertSame($id, $row['id']);
        }
        self::assertSame(10, $id);

        // drop cache and check again
        $this->config->getCacheDriver()->delete(__METHOD__);
        $sth->execute();

        $rows = $sth->fetchRowSet('id');
        self::assertCount(10, $rows);

        foreach ($rows as $id => $row) {
            self::assertSame($id, $row['id']);
        }
        self::assertSame(10, $id);
    }

    /**
     * @throws DBDException
     * @throws InvalidArgumentException
     */
    public function testExecuteFetchRowSetWithCache()
    {
        $this->config->setCacheDriver($this->memcache);
        $this->options->setConvertNumeric(true);
        $this->options->setConvertBoolean(true);
        $this->options->setUseDebug(true);

        $this->db->do("DROP TABLE IF EXISTS testExecuteFetchRowSetWithCache");
        $this->config->getCacheDriver()->delete(__METHOD__);

        $sth = $this->db->prepare("CREATE TABLE testExecuteFetchRowSetWithCache AS SELECT id, id%2 > 0 AS bool_var from generate_series(1,10) id");
        $sth->execute();

        // taking from DB
        $sth = $this->db->prepare("SELECT * FROM testExecuteFetchRowSetWithCache ORDER BY id");
        $sth->cache(__METHOD__);
        $sth->execute();

        $rows = $sth->fetchRowSet();
        self::assertCount(10, $rows);

        $i = 0;
        foreach ($rows as $row) {
            $i++;
            self::assertSame($i, $row['id']);
        }

        self::assertSame(10, $i);

        // Execute again and data should be taken from cache
        $sth->execute();
        $rows = $sth->fetchRowSet();
        self::assertCount(10, $rows);

        $i = 0;
        foreach ($rows as $row) {
            $i++;
            self::assertSame($i, $row['id']);
        }
        self::assertSame(10, $i);

        // drop cache and check again
        $this->config->getCacheDriver()->delete(__METHOD__);
        $sth->execute();

        $rows = $sth->fetchRowSet();
        self::assertCount(10, $rows);

        $i = 0;
        foreach ($rows as $row) {
            $i++;
            self::assertSame($i, $row['id']);
        }
        self::assertSame(10, $i);
    }

    /**
     * @throws DBDException
     * @throws InvalidArgumentException
     */
    public function testExecuteFetchRowWithCache()
    {
        $this->config->setCacheDriver($this->memcache);
        $this->options->setConvertNumeric(true);
        $this->options->setConvertBoolean(true);
        $this->options->setUseDebug(true);

        $this->db->do("DROP TABLE IF EXISTS testExecuteWithCache");
        $this->config->getCacheDriver()->delete(__METHOD__);

        $sth = $this->db->prepare("CREATE TABLE testExecuteWithCache AS SELECT id, id%2 > 0 AS bool_var from generate_series(1,10) id");
        $sth->execute();

        $sth = $this->db->prepare("SELECT * FROM testExecuteWithCache ORDER BY id");
        $sth->cache(__METHOD__);
        $sth->execute();

        $i = 0;
        while ($row = $sth->fetchRow()) {
            $i++;
            self::assertSame($i, $row['id']);
        }

        self::assertSame(10, $i);

        // Execute again and data should be taken from cache
        $sth->execute();

        $i = 0;
        while ($row = $sth->fetchRow()) {
            $i++;
            self::assertSame($i, $row['id']);
        }
        self::assertSame(10, $i);

        // drop cache and check again
        $this->config->getCacheDriver()->delete(__METHOD__);
        $sth->execute();

        $i = 0;
        while ($row = $sth->fetchRow()) {
            $i++;
            self::assertSame($i, $row['id']);
        }
        self::assertSame(10, $i);
    }

    /**
     * @throws DBDException
     * @throws InvalidArgumentException
     */
    public function testExecuteFetchWithCache()
    {
        $this->config->setCacheDriver($this->memcache);
        $this->options->setConvertNumeric(true);
        $this->options->setConvertBoolean(true);
        $this->options->setUseDebug(true);

        $this->db->do("DROP TABLE IF EXISTS testExecuteWithCache");
        $this->config->getCacheDriver()->delete(__METHOD__);

        $sth = $this->db->prepare("CREATE TABLE testExecuteWithCache AS SELECT id, id%2 > 0 AS bool_var from generate_series(1,10) id");
        $sth->execute();

        $sth = $this->db->prepare("SELECT * FROM testExecuteWithCache ORDER BY id LIMIT 1");
        $sth->cache(__METHOD__);
        $sth->execute();

        $i = 0;
        while ($value = $sth->fetch()) {
            $i++;
            switch ($i) {
                case 1:
                    self::assertSame($i, $value);
                    break;
                case 2:
                    self::assertTrue($value);
                    break;
            }
        }

        self::assertSame(2, $i);

        // Execute again and data should be taken from cache
        $sth->execute();

        $i = 0;
        while ($value = $sth->fetch()) {
            $i++;
            switch ($i) {
                case 1:
                    self::assertSame($i, $value);
                    break;
                case 2:
                    self::assertTrue($value);
                    break;
            }
        }

        // drop cache and check again
        $this->config->getCacheDriver()->delete(__METHOD__);
        $sth->execute();

        $i = 0;
        while ($value = $sth->fetch()) {
            $i++;
            switch ($i) {
                case 1:
                    self::assertSame($i, $value);
                    break;
                case 2:
                    self::assertTrue($value);
                    break;
            }
        }
    }

    /**
     * @throws DBDException
     */
    public function testFetch()
    {
        $this->options->setConvertNumeric(false);
        $this->options->setConvertBoolean(false);
        $sth = $this->db->prepare("SELECT 1::smallint, 1::text, 2::int, 2::text, 3::bigint, 3::text, true, true::text, false, false::text");
        $sth->execute();
        $i = 0;
        while ($value = $sth->fetch()) {
            switch ($i) {
                case 0:
                case 1:
                    self::assertSame("1", $value);
                    break;
                case 2:
                case 3:
                    self::assertSame("2", $value);
                    break;
                case 4:
                case 5:
                    self::assertSame("3", $value);
                    break;
                case 6:
                    self::assertSame("t", $value);
                    break;
                case 7:
                    self::assertSame("true", $value);
                    break;
                case 8:
                    self::assertSame("f", $value);
                    break;
                case 9:
                    self::assertSame("false", $value);
                    break;
            }
            $i++;
        }
        self::assertSame(10, $i);

        $this->options->setConvertNumeric(true);
        $this->options->setConvertBoolean(true);
        $sth = $this->db->prepare("SELECT 1::smallint, 1::text, 2::int, 2::text, 3::bigint, 3::text, true, true::text, false, false::text");
        $sth->execute();
        $i = 0;
        while ($value = $sth->fetch()) {
            switch ($i) {
                case 0:
                    self::assertSame(1, $value);
                    break;
                case 1:
                    self::assertSame("1", $value);
                    break;
                case 2:
                    self::assertSame(2, $value);
                    break;
                case 3:
                    self::assertSame("2", $value);
                    break;
                case 4:
                    self::assertSame(3, $value);
                    break;
                case 5:
                    self::assertSame("3", $value);
                    break;
                case 6:
                    self::assertIsBool($value);
                    self::assertTrue($value);
                    break;
                case 7:
                    self::assertSame("true", $value);
                    break;
                case 8:
                case 9:
                    throw new DBDException("impossible situation");
            }
            $i++;
        }
        // Last two columns are false while ($value = $sth->fetch()) exits
        self::assertSame(8, $i);
    }

    /**
     * @throws DBDException
     */
    public function testFetchRow()
    {
        $sth = $this->db->prepare("CREATE TABLE test_fetch_row AS SELECT id, id%2 > 0 AS bool_var from generate_series(1,10) id");
        $sth->execute();

        $this->options->setConvertNumeric(false);
        $this->options->setConvertBoolean(false);

        $sth = $this->db->prepare("SELECT * FROM test_fetch_row ORDER BY id");
        $sth->execute();

        $i = 0;
        while ($row = $sth->fetchRow()) {
            $i++;
            self::assertSame((string)$i, $row['id']);

            if ($i % 2)
                self::assertSame("t", $row['bool_var']);
            else
                self::assertSame("f", $row['bool_var']);
        }
        self::assertSame(10, $i);

        $this->options->setConvertNumeric(true);
        $this->options->setConvertBoolean(true);

        $sth = $this->db->prepare("SELECT * FROM test_fetch_row ORDER BY id");
        $sth->execute();

        $i = 0;
        while ($row = $sth->fetchRow()) {
            $i++;
            self::assertSame($i, $row['id']);

            if ($i % 2)
                self::assertTrue($row['bool_var']);
            else
                self::assertFalse($row['bool_var']);
        }
        self::assertSame(10, $i);

        $this->db->do("DROP TABLE test_fetch_row");
    }

    /**
     * @throws DBDException
     */
    public function testFetchRowSet()
    {
        $this->db->do("DROP TABLE IF EXISTS test_fetch_row_set");

        $sth = $this->db->prepare("CREATE TABLE test_fetch_row_set AS SELECT id, id%2 > 0 AS bool_var from generate_series(1,10) id");
        $sth->execute();

        $this->options->setConvertNumeric(false);
        $this->options->setConvertBoolean(false);
        $sth = $this->db->prepare("SELECT * FROM test_fetch_row_set ORDER BY id");
        $sth->execute();

        $set = $sth->fetchRowSet();

        self::assertCount(10, $set);

        $i = 0;
        foreach ($set as $row) {
            $i++;
            self::assertSame((string)$i, $row['id']);

            if ($i % 2)
                self::assertSame("t", $row['bool_var']);
            else
                self::assertSame("f", $row['bool_var']);
        }

        $this->options->setConvertNumeric(true);
        $this->options->setConvertBoolean(true);

        $sth = $this->db->prepare("SELECT * FROM test_fetch_row_set ORDER BY id");
        $sth->execute();

        $set = $sth->fetchRowSet();

        self::assertCount(10, $set);

        $i = 0;
        foreach ($set as $row) {
            $i++;
            self::assertSame($i, $row['id']);

            if ($i % 2)
                self::assertTrue($row['bool_var']);
            else
                self::assertFalse($row['bool_var']);
        }
    }

    /**
     * @throws DBDException
     */
    public function testFetchRowSetWithKey()
    {
        $this->db->do("DROP TABLE IF EXISTS test_fetch_row_set");

        $sth = $this->db->prepare("CREATE TABLE test_fetch_row_set AS SELECT id, id%2 > 0 AS bool_var from generate_series(1,10) id");
        $sth->execute();

        $this->options->setConvertNumeric(false);
        $this->options->setConvertBoolean(false);
        $sth = $this->db->prepare("SELECT * FROM test_fetch_row_set ORDER BY id");
        $sth->execute();

        $set = $sth->fetchRowSet('id');

        self::assertCount(10, $set);

        $i = 0;
        foreach ($set as $id => $row) {
            $i++;
            self::assertSame($i, $id);
            self::assertSame((string)$i, $row['id']);

            if ($i % 2)
                self::assertSame("t", $row['bool_var']);
            else
                self::assertSame("f", $row['bool_var']);
        }

        $this->options->setConvertNumeric(true);
        $this->options->setConvertBoolean(true);

        $sth = $this->db->prepare("SELECT * FROM test_fetch_row_set ORDER BY id");
        $sth->execute();

        $set = $sth->fetchRowSet('id');

        self::assertCount(10, $set);

        $i = 0;
        foreach ($set as $id => $row) {
            $i++;
            self::assertSame($i, $id);
            self::assertSame($i, $row['id']);

            if ($i % 2)
                self::assertTrue($row['bool_var']);
            else
                self::assertFalse($row['bool_var']);
        }

        $this->db->do("INSERT INTO test_fetch_row_set(id) VALUES (?)", 1);
        $sth = $this->db->prepare("SELECT * FROM test_fetch_row_set ORDER BY id");
        $sth->execute();

        self::expectException(DBDException::class);
        $sth->fetchRowSet('id');
    }

    public function testGetOptions()
    {
        self::assertInstanceOf(Options::class, $this->db->getOptions());
    }

    /**
     * @throws DBDException
     */
    public function testGetPreparedQuery()
    {
        $sth = $this->db->prepare("SELECT 1, ?");
        self::expectException(DBDException::class);
        $sth->execute();
    }

    /**
     * @throws DBDException
     * @throws InvalidArgumentException
     */
    public function testNoRows()
    {
        $this->config->setCacheDriver($this->memcache);
        $this->options->setConvertNumeric(true);
        $this->options->setConvertBoolean(true);
        $this->options->setUseDebug(true);

        $this->db->do("DROP TABLE IF EXISTS testNoRows");
        $this->config->getCacheDriver()->delete(__METHOD__);

        $sth = $this->db->prepare("CREATE TABLE testNoRows AS SELECT id, id%2 > 0 AS bool_var from generate_series(1,10) id");
        $sth->execute();

        $sth = $this->db->prepare("SELECT * FROM testNoRows  WHERE id > ?");
        $sth->cache(__METHOD__);
        $sth->execute(1000);

        $i = 0;
        while ($sth->fetchRow()) {
            $i++;
        }

        self::assertSame(0, $i);

        // Execute again and data should be taken from cache
        $sth->execute(1000);

        $i = 0;
        while ($sth->fetchRow()) {
            $i++;
        }
        self::assertSame(0, $i);

        // drop cache and check again
        $this->config->getCacheDriver()->delete(__METHOD__);
        $sth->execute(1000);

        $i = 0;
        while ($sth->fetchRow()) {
            $i++;
        }
        self::assertSame(0, $i);
    }

    /**
     * @throws DBDException
     */
    public function testPrepare()
    {
        // Preparing different queries
        $sta = $this->db->prepare("SELECT 1");
        $optionsA = $sta->getOptions();
        $stb = $this->db->prepare("SELECT 2");
        $optionsB = $stb->getOptions();
        $stc = $this->db->prepare("SELECT 3");
        $optionsC = $stc->getOptions();

        // Assert that all instances have same options
        self::assertSame($optionsA, $optionsB);
        self::assertSame($optionsB, $optionsC);
        self::assertSame($optionsC, $optionsA);

        // Resetting onDemand and check
        $onDemand = $optionsC->isOnDemand();
        $optionsC->setOnDemand(!$onDemand);
        self::assertSame($optionsA->isOnDemand(), $optionsB->isOnDemand());
        self::assertSame($optionsB->isOnDemand(), $optionsC->isOnDemand());
        self::assertSame($optionsC->isOnDemand(), $optionsA->isOnDemand());

        $sta->execute();
        $stb->execute();
        $stc->execute();

        // checking each handler has own result
        self::assertEquals(1, $sta->fetch());
        self::assertEquals(2, $stb->fetch());
        self::assertEquals(3, $stc->fetch());

        self::expectException(DBDException::class);
        $this->db->prepare("");
    }

    /**
     * @throws DBDException
     */
    public function testSelect()
    {
        $this->options->setConvertNumeric(true);
        self::assertSame(1, $this->db->select("SELECT COUNT(1), 2"));
        $this->options->setConvertNumeric(false);
        self::assertSame("1", $this->db->select("SELECT COUNT(1), 2"));

        $this->options->setConvertBoolean(true);
        self::assertSame(true, $this->db->select("SELECT true, COUNT(1), 2"));
        $this->options->setConvertBoolean(false);
        self::assertSame("t", $this->db->select("SELECT true, COUNT(1), 2"));

        $this->assertException(DBDException::class, function () {
            $this->db->select("DROP TABLE fake_table");
        });

        self::assertNull($this->db->select("DROP VIEW IF EXISTS non_exist"));
    }
}
