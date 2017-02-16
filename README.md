# PostgreSQL driver for PHP

Basic useful feature list:

* Protection from SQL injection
* DBD-PG perl-like syntax

## Database operation

* connect
* disconnect
* isConnected

## Main methods

* [do](#do)
* [prepare](#prepare)
* [execute](#execute)
* [fetch](#fetch)
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
## **`do`**
**do** — Returns number of affected records (tuples)

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

#### Example

```php
<?php
// Create DSN 
$dsn = Driver::create("database_name", "sql_user", "sql_pass", "hostname.com", 5432);

// make connection to the database
$db = $dsn->connect();

// Bad example how SQL can be injected as every string parameter must be escaped 
// manually or with $db->quote('must be null');
$param = "'must be null'";
$result = $db->do("UPDATE table SET column1 = NULL WHERE column2 = $param");

// more easiest, simple and safe for SQL injections way.
// Number of affected tuples will be stored in $result variable
$result = $db->do("UPDATE table SET column1 = ? WHERE column2 = ?", NULL, 'must be null');
?>
```

* * *
## **`prepare`**
**prepare** — creates a prepared statement for later execution with [execute](#execute)() method. This feature allows commands that will be used repeatedly to be parsed and planned just once, rather than each time they are executed.

#### Description

```php
resource prepare ( string $statement )
```

**prepare()** returns the new DB driver instance.

#### Parameters
<dl>
  <dt><b>statement</b></dt>
  <dd>The SQL statement to be executed. Can have placeholders. Must contain only a single statement (multiple statements separated by semi-colons are not allowed). If any parameters are used, they are referred to as ?, ?, etc.</dd>
</dl>

#### Example

```php
<?php
// Common usage for repeatedly SELECT queries
$sth = $db->prepare("UPDATE table SET column1 = ? WHERE column2 = ?");

$fruits = array('apple','banana','apricot');

foreach ($fruits as $fruit) {
	$sth->execute(NULL,$fruit);
}

// this code will execute three statements
// UPDATE table SET column1 = NULL WHERE column2 = 'apple';
// UPDATE table SET column1 = NULL WHERE column2 = 'banana';
// UPDATE table SET column1 = NULL WHERE column2 = 'apricot';
?>
```


* * *
## **`execute`**
**execute** — Sends a request to execute a prepared statement with given parameters, and waits for the result.

#### Description

```php
resource execute ( [ mixed $params ] )
```

**execute()** executes previously-prepared statement, instead of giving a query string. This feature allows commands that will be used repeatedly to be parsed and planned just once, rather than each time they are executed. The statement must have been prepared previously.

#### Parameters
<dl>
  <dt><b>params</b></dt>
  <dd>An array of parameter values to substitute for the ?, ?, etc. placeholders in the original prepared query string. The number of elements in the array must match the number of placeholders.</dd>
</dl>

#### Example

```php
<?php
// Create DSN 
$dsn = Driver::create("database_name", "sql_user", "sql_pass", "hostname.com", 5432);

// make connection to the database
$db = $dsn->connect();

// Common usage for repeatedly UPDATE queries
$sth = $db->prepare("SELECT col1, col2, col3 FROM table1");
$std = $db->prepare("UPDATE table2 SET col2 =? WHERE col1=? AND col2=?");

$sth->execute();

while ($row = $sth->fetchrow()) {
	if ($row['col1'] == 'banana') {
    	$std->execute(FALSE,NULL,$row[col2]);
    }
}
?>
```
