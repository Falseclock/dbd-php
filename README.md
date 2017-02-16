# PostgreSQL driver for PHP

Basic useful feature list:

* Protection from SQL injection

## Database operation

* connect
* disconnect
* isConnected

## Main methods

* do
* prepare
* execute
* fetch
* fetchrow
* fetchrowset
* insert
* select
* delete
* begin
* commit
* rollback
* cache
* rows
* getColumn
* result

* * *
### **`do`**
do — Returns number of affected records (tuples)

#### Description

```php
int do ( string $statement [, mixed $params ] )
```

**do()** returns the number of tuples (instances/records/rows) affected by INSERT, UPDATE, and DELETE queries.

Since PostgreSQL 9.0 and above, the server returns the number of SELECTed rows. Older PostgreSQL return 0 for SELECT.

#### Parameters
<dl>
  <dt><b>statement</b></dt>
  <dd>The SQL statement to be executed. Can have placeholders. Must contain only a single statement (multiple statements separated by semi-colons are not allowed). If any parameters are used, they are referred to as ?, ?, etc.</dd>

<dt><b>params</b></dt>
  <dd>An array of parameter values to substitute for the ?, ?, etc. placeholders in the original prepared SQL statement string. The number of elements in the array must match the number of placeholders.</dd>
</dl>

<font color="red">This is some text!</font>
#### Examples

```php
<?php
// Create DSN 
$dsn = Driver::create("database_name", "sql_user", "sql_pass", "hostname.com", 5432);

// make connection to the database
$db = $dsn->connect();

// Execute SQL query and write number affected tuples into $result variable
// very dangerous and every string parameter must be escaped manually or with $db->quote('must be null');
$param = "'must be null'";
$result = $db->do("UPDATE table SET column1 = NULL WHERE column2 = $param");

// more easiest, simple and safe for SQL injections way
$result = $db->do("UPDATE table SET column1 = ? WHERE column2 = ?", NULL, 'must be null');
?>
```
