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
use DBD\Entity\Common\EntityException;
use DBD\Helpers\PgUtils;
use DBD\Pg;
use DBD\Tests\Traits\BindTestPg;
use DBD\Tests\Traits\ConvertTypesTestPg;
use DBD\Tests\Traits\EntityTest;

class PgTest extends DBDTest
{
    use EntityTest;
    use BindTestPg;
    use ConvertTypesTestPg;

    const QUERY_TIMEZONE = "SELECT current_setting('TIMEZONE')";

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

    /**
     * @throws EntityException
     * @throws DBDException
     */
    public function testUtilsCommon()
    {
        $this->db->do("DROP TABLE IF EXISTS public.city");
        $this->db->do("DROP TABLE IF EXISTS public.country");

        $this->db->do("
            CREATE TABLE public.country
            (
              country_id integer NOT NULL,
              country_name character varying(128),
              CONSTRAINT country_pkey PRIMARY KEY (country_id)
            )
        ");
        $this->db->do("
            CREATE TABLE public.city
            (
              city_id integer NOT NULL,
              country_id integer,
              city_name character varying(128),
              city_date timestamp with time zone DEFAULT NOW(),
              city_parent_id integer,
              CONSTRAINT city_pkey PRIMARY KEY (city_id),
              CONSTRAINT city_ref_country FOREIGN KEY (country_id)
                  REFERENCES public.country (country_id) MATCH SIMPLE
                  ON UPDATE CASCADE ON DELETE CASCADE,
              CONSTRAINT city_ref_city FOREIGN KEY (city_parent_id)
                  REFERENCES public.city (city_id) MATCH SIMPLE
                  ON UPDATE CASCADE ON DELETE CASCADE
            )
        ");

        $this->db->do("COMMENT ON COLUMN public.city.city_date IS 'Comment'");

        $utils = new PgUtils($this->db);

        $table = $utils->tableStructure("city", "public");
        self::assertNotNull($table);
        self::assertCount(2, $utils->getTableConstraints($table));

        $this->db->do("DROP TABLE city");
        $this->db->do("DROP TABLE country");
    }
}
