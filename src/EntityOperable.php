<?php
/**
 * @author    Nurlan Mukhanov <nurike@gmail.com>
 * @copyright 2009-2022 Nurlan Mukhanov
 * @license   https://en.wikipedia.org/wiki/MIT_License MIT License
 * @link      https://github.com/Falseclock/dbd-php
 */

declare(strict_types=1);

namespace DBD;

use DBD\Entity\Entity;

interface EntityOperable
{
    /**
     * @param Entity $entity
     * @param bool $exceptionIfNoRecord
     * @return Entity|null
     */
    public function entitySelect(Entity &$entity, bool $exceptionIfNoRecord = true): ?Entity;

    /**
     * @param Entity $entity
     * @return bool
     */
    public function entityDelete(Entity $entity): bool;

    /**
     * @param Entity $entity
     * @return Entity
     */
    public function entityInsert(Entity &$entity): Entity;

    /**
     * @param Entity $entity
     * @return Entity
     */
    public function entityUpdate(Entity &$entity): Entity;
}

