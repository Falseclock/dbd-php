<?php
/**
 * PgTest
 *
 * @author    Nurlan Mukhanov <nurike@gmail.com>
 * @copyright 2020 Nurlan Mukhanov
 * @license   https://en.wikipedia.org/wiki/MIT_License MIT License
 * @link      https://github.com/Falseclock/dbd-php
 */

declare(strict_types=1);

namespace DBD\Tests;

use DBD\Base\Bind;
use DBD\Base\Config;
use DBD\Base\Options;
use DBD\Cache\MemCache;
use DBD\Common\DBDException;
use DBD\Entity\Common\EntityException;
use DBD\Entity\Primitive;
use DBD\Pg;
use DBD\Tests\Entities\TestBaseNoAuto;
use DBD\Tests\Entities\TestBaseNullable;
use DBD\Tests\Entities\TestBaseNullable2;
use DBD\Tests\Entities\TestBaseNullable2Map;
use DBD\Tests\Entities\TestBaseNullableMap;
use Exception;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\InvalidArgumentException;

class PgTest extends TestCase
{
    /** @var Config */
    private $config;
    /** @var Pg */
    private $db;
    /**  @var MemCache */
    private $memcache;
    /** @var Options */
    private $options;

    /**
     * PgTest constructor.
     *
     * @param null $name
     * @param array $data
     * @param string $dataName
     *
     * @throws DBDException
     */
    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $host = getenv('PGHOST') ?: 'localhost';
        $port = intval(getenv('PGPORT')) ?: 5432;
        $database = getenv('PGDATABASE') ?: 'dbd_tests';
        $user = getenv('PGUSER') ?: 'postgres';
        $password = getenv('PGPASSWORD') ?: '';

        // @todo make connection to cache on demand
        $this->memcache = new MemCache([[MemCache::HOST => '127.0.0.1', MemCache::PORT => 11211]]);
        $this->memcache->connect();

        $this->config = new Config($host, $port, $database, $user, $password);
        $this->config->setCacheDriver($this->memcache);

        $this->options = new Options();
        $this->options->setUseDebug(true);
        $this->db = new Pg($this->config, $this->options);
        $this->db->connect();
    }

    /**
     * This should be last as transaction may be in fail state
     *
     * @throws DBDException
     * @noinspection SqlResolve
     */
    public function tAAAAAAAAAAAAAAAAestErrorQueryDirect()
    {
        $this->options->setPrepareExecute(true);
        $this->expectException(DBDException::class);
        $this->db->query("SELECT * FROM unknown_TABLE");
    }

    /**
     * @throws DBDException
     * @noinspection SqlResolve
     */
    public function tAAAAAAAAAAAAAAestErrorQueryPrepare()
    {
        $this->options->setPrepareExecute(false);
        $this->expectException(DBDException::class);
        $this->db->query("SELECT * FROM unknown_TABLE");
    }

    /**
     * @throws DBDException
     */
    public function testBegin()
    {
        self::assertTrue($this->db->begin());
        $sth = $this->db->prepare("SELECT version()");
        $sth->execute();
        self::assertTrue($this->db->commit());

        self::assertTrue($this->db->begin());
        self::expectException(DBDException::class);

        $this->db->begin();
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
        self::expectExceptionMessage("SQL statement not prepared");
        $this->db->cache(__METHOD__);
    }

    /**
     * @throws DBDException
     */
    public function testCacheNoSelect()
    {
        $this->config->setCacheDriver($this->memcache);

        self::expectException(DBDException::class);
        self::expectExceptionMessage("Caching setup failed, current query is not of SELECT type");

        $sth = $this->db->prepare(" \t    \r\n\r\nDELETE FROM TEST WHERE SELECT");
        $sth->cache(__METHOD__);
    }

    /**
     * @throws DBDException
     */
    public function testCommitWithoutConnection()
    {
        $this->db->disconnect();
        self::expectException(DBDException::class);
        $this->db->commit();
    }

    /**
     * @throws DBDException
     */
    public function testCommitWithoutTransaction()
    {
        $this->options->setOnDemand(false);
        $this->db->connect();
        self::expectException(DBDException::class);
        $this->db->commit();
    }

    /**
     * @throws DBDException
     */
    public function testConnect()
    {
        $this->db->disconnect();
        self::assertInstanceOf(Pg::class, $this->db->connect());
        $this->db->disconnect();

        $this->db->getOptions()->setOnDemand(!$this->db->getOptions()->isOnDemand());
        self::assertInstanceOf(Pg::class, $this->db->connect());
    }

    public function testConstructWithoutOptions()
    {
        $db = new Pg($this->config);
        self::assertNotNull($db->getOptions());
    }

    /**
     * @throws DBDException
     */
    public function testDisconnect()
    {
        $this->db->do("SELECT 1");
        self::assertInstanceOf(Pg::class, $this->db->disconnect());
        self::assertInstanceOf(Pg::class, $this->db->disconnect());
        $this->db->begin();
        self::expectException(DBDException::class);
        $this->db->disconnect();
    }

    /**
     * @throws DBDException
     * @noinspection SqlResolve
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

    public function testEscape()
    {
        self::assertSame("''''", $this->db->escape("'"));
    }

    /**
     * @throws DBDException
     * @throws InvalidArgumentException
     * @noinspection SqlResolve
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
     * @noinspection SqlResolve
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
     * @noinspection SqlResolve
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
     * @noinspection SqlResolve
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
     * @noinspection SqlResolve
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
     * @noinspection SqlResolve
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
     * @noinspection SqlResolve
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
     * @noinspection SqlResolve
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
     * @noinspection SqlResolve
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
        while ($row = $sth->fetchRow()) {
            $i++;
        }

        self::assertSame(0, $i);

        // Execute again and data should be taken from cache
        $sth->execute(1000);

        $i = 0;
        while ($row = $sth->fetchRow()) {
            $i++;
        }
        self::assertSame(0, $i);

        // drop cache and check again
        $this->config->getCacheDriver()->delete(__METHOD__);
        $sth->execute(1000);

        $i = 0;
        while ($row = $sth->fetchRow()) {
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
     * @noinspection SqlResolve
     * @noinspection SqlWithoutWhere
     */
    public function testQuery()
    {
        $this->db->do("DROP TABLE IF EXISTS test_query");
        // Test regular
        self::assertInstanceOf(Pg::class, $this->db->query("CREATE TABLE test_query (id serial, test int)"));
        self::assertInstanceOf(Pg::class, $this->db->query("INSERT INTO test_query (test) VALUES (1)"));
        self::assertInstanceOf(Pg::class, $this->db->query("INSERT INTO test_query (test) VALUES (1),(1),(1)"));
        self::assertInstanceOf(Pg::class, $this->db->query("SELECT * FROM test_query"));
        self::assertInstanceOf(Pg::class, $this->db->query("UPDATE test_query SET test = 2"));
        self::assertInstanceOf(Pg::class, $this->db->query("SELECT * FROM test_query WHERE test = 2"));
        self::assertInstanceOf(Pg::class, $this->db->query("DELETE FROM test_query"));
        self::assertInstanceOf(Pg::class, $this->db->query("SELECT * FROM test_query"));

        // Test placeholder
        self::assertInstanceOf(Pg::class, $this->db->query("INSERT INTO test_query (test) VALUES (?)", 1));
        self::assertInstanceOf(Pg::class, $this->db->query("INSERT INTO test_query (test) VALUES (?),(?),(?)", 1, 1, 1));
        self::assertInstanceOf(Pg::class, $this->db->query("SELECT * FROM test_query"));
        self::assertInstanceOf(Pg::class, $this->db->query("UPDATE test_query SET test = ?", 2));
        self::assertInstanceOf(Pg::class, $this->db->query("SELECT * FROM test_query WHERE test = 2"));
        self::assertInstanceOf(Pg::class, $this->db->query("DELETE FROM test_query"));
        self::assertInstanceOf(Pg::class, $this->db->query("SELECT * FROM test_query"));

        self::assertInstanceOf(Pg::class, $this->db->query("DROP TABLE test_query"));

        self::expectException(DBDException::class);
        $this->db->query();
    }

    /**
     * @throws DBDException
     */
    public function testRollback()
    {
        // starting transaction
        self::assertTrue($this->db->begin());

        // create table
        self::assertSame(0, $this->db->do("CREATE TABLE test_rollback (id INT)"));

        // check table is created
        $sth = $this->db->prepare("SELECT 'public.test_rollback'::regclass");
        self::assertInstanceOf(Pg::class, $sth);
        self::assertIsResource($sth->execute());
        self::assertSame("test_rollback", $sth->fetch());

        // rollback
        self::assertTrue($this->db->rollback());

        // check table not exist
        self::expectException(DBDException::class);
        $this->db->do("SELECT 'public.test_rollback'::regclass");
    }

    /**
     * @throws DBDException
     */
    public function testRollbackWithoutBegin()
    {
        self::expectException(DBDException::class);
        $this->db->rollback();
    }

    /**
     * @throws DBDException
     * @noinspection SqlResolve
     * @noinspection SqlWithoutWhere
     */
    public function testRows()
    {
        $sth = $this->db->query("SELECT 1 UNION SELECT 2 UNION SELECT 3");
        self::assertSame(3, $sth->rows());

        $sth = $this->db->query("SELECT 1");
        self::assertSame(1, $sth->rows());

        $sth = $this->db->query("DROP TABLE IF EXISTS fake_table");
        self::assertSame(0, $sth->rows());

        $sth = $this->db->prepare("SELECT 1 UNION SELECT 2 UNION SELECT 3");
        $sth->execute();
        self::assertSame(3, $sth->rows());

        $sth = $this->db->prepare("SELECT 1");
        $sth->execute();
        self::assertSame(1, $sth->rows());

        $sth = $this->db->prepare("DROP TABLE IF EXISTS fake_table");
        $sth->execute();
        self::assertSame(0, $sth->rows());

        // Test through prepare
        $sth = $this->db->prepare("CREATE TABLE test_rows AS SELECT test, MD5(random()::text) from generate_series(1,10) test");
        $sth->execute();
        self::assertSame(10, $sth->rows());
        self::assertSame(0, $this->db->do("DROP TABLE test_rows"));

        // Test through do
        self::assertSame(10, $this->db->do("CREATE TABLE test_rows AS SELECT test, MD5(random()::text) from generate_series(1,10) test"));
        self::assertSame(0, $this->db->do("DROP TABLE test_rows"));

        // Test through query
        $sth = $this->db->query("CREATE TABLE test_rows AS SELECT test, MD5(random()::text) from generate_series(1,10) test");
        self::assertSame(10, $sth->rows());

        // Test through prepare
        $sth = $this->db->prepare("SELECT * FROM test_rows");
        $sth->execute();
        self::assertSame(10, $sth->rows());

        // Test through do
        self::assertSame(10, $this->db->do("SELECT * FROM test_rows"));

        // Test through query
        $sth = $this->db->query("SELECT * FROM test_rows");
        self::assertSame(10, $sth->rows());

        // -------------- UPDATE --------------

        // Test through prepare
        $sth = $this->db->prepare("UPDATE test_rows SET test = null");
        $sth->execute();
        self::assertSame(10, $sth->rows());

        // Test through do
        self::assertSame(10, $this->db->do("UPDATE test_rows SET test = null"));

        // Test through query
        $sth = $this->db->query("UPDATE test_rows SET test = null");
        self::assertSame(10, $sth->rows());

        // -------------- DELETION --------------

        // Test through prepare
        $sth = $this->db->prepare("DELETE FROM test_rows");
        $sth->execute();
        self::assertSame(10, $sth->rows());

        // Test through do
        self::assertSame(10, $this->db->do("INSERT INTO test_rows (test, md5) SELECT test, MD5(random()::text) from generate_series(1,10) test"));
        self::assertSame(10, $this->db->do("DELETE FROM test_rows"));

        // Test through query
        self::assertSame(10, $this->db->do("INSERT INTO test_rows (test, md5) SELECT test, MD5(random()::text) from generate_series(1,10) test"));
        $sth = $this->db->query("DELETE FROM test_rows");
        self::assertSame(10, $sth->rows());

        self::assertSame(0, $this->db->do("DROP TABLE test_rows"));
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

        self::expectException(DBDException::class);
        $this->db->select("DROP TABLE IF EXISTS fake_table");
    }

    public function testBindNotInteger16()
    {
        self::expectException(DBDException::class);
        new Bind(':some', '1', Primitive::Int16);
    }

    public function testBindNotInteger32()
    {
        self::expectException(DBDException::class);
        new Bind(':some', '1', Primitive::Int32);
    }

    public function testBindNotInteger64()
    {
        self::expectException(DBDException::class);
        new Bind(':some', '1', Primitive::Int64);
    }

    public function testBindNotIntegerArray16()
    {
        self::expectException(DBDException::class);
        new Bind(':some', [1, '1', 2], Primitive::Int16);
    }

    public function testBindNotIntegerArray32()
    {
        self::expectException(DBDException::class);
        new Bind(':some', [1, '1', 2], Primitive::Int32);
    }

    public function testBindNotIntegerArray64()
    {
        self::expectException(DBDException::class);
        new Bind(':some', [1, '1', 2], Primitive::Int64);
    }

    /**
     * @throws DBDException
     * @throws Exception
     */
    public function testBind()
    {
        $binary = 'binary';
        $sth = $this->db->prepare("
            SELECT 
            :int as first, 
            :string as seconds, 
            :binary::bytea as third, 
            ?::smallint as fourth, 
            ?::text as fifth,
            ARRAY[:int_array] as array_of_int
        ")
            ->bind(':int', 1, Primitive::Int16)
            ->bind(':string', 'some string')
            ->bind(':binary', $binary, Primitive::Binary)
            ->bind(':int_array', [1, 2, 3, 4, 5], Primitive::Int16);

        $sth->execute(2, 'another string');
        $row = $sth->fetchRow();

        self::assertIsArray($row);
        self::assertEquals(1, $row['first']);
        self::assertSame('some string', $row['seconds']);
        self::assertSame($binary, hex2bin(substr($row['third'], 2)));
        self::assertEquals(2, $row['fourth']);
        self::assertSame('another string', $row['fifth']);
        self::assertSame('{1,2,3,4,5}', $row['array_of_int']);

    }

}
