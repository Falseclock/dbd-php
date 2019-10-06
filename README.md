##
## Database driver for PHP (beta version)
*(not native English speaker, any help appreciated)*

#### Why not standard PDO?
Actually, development of this library was started before PHP 5.0. Being mostly Perl developer and
inspired by DBI::DBD library I tried to develop the same functionality for PHP. 

#### Basic feature list:

* Much comfortable and easy than PDO
* SQL injections protection
* DBD/DBI perl-like library
* Easy caching integration
* Better error handling
* Measurements and debugging
* Extendable by other drivers (only PostgreSQL fully ready)
* Automatic conversion of records to objects with dbd-php-entity library

## Getting instance

Very and very easy:

```php
<?php
use DBD\Base\Config;
use DBD\Pg;

$config = new Config("127.0.0.1", 5432, "db_name", "user_name", "user_password");

$dbh =  new Pg($config);
$dbh->connect();

/*
... do some stuff
*/

$dbh->disconnect();
?>
``` 

## Basic methods

* [connect](#connect)
* [do](#do)
* [query](#query)
* [prepare](#prepare)
* [execute](#execute)
* [fetch](#fetch)
* [fetchRow](#fetchrow)
* [fetchRowSet](#fetchrowset)
* [fetchArraySet](#fetcharrayset)
* [insert](#insert)
* [update](#update)
* [begin](#begin)
* [commit](#commit)
* [rollback](#rollback)
* [cache](#cache)
* [isAffected](#isaffected)
* [escape](#escape)
* [affectedRows](#affectedrows)
* [entityInsert](#entityinsert)
* [entitySelect](#entityselect)
* [entityUpdate](#entityupdate)
* [entityDelete](#entitydelete)
* [disconnect](#disconnect)

* * *
# **connect**

**connect** — initiate connection to a database

### Description

```php
resource connect ()
```

**connect()** opens a connection to a database using **Option** instance provided in construction.

* * *
# **do**

**do** — Returns number of affected records (tuples)

### Description

```php
int do ( string $statement [, mixed $params ] )
```

**do()** returns the number of tuples (instances/records/rows) affected by INSERT, UPDATE, and DELETE queries.

Since PostgreSQL 9.0 and above, the server returns the number of SELECTed rows. Older PostgreSQL return 0 for SELECT.


### Parameters

***statement***
>The SQL statement to be executed. Can have placeholders. Must contain only a single statement (multiple statements separated by semi-colons are not allowed). If any parameters are used, they are referred to as ?, ?, etc.

***params***
>An array of parameter values to substitute for the ?, ?, etc. placeholders in the original prepared SQL statement string. The number of elements in the array must match the number of placeholders.


### Example

```php
<?php
use DBD\Base\Config;
use DBD\Pg;

$config = new Config("127.0.0.1", 5432, "db_name", "user_name", "user_password");

$db =  new Pg($config);
$db->connect();

// The following example is insecure against SQL injections
$param = "'must be null'";
$result = $db->do("UPDATE table SET column1 = NULL WHERE column2 = $param");

// more easiest, simple and safe for SQL injections example.
// Number of affected tuples will be stored in $result variable
$result = $db->do("UPDATE table SET column1 = ? WHERE column2 = ?", NULL, 'must be null');
?>
```
* * *
# **query**

**query** — quick statement execution

### Description

```php
resource query ( string $statement [, mixed $params ] )
```

**query()** do the same as [do](#do)() method, but returns self instance.


### Parameters

***statement***
>The SQL statement to be executed. Can have placeholders. Must contain only a single statement (multiple statements separated by semi-colons are not allowed). If any parameters are used, they are referred to as ?, ?, etc.

***params***
>An array of parameter values to substitute for the ?, ?, etc. placeholders in the original prepared SQL statement string. The number of elements in the array must match the number of placeholders.


### Example

```php
<?php
use DBD\Pg;
/** @var Pg $db */
$sth = $db->query("SELECT * FROM invoices");

while ($row = $sth->fetchRow()) {
	echo($row['invoice_id']);
}

$sth = $db->query("UPDATE invoices SET invoice_uuid = ?",'550e8400-e29b-41d4-a716-446655440000');

echo($sth->affectedRows());

?>
```
* * *
# **prepare**

**prepare** — creates a prepared statement for later execution with [execute](#execute)() method. 
This feature allows commands that will be used repeatedly to be parsed and planned just once, rather than each time they are executed.

### Description

```php
resource prepare ( string $statement )
```

**prepare()** returns the new DB driver instance.

### Parameters

***statement***
> The SQL statement to be executed. Can have placeholders. Must contain only a single statement (multiple statements separated by semi-colons are not allowed). If any parameters are used, they are referred to as ?, ?, etc.

### Example

```php
<?php
use DBD\Pg;
/** @var Pg $db */
// Common usage for repeatedly SELECT queries
$sth = $db->prepare("UPDATE table SET column1 = ? WHERE column2 = ?");

$fruits = array('apple','banana','apricot');

foreach ($fruits as $fruit) {
	$sth->execute(NULL,$fruit);
}

/*
this code will execute three statements
UPDATE table SET column1 = NULL WHERE column2 = 'apple';
UPDATE table SET column1 = NULL WHERE column2 = 'banana';
UPDATE table SET column1 = NULL WHERE column2 = 'apricot';
*/
?>
```


* * *
# **execute**

**execute** — Sends a request to execute a prepared statement with given parameters, and waits for the result.

### Description

```php
resource execute ( [ mixed $params ] )
```

**execute()** executes previously-prepared statement, instead of giving a query string. This feature allows commands that will be used repeatedly to be parsed and planned just once, rather than each time they are executed. The statement must have been prepared previously.

### Parameters

***params***
  >An array of parameter values to substitute for the ?, ?, etc. placeholders in the original prepared query string. The number of elements in the array must match the number of placeholders.

### Example

```php
<?php
use DBD\Pg;
/** @var Pg $db */

// Common usage for repeatedly UPDATE queries
$sth = $db->prepare("SELECT col1, col2, col3 FROM table1");
$std = $db->prepare("UPDATE table2 SET col2 =? WHERE col1=? AND col2=?");

$sth->execute();

while ($row = $sth->fetchRow()) {
	if ($row['col1'] == 'banana') {
    	$std->execute(FALSE, NULL, $row['col2']);
    }
}
/*
this code will execute this statement
UPDATE table2 SET col2 = FALSE WHERE col1 = NULL AND col2 = <value of col2 from table1>;
*/
?>
```


* * *
# **fetch**

**fetch** — Fetch a column from first row.

### Description

```php
mixed fetch ()
```

**fetch()** fetch a column from first row without fetching whole row and not reducing result. Calling fetchrow() or fetchrowset() will still return whole result set. Subsequent fetch() invoking will return next column in a row. Useful when you need to get value of column when it is a same in all rows.

### Example

```php
<?php
use DBD\Pg;
/** @var Pg $db */
$sth = $db->prepare("SELECT 'VIR-TEX LLC' AS company, generate_series AS wrh_id, 'Warehouse #'||trunc(random()*1000) AS wrh_name, trunc((random()*1000)::numeric, 2) AS wrh_volume FROM generate_series(1,3)");

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

echo ("Company name: $company_name\n");

while ($row = $sth->fetchRow()) {
	echo("	{$row['wrh_name']} volume {$row['wrh_volume']}\n");
}

/* cycle above will produce following printout
Company name: VIR-TEX LLP
	Warehouse #845 volume: 489.20
	Warehouse #790 volume: 241.80
	Warehouse #509 volume: 745.29
*/
?>
```

* * *
# **fetchrow**

**fetchRow** — fetch a row as an associative array

### Description

```php
array fetchRow ()
```

**fetchRow()** returns an associative array that corresponds to the fetched row (records).

### Return Values

An array indexed associatively (by field name). Each value in the array is represented as a string. Database NULL values are returned as NULL.

FALSE is returned if row exceeds the number of rows in the set, there are no more rows, or on any other error.

### Example

```php
<?php
use DBD\Pg;
/** @var Pg $db */
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
# **fetchrowset**

**fetchRowSet** — fetch a full result as multidimensional array, where each element is an associative array that corresponds to the fetched row.

### Description

```php
array fetchRowSet ([ string $key ])
```

### Parameters

***key***
  >A column name to use as an index. If two or more columns will have the same value in a column, only last row will be stored in array.

### Return Values

An associative array (in case if key provided) or indexed array if no key was provided. Each value in the array represented as an associative array (by field name). 
Values in a row Database NULL values are returned as NULL.


### Example

```php
<?php
use DBD\Pg;
/** @var Pg $db */
$sth = $db->prepare("SELECT generate_series AS wrh_id, 'Warehouse #'||trunc(random()*1000) AS wrh_name, trunc((random()*1000)::numeric, 2) AS wrh_volume FROM generate_series(1,3)");
$sth->execute();
print_r($sth->fetchRowSet());

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
print_r($sth->fetchRowSet('wrh_name'));

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
* * *
# **insert**

**insert** — makes new row insertion into the table. Returns self instance.

### Description

```php
mixed insert (string $table, array $values [, string $return])
```

### Parameters

***table***
>Database table name

***values***
>An associative array where key is field name and value is a field value.

***return***
>You can define which fields of the table you want return after successful insert


### Example 1

```php
<?php
use DBD\Pg;
/** @var Pg $db */
/** @var array $doc */
$record = [
	'invoice_uuid' => $doc['Ref'],
	'invoice_date' => $doc['Date'],
	'invoice_number' => $doc['Number'],
	'invoice_amount' => $doc['Amount'],
	'waybill_uuid' => $doc['reference']['uuid']
];
$sth = $db->insert('vatInvoices',$record);
echo ($sth->affectedRows());

?>
```
### Example 2

```php
<?php
use DBD\Pg;
/** @var Pg $db */
/** @var array $payment */
$record = [
	//'payment_id' => IS SERIAL, will be generated automatically
	'payment_uuid' => $payment['Ref'],
	'payment_date' => $payment['Date'],
	'payment_number' => $payment['Number'],
	'payment_amount' => $payment['Amount']
];

$sth = $db->insert('payments', $record, 'payment_id, payment_uuid');

while ($row = $sth->fetchrow()) {
	printf("We inserted new payment with ID=%d and UUID=%s\n",$row['payment_id'],$row['payment_uuid']);
}
?>
```

* * *
# **update**

**update** — makes updates of the rows by giving parameters and prepared values. Returns self instance.

### Description

```php
mixed update (string $table, array $values [, mixed $where..., [ mixed $args...], [string $return]])
```

### Parameters

***table***
>Database table name

***values***
>An associative array where key is field name and value is a field value.

***where***
>Specifies update condition. Can have placeholders.

***args***
>Binds value for **where** condition. Strict if placeholders are exist in **where** parameter. Can be omitted if there are no any placeholders in **where** parameter.

***return***
>You can define which fields of the table you want return after succesfull insert

### Example 1

```php
<?php
use DBD\Pg;
/** @var Pg $db */
/** @var array $doc */
$update = [
	'invoice_date' => $doc['Date'],
	'invoice_number' => $doc['Number'],
	'invoice_amount' => $doc['Amount']
];
/* this will update all rows in a table */
$sth = $db->update('invoices',$update);
echo ($sth->affectedRows());
?>
```
### Example 2

```php
<?php
use DBD\Pg;
/** @var Pg $db */
/** @var array $doc */
$update = [
	'invoice_date' => $doc['Date'],
	'invoice_number' => $doc['Number'],
	'invoice_amount' => $doc['Amount']
];
/* this will update all rows in a table where vat_invoice_uuid equals to some value */
$sth = $db->update('vat_invoices', $update, "vat_invoice_uuid=?", $doc['UUID']);
echo ($sth->affectedRows());
?>
```
### Example 3

```php
<?php
use DBD\Pg;
/** @var Pg $db */
/** @var array $doc */
$update = array(
	'vatinvoice_date' => $doc['Date'],
	'vatinvoice_number' => $doc['Number'],
	'vatinvoice_amount' => $doc['Amount']
);
/* 
this will update all rows in a table where vatinvoice_uuid is null
query will return vatinvoice_id
*/
$sth = $db->update('vatinvoices', $update, "vatinvoice_uuid IS NULL", "vatinvoice_id");
while ($row = $sth->fetchRow()) {
	printf("Updated vatinvoice with ID=%d\n", $row['vatinvoice_id']);
}
?>
```
### Example 4

```php
<?php
use DBD\Pg;
/** @var Pg $db */
/** @var array $doc */
$update = array(
	'vatinvoice_date' => $doc['Date'],
	'vatinvoice_number' => $doc['Number'],
	'vatinvoice_amount' => $doc['Amount']
);
// this will update all rows in a table where vatinvoice_uuid equals to some value
// query will return vatinvoice_id
$sth = $db->update('vatinvoices',$update,"vatinvoice_uuid =? ", $doc['UUID'], "vatinvoice_id, vatinvoice_uuid");
while ($row = $sth->fetchRow()) {
	printf("Updated vatinvoice with ID=%d and UUID=%s\n",$row['vatinvoice_id'],$row['vatinvoice_uuid']);
}
?>
```
* * *
# **begin**

**begin** — Starts database transaction

### Description

```php
mixed begin ()
```

**begin()** enable transactions (by turning AutoCommit off) until the next call to [commit](#commit) or [rollback](#rollback). After the next [commit](#commit) or [rollback](#rollback), AutoCommit will automatically be turned on again.

### Example

```php
<?php
use DBD\Pg;
/** @var Pg $db */

$db->begin();

// Common usage for repeatedly UPDATE queries
$sth = $db->prepare("SELECT col1, col2, col3 FROM table1");
$std = $db->prepare("UPDATE table2 SET col2 =? WHERE col1=? AND col2=?");

$sth->execute();

while ($row = $sth->fetchrow()) {
	if ($row['col1'] == 'banana') {
    	$std->execute(FALSE,NULL,$row['col2']);
    }
}
$db->commit();
?>
```
* * *
# **commit**

**commit** — Commit database transaction

### Description

```php
mixed commit ()
```

**commit()** makes permanent the most recent series of database changes if the database supports transactions and AutoCommit is off.

* * *
# **rollback**

**rollback** — undo changes

### Description

```php
mixed rollback ()
```

**rollback()** undo the most recent series of uncommitted database changes if the database supports transactions and AutoCommit is off.


* * *
# **cache**

**cache** — cache select result 

### Description

```php
mixed cache ()
```

**cache()** bla bla la


