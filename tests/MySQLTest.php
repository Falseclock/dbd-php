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
use DBD\MySQL;

class MySQLTest extends DBDTest
{
    /** @var MySQL */
    protected $db;
    /**  @var MemCache */
    protected $cache;

    /**
     * PgTest constructor.
     *
     * @param null $name
     * @param array $data
     * @param string $dataName
     * @throws DBDException
     * @note docker run --name mysql --network="host" -e MYSQL_ROOT_PASSWORD=mysql -d mysql:5.7.37-debian
     */
    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $host = getenv('MYSQLHOST') ?: 'debian.wsl';
        $port = intval(getenv('MYSQLPORT')) ?: 3306;
        $database = getenv('MYSQLDATABASE') ?: 'mysql';
        $user = getenv('MYSQLUSER') ?: 'root';
        $password = getenv('MYSQLPASSWORD') ?: 'mysql';

        $this->config = new Config($host, $port, $database, $user, $password);
        $this->config->setCacheDriver($this->cache);

        $this->options = new Options();
        $this->options->setUseDebug(true);
        $this->db = new MySQL($this->config, $this->options);
        $this->db->connect();
    }
}
