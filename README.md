# SQL Database driver for PHP

Basic useful feature list:

* Protection from SQL injections
* DBD/DBI perl-like syntax

## Database operations

* [connect](#connect)
* [disconnect](#disconnect)
* [isConnected](#isConnected)

## Main methods

* [do](#do)
* [prepare](#prepare)
* [execute](#execute)
* [fetch](#fetch)
* [fetchrow](#fetchrow)
* [fetchrowset](#fetchrowset)
* [insert](#insert)
* [select](#select)
* [delete](#delete)
* [begin](#begin)
* [commit](#commit)
* [rollback](#rollback)
* [cache](#cache)
* [rows](#rows)
* [getColumn](#getColumn)
* [result](#result)

* * *
## **`du`**

**du** — Returns number of affected records (tuples)

### Description

```php
int du ( string $statement [, mixed $params ] )
```

**du()** returns the number of tuples (instances/records/rows) affected by INSERT, UPDATE, and DELETE queries.

Since PostgreSQL 9.0 and above, the server returns the number of SELECTed rows. Older PostgreSQL return 0 for SELECT.


### Parameters

**statement**
>The SQL statement to be executed. Can have placeholders. Must contain only a single statement (multiple statements separated by semi-colons are not allowed). If any parameters are used, they are referred to as ?, ?, etc.

**params**
>An array of parameter values to substitute for the ?, ?, etc. placeholders in the original prepared SQL statement string. The number of elements in the array must match the number of placeholders.


### Example

```php
<?php

$db_options = array(
    'ConvertNumeric'    => true,
    'UseDebug'          => true
);
// Create DSN 
$dsn = (new DBD\Pg())->create("host=localhost;port=5432;dbname=falseclock", "username","password", $db_options);

// make connection to the database
$db = $dsn->connect();

// Bad example how SQL can be injected as every string parameter must be escaped 
// manually or with $db->quote('must be null');
$param = "'must be null'";
$result = $db->du("UPDATE table SET column1 = NULL WHERE column2 = $param");

// more easiest, simple and safe for SQL injections way.
// Number of affected tuples will be stored in $result variable
$result = $db->du("UPDATE table SET column1 = ? WHERE column2 = ?", NULL, 'must be null');
?>
```

* * *
## **`prepare`**

**prepare** — creates a prepared statement for later execution with [execute](#execute)() method. This feature allows commands that will be used repeatedly to be parsed and planned just once, rather than each time they are executed.

### Description

```php
resource prepare ( string $statement )
```

**prepare()** returns the new DB driver instance.

### Parameters

**statement**
> The SQL statement to be executed. Can have placeholders. Must contain only a single statement (multiple statements separated by semi-colons are not allowed). If any parameters are used, they are referred to as ?, ?, etc.

### Example

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

### Description

```php
resource execute ( [ mixed $params ] )
```

**execute()** executes previously-prepared statement, instead of giving a query string. This feature allows commands that will be used repeatedly to be parsed and planned just once, rather than each time they are executed. The statement must have been prepared previously.

### Parameters

**params**
  >An array of parameter values to substitute for the ?, ?, etc. placeholders in the original prepared query string. The number of elements in the array must match the number of placeholders.

### Example

```php
<?php
$db_options = array(
    'ConvertNumeric'    => true,
    'UseDebug'          => true
);
// Create DSN 
$dsn = (new DBD\Pg())->create("host=localhost;port=5432;dbname=falseclock", "username","password", $db_options);

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


* * *
## **`fetch`**

**fetch** — Fetch a column from first row.

### Description

```php
mixed fetch ()
```

**fetch()** fetch a column from first row without fetching whole row and not reducing result. Calling fetchrow() or fetchrowset() will still return whole result set. Subsequent fetch() invoking will return next column in a row. Useful when you need to get value of column when it is a same in all rows.

### Example

```php
<?php
$sth = $db->prepare("SELECT 'VIR-TEX LLP' AS company, generate_series AS wrh_id, 'Warehouse #'||trunc(random()*1000) AS wrh_name, trunc((random()*1000)::numeric, 2) AS wrh_volume FROM generate_series(1,3)");

/* select result example
   company   | wrh_id |    wrh_name    | wrh_volume
-------------+--------+----------------+------------
 VIR-TEX LLP |      1 | Warehouse #845 |     489.20
 VIR-TEX LLP |      2 | Warehouse #790 |     241.80
 VIR-TEX LLP |      3 | Warehouse #509 |     745.29
*/

$sth->execute();

$company_name = $sth->fetch(); // getting first column
$wrh_id = $sth->fetch(); // getting second column as an example of subsequent invoking
$wrh_name = $sth->fetch(); // getting third column

echo ("Company name: $company\n");

while ($row = $sth->fetchrow()) {
	echo("	{$row['wrh_name']} volume {$row['wrh_volume']}\n");
}

/* cycle abobe will produce following printout
Company name: VIR-TEX LLP
	Warehouse #845 volume: 489.20
	Warehouse #790 volume: 241.80
	Warehouse #509 volume: 745.29
*/
?>
```

* * *
## **`fetchrow`**

**fetchrow** — fetch a row as an associative array

### Description

```php
array fetchrow ()
```

**fetchrow()** returns an associative array that corresponds to the fetched row (records).

### Return Values

An array indexed associatively (by field name). Each value in the array is represented as a string. Database NULL values are returned as NULL.

FALSE is returned if row exceeds the number of rows in the set, there are no more rows, or on any other error.

### Example

```php
<?php
$sth = $db->prepare("SELECT *, 'orange' AS col1, 'apple' AS col2, 'tomato'  AS col3 FROM generate_series(1,3)");
$sth->execute();
print_r($sth->fetchrow());

/* code above will produce following printout
Array
(
    [generate_series] => 1
    [col1] => orange
    [col2] => apple
    [col3] => tomato
)
*/
?>
```

* * *
## **`fetchrowset`**

**fetchrowset** — fetch a full result as multidimensional array, where each element is an associative array that corresponds to the fetched row.

### Description

```php
array fetchrowset ([ string $key ])
```

### Parameters

**key**
  >A column name to use as an index. If two or more columns will have the same value in a column, only last row will be stored in array.

### Return Values

An associative array (in case if key provided) or indexed array if no key was provided. Each value in the array represented as an associative array (by field name). 
Values in a row Database NULL values are returned as NULL.


### Example

```php
<?php
$sth = $db->prepare("SELECT generate_series AS wrh_id, 'Warehouse #'||trunc(random()*1000) AS wrh_name, trunc((random()*1000)::numeric, 2) AS wrh_volume FROM generate_series(1,3)");
$sth->execute();
print_r($sth->fetchrowset());

/* code above will produce following printout
Array
(
    [0] => Array
        (
            [wrh_id] => 1
            [wrh_name] => Warehouse #795
            [wrh_volume] => 809.73
        )

    [1] => Array
        (
            [wrh_id] => 2
            [wrh_name] => Warehouse #639
            [wrh_volume] => 894.50
        )

    [2] => Array
        (
            [wrh_id] => 3
            [wrh_name] => Warehouse #334
            [wrh_volume] => 13.77
        )

)
*/

$sth->execute();
print_r($sth->fetchrowset('wrh_name'));

/*
Array
(
    [Warehouse #214] => Array
        (
            [wrh_id] => 1
            [wrh_name] => Warehouse #214
            [wrh_volume] => 462.10
        )

    [Warehouse #563] => Array
        (
            [wrh_id] => 2
            [wrh_name] => Warehouse #563
            [wrh_volume] => 8.88
        )

    [Warehouse #634] => Array
        (
            [wrh_id] => 3
            [wrh_name] => Warehouse #634
            [wrh_volume] => 338.18
        )

)
*/
?>
```
