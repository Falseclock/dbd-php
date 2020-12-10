<?php
/**
 * PgTest
 *
 * @author    Nurlan Mukhanov <nurike@gmail.com>
 * @copyright 2020 Nurlan Mukhanov
 * @license   https://en.wikipedia.org/wiki/MIT_License MIT License
 * @link      https://github.com/Falseclock/dbd-php
 */

namespace DBD\Tests;

use DBD\Base\Config;
use DBD\Base\Options;
use DBD\Cache\MemCache;
use DBD\Common\DBDException;
use DBD\Pg;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\InvalidArgumentException;
use ReflectionException;

class PgTest extends TestCase
{
    /** @var Pg */
    private $db;
    /** @var Options */
    private $options;
    /** @var Config */
    private $config;

    /**
     * PgTest constructor.
     * @param null $name
     * @param array $data
     * @param string $dataName
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
        $memcache = new MemCache([[MemCache::HOST => '127.0.0.1', MemCache::PORT => 11211]]);
        $memcache->connect();

        $this->config = new Config($host, $port, $database, $user, $password);
        $this->config->setCacheDriver($memcache);

        $this->options = new Options();
        $this->db = new Pg($this->config, $this->options);
        $this->db->connect();
    }

    public function testConstructWithoutOptions()
    {
        $db = new Pg($this->config);
        self::assertNotNull($db->getOptions());
    }

    /**
     * @throws DBDException
     * @throws InvalidArgumentException
     * @throws ReflectionException
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
     * @throws InvalidArgumentException
     * @throws ReflectionException
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
     * @throws InvalidArgumentException
     * @throws ReflectionException
     * @noinspection SqlResolve
     * @noinspection SqlWithoutWhere
     */
    public function testDo()
    {
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
     * @throws InvalidArgumentException
     * @throws ReflectionException
     * @noinspection SqlResolve
     * @noinspection SqlWithoutWhere
     */
    public function testQuery()
    {
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
     * @throws InvalidArgumentException
     * @throws ReflectionException
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
     * @throws InvalidArgumentException
     * @throws ReflectionException
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
     */
    public function testConnect()
    {
        $this->db->disconnect();
        self::assertInstanceOf(Pg::class, $this->db->connect());
        $this->db->disconnect();

        $this->db->getOptions()->setOnDemand(!$this->db->getOptions()->isOnDemand());
        self::assertInstanceOf(Pg::class, $this->db->connect());

    }

    /**
     * @throws DBDException
     * @throws InvalidArgumentException
     * @throws ReflectionException
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

    /**
     * @throws DBDException
     * @throws InvalidArgumentException
     * @throws ReflectionException
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
                    self::assertIsBool($value);
                    self::assertFalse($value);
                    break;
                case 9:
                    self::assertSame("false", $value);
                    break;
            }
            $i++;
        }
    }

    /**
     * @throws DBDException
     * @throws InvalidArgumentException
     * @throws ReflectionException
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

    public function testEscape()
    {
        self::assertSame("''''", $this->db->escape("'"));
    }

    public function testGetOptions()
    {
        self::assertInstanceOf(Options::class, $this->db->getOptions());
    }
}
