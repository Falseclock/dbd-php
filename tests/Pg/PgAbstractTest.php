<?php
/**
 * PgAbstractTest
 *
 * @author    Nurlan Mukhanov <nurike@gmail.com>
 * @copyright 2021 Nurlan Mukhanov
 * @license   https://en.wikipedia.org/wiki/MIT_License MIT License
 * @link      https://github.com/Falseclock/dbd-php
 * @noinspection PhpComposerExtensionStubsInspection
 */

declare(strict_types=1);

namespace DBD\Tests\Pg;

use DBD\Base\Config;
use DBD\Base\Options;
use DBD\Cache\MemCache;
use DBD\Common\DBDException;
use DBD\Pg;
use DBD\Tests\CommonTest;

abstract class PgAbstractTest extends CommonTest
{
    /** @var Pg */
    protected $db;
    /**  @var MemCache */
    protected $memcache;

    /**
     * @throws DBDException
     */
    public function __construct(?string $name = null, array $data = [], $dataName = '')
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
     * @throws DBDException
     */
    public function __destruct()
    {
        $this->db->disconnect();
        $this->memcache->disconnect();
    }
}
