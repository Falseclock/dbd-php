<?php
/**
 * Metadata
 *
 * @author       Nurlan Mukhanov <nurike@gmail.com>
 * @copyright    2020 Nurlan Mukhanov
 * @license      https://en.wikipedia.org/wiki/MIT_License MIT License
 * @link         https://github.com/Falseclock/dbd-php
 */
declare(strict_types=1);

namespace DBD\Utils\OData;

use stdClass;

class Metadata
{
    /** @var EntityType[] */
    public $EntityType = [];
    /** @var EntityContainer */
    public $EntityContainer;
    /** @var string */
    public $nameSpace;

    /**
     * Metadata constructor.
     * @param stdClass $schema
     */
    public function __construct(stdClass $schema)
    {
        $this->nameSpace = $schema->{'@attributes'}->Namespace;

        if (isset($schema->{'EntityType'}))
            foreach ($schema->{'EntityType'} as $entityType)
                $this->EntityType[] = new EntityType($entityType);

        if (isset($schema->{'EntityContainer'}))
            $this->EntityContainer = new EntityContainer($schema->{'EntityContainer'});

    }
}
