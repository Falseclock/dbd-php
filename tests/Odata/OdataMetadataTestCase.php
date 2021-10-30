<?php
/**
 * OdataMetadataTest
 *
 * @author    Nurlan Mukhanov <nurike@gmail.com>
 * @copyright 2021 Nurlan Mukhanov
 * @license   https://en.wikipedia.org/wiki/MIT_License MIT License
 * @link      https://github.com/Falseclock/dbd-php
 */

declare(strict_types=1);

namespace DBD\Tests\Odata;

use DBD\Utils\OData\Metadata;
use Exception;

class OdataMetadataTestCase extends OdataTestCase
{
    /**
     * @throws Exception
     */
    public function testMetadataV4()
    {
        $fileContents = file_get_contents("./tests/fixtures/metadata-v4.xml");

        $xml = simplexml_load_string($fileContents);
        $body = $xml->xpath("//edmx:Edmx/edmx:DataServices/*");
        $schema = json_decode(json_encode($body[0]));

        $schema = new Metadata($schema);

        self::assertNotNull($schema);
    }
}
