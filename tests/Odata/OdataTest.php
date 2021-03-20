<?php
/**
 * OdataTest
 *
 * @author    Nurlan Mukhanov <nurike@gmail.com>
 * @copyright 2020 Nurlan Mukhanov
 * @license   https://en.wikipedia.org/wiki/MIT_License MIT License
 * @link      https://github.com/Falseclock/dbd-php
 */

declare(strict_types=1);

namespace DBD\Tests\OData;

use DBD\Base\Config;
use DBD\Base\Options;
use DBD\Cache\MemCache;
use DBD\Common\DBDException;
use DBD\OData;
use PHPUnit\Framework\TestCase;

abstract class OdataTest extends TestCase
{
    /** @var Options */
    protected $options;
    /** @var Config */
    protected $config;
    /** @var OData */
    protected $db;
    /**  @var MemCache */
    protected $memcache;

    /**
     * @param int $length
     * @return false|string
     */
    protected function randomCacheString($length = 10): string {
        return substr(str_shuffle(str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', intval(ceil($length/strlen($x)) ) )),1,$length);
    }

    /**
     * OdataTest constructor.
     * @param string|null $name
     * @param array $data
     * @param string $dataName
     */
    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $host = getenv('ODHOST') ?: 'https://url/odata/standard.odata/';
        $user = getenv('ODUSER') ?: 'User';
        $password = getenv('ODPASSWORD') ?: 'password';

        $this->memcache = new MemCache([[MemCache::HOST => '127.0.0.1', MemCache::PORT => 11211]]);
        $this->memcache->connect();

        $this->config = new Config($host, null, null, $user, $password);
        $this->config->setCacheDriver($this->memcache);

        $this->options = new Options();
        $this->db = new OData($this->config, $this->options);
    }

    /**
     * @throws DBDException
     */
    public function notestJoin()
    {
        $sth = $this->db->prepare("
            SELECT
                Ref_Key,
                Number,
                ДатаВходящегоДокумента,
                Комментарий,
                СуммаДокумента,
                НазначениеПлатежа,
                ВалютаДокумента/БуквенныйКод,
                Контрагент/ИдентификационныйКодЛичности,
                Контрагент/Description,
                Контрагент/НаименованиеПолное,
                Контрагент/ЮрФизЛицо,
                Контрагент/Ref_Key
            FROM 
                Document_ПлатежноеПоручениеВходящее
            JOIN Контрагент ON TRUE
            JOIN СчетКонтрагента ON TRUE
            JOIN ВалютаДокумента ON TRUE
            WHERE
                DeletionMark = false AND aaa > 0 and BBB < 0
        ");
        $sth->execute();

    }
}