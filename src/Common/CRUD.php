<?php
/**
 * CRUD
 *
 * @author       Nurlan Mukhanov <nurike@gmail.com>
 * @copyright    2020 Nurlan Mukhanov
 * @license      https://en.wikipedia.org/wiki/MIT_License MIT License
 * @link         https://github.com/Falseclock/dbd-php
 * @noinspection PhpComposerExtensionStubsInspection
 */

declare(strict_types=1);

namespace DBD\Common;

interface CRUD
{
    const CREATE = "INSERT";
    const READ = "SELECT";
    const UPDATE = "UPDATE";
    const DELETE = "DELETE";

    const ERROR_CACHING_NON_SELECT_QUERY = "Caching setup failed, current query is not of SELECT type";
    const ERROR_STATEMENT_NOT_PREPARED = "SQL statement not prepared";
    const ERROR_UNCOMMITTED_TRANSACTION = "Uncommitted transaction state";
    const ERROR_NO_STATEMENT = "Query failed: statement is not set or empty";
    const ERROR_NOT_PREPARED = "No query prepared for execution";
    const ERROR_OBJECT_ESCAPE = "Object can't be escaped";
    const ERROR_ARRAY_ESCAPE = "Array can't be escaped";
    const ERROR_NOTHING_TO_PREPARE = "Prepare failed: statement is not set or empty";
    const ERROR_BINDS_MISMATCH = "Execute failed, called with %s bind variables when %s are needed";
    const ERROR_KEY_NOT_UNIQUE = "Key '%s' not unique";
    const ERROR_ENTITY_NO_PK = "Entity %s does not have any defined primary key";
    const ERROR_PK_IS_NULL = "Value of %s->%s, which is primary key column, is null";
    const ERROR_ENTITY_PROPERTY_NOT_NULL = "Property '%s' of %s can't be null according to Mapper annotation";
    const ERROR_ENTITY_PROPERTY_NON_SET = "Property '%s' of %s not set";
    const ERROR_ENTITY_TOO_MANY_UPDATES = "More then one records updated with query. Transaction rolled back!";
    const ERROR_ENTITY_NO_UPDATES = "No any records updated.";
    const ERROR_ARGUMENT_NOT_SCALAR = "Execute arguments for WHERE condition is not scalar";
    const ERROR_ENTITY_NOT_FOUND = "No data found for entity '%s' with such query";
    const ERROR_UNKNOWN_INSERT_FORMAT = "Unknown format of record for insert";
    const ERROR_UNKNOWN_UPDATE_FORMAT = "Unknown format of record for update";
    const ERROR_NON_SQL_QUERY= "non SQL query: %s";
    const ERROR_UNIDENTIFIABLE_QUERY = "Can't identity query type";
}
