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

namespace DBD\Tests;

use DBD\Base\Config;
use DBD\Base\Options;
use DBD\Common\DBDException;
use DBD\Pg;
use DBD\Utils\OData\Metadata;
use DBD\YellowERP;
use DOMDocument;
use Exception;
use LSS\XML2Array;
use PHPUnit\Framework\TestCase;
use SimpleXMLElement;
use XMLReader;

class OdataTest extends TestCase
{
    /** @var Options */
    private $options;
    /** @var Config */
    private $config;
    /** @var Pg */
    private $db;

    /**
     * OdataTest constructor.
     * @param string|null $name
     * @param array $data
     * @param string $dataName
     * @throws DBDException
     */
    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->config = new Config("", 111, "database", "user", "password");

        $this->options = new Options();
        $this->db = new YellowERP($this->config, $this->options);
        $this->db->connect();
    }

    /**
     * @throws Exception
     */
    public function testMetadataV3()
    {
        $fileContents= file_get_contents("./tests/fixtures/metadata-v4.xml");

        $xml = simplexml_load_string($fileContents);
        $body = $xml->xpath("//edmx:Edmx/edmx:DataServices/*");
        $schema = json_decode(json_encode($body[0]));

        $schema = new Metadata($schema);

        return;
    }


    /**
     * @throws DBDException
     */
    public function testJoin()
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