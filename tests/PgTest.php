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
use DBD\Common\DBDException;
use DBD\Common\Options;
use DBD\Pg;
use DBD\Tests\Traits\PgUtilsTest;

class PgTest extends DBDTest
{
    use PgUtilsTest;

    /** @var Pg */
    protected $db;
    /**  @var MemCache */
    protected $cache;

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

        $this->config = new Config($host, $port, $database, $user, $password);
        $this->config->setCacheDriver($this->cache);

        $this->options = new Options();
        $this->options->setUseDebug(true);
        $this->db = new Pg($this->config, $this->options);
        $this->db->connect();
    }
}
