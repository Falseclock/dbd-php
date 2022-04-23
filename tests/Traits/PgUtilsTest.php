<?php
/**
 * @author    Nurlan Mukhanov <nurike@gmail.com>
 * @copyright 2009-2022 Nurlan Mukhanov
 * @license   https://en.wikipedia.org/wiki/MIT_License MIT License
 * @link      https://github.com/Falseclock/dbd-php
 * @noinspection SqlNoDataSourceInspection
 * @noinspection SqlResolve
 * @noinspection SqlWithoutWhere
 * @noinspection PhpUnused
 */

declare(strict_types=1);

namespace DBD\Tests\Traits;

use DBD\Common\DBDException;
use DBD\Entity\Common\EntityException;
use DBD\Helpers\PgUtils;

trait PgUtilsTest
{
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
