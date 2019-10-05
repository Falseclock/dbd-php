##
## PHP Драйвер базы данных для  (beta version)

#### Список основного функционала:

* Защита от SQL инъекций
* Стиль программирования как у DBI::DBD
* Быстрая и легкая интеграция с Memcache
* Удобный интерфейс отладки и мониторинга ошибок
* Расширяемый интерфейс для других баз дынных (полность работает Postgresql, MSSQL в стадии тестирования, mySQL - в стадии беты)

## Операции с базой

* [create](#create) - инициализация
* [connect](#connect) - соединение с базой
* [setOption](#setOption) - установка опций
* [getOption](#getOption) - чтение опций
* [disconnect](#disconnect) - разрыв соединения с базой
* [isConnected](#isConnected) - проверка на соединение
* [getDebug](#getDebug) - получение отладочной информации
* [printDebug](#printDebug) - вывод отладочной информации

## Основные методы

* [query](#query)
* [doit](#doit)
* [prepare](#prepare)
* [execute](#execute)
* [fetch](#fetch)
* [fetchrow](#fetchrow)
* [fetchrowset](#fetchrowset)
* [insert](#insert)
* [update](#update)
* [begin](#begin)
* [commit](#commit)
* [rollback](#rollback)
* [cache](#cache)
* [drop](#drop)
* [rows](#rows)
* [getColumn](#getColumn)
* [result](#result)
* [getStorage](#getStorage) - получение типа результата выборки
* [getResult](#getResult) - 

## Примеры
* [Соединение с MemCache сервером и установка параметров БД](#memcache_example)

* * *
# **create**

**create** — инициирует параметры для работы с базой данных

### Описание

```php
resource create(string $dsn, string $port, string $database, string $username, string $password[, array $options ])
```

### Параметры

***dsn***
>В случае работы с SQL базами данных *dsn* является IP адресом или FQDN. В случае работы с ODATA интерфейсом, данный параметр является URL по которому расположен WSDL.

***port***
>Порт на котором база данных ожидает подключения. В случае с ODATA данное поле необходимо оставить как *null*.

***database***
>Наименование базы данных. В случае с ODATA данное поле необходимо оставить как *null*.

***username***
>Имя пользователя под которым будет производиться подключение к базе дынных.

***password***
>Пароль пользователя для подключения к базе.

***options***
>Массив настроек. Имеются следующие виды настроек:
>
>| Параметр           | Станд. знач | Описание                                            |
>|--------------------|:-----------:|-----------------------------------------------------|
>| OnDemand           |    false    | Соединение с базой по требованию                    |
>| RaiseError         |     true    | Остановка кода в случае ошибки в работе с БД        |
>| PrintError         |     true    | Печать ошибки в дефолтный обработчик вывода         |
>| ShowErrorStatement |    false    | Отображение SQL запроса, вызвавшего ошибку          |
>| HTMLError          |    false    | Отображение ошибки в виде HTML, иначе в виде текста |
>| ConvertNumeric     |    false    | Конвертация цифровых значений из текста             |
>| UseDebug           |    false    | Сбор статистики запросов.                           |
>| ErrorHandler       |     null    | Возможность настройки своего обработчика ошибок     |
>| CacheDriver        |     null    | Ресурс кэширующего интерфейса                       |

****OnDemand**** 
>В случае, если вы активно используете кэширование и на некоторых страницах у вас не всегда необходимо создавать соединение к БД, вы можете поставить данное значение в TRUE. 
>Это может серьезно сократить скорость загрузки страницы, так как не будет происходить бесполезное и не используемое соедининие с БД.

****RaiseError****
>При TRUE значении, в случае возникновения ошибки, выполнение кода будет остановлено, а в браузер будет отправлен статус 500 Internal Server Error («внутренняя ошибка сервера»).
>Вы можете установить свой обработчик через параметр *ErrorHandler*.

****PrintError****
>Печать ошибки в дефолтный обработчик вывода. Не рекомендуется на производственном сервере, так как выводится отлажная информация со стэком и куском кода, где произошла ошибка. 
>В случае указания своего обработчика ошибок через параметр *ErrorHandler* данный параметр необходимо также проверять.

****ShowErrorStatement**** 
>Печать запроса, который вызвал ошибку. Не рекомендуется производственном сервере, так как может быть использовано в случае атаки путем SQL-инъекции.
>Не смотря на то, что данный драйвер защищает от подобного рода атака, в случае если вы корректно передаете параметры для формирования запросов, но не гарантирует, если вы совершите ошибки в программировании.

****ConvertNumeric****
>Некоторые базы данных, например PostgreSQL, возвращают в PHP числовые значения в виде текста. 
>Например, если вы формируете JSON строку из результатов выборки, то значения будут иметь типа string. 
>Если вам важно в последующей работе, например с Javascript, иметь правильный формат данных, то данный параметр необходимо установить в TRUE значение.

****UseDebug****
>Некоторые базы данных, например PostgreSQL, возвращают в PHP числовые значения в виде текста. 
>Например, если вы формируете JSON строку из результатов выборки, то значения будут иметь типа string. 
>Если вам важно в последующей работе, например с Javascript, иметь правильный формат данных, то данный параметр необходимо установить в TRUE значение.

****ErrorHandler****
>Если вам необходимо определить свой обработчик ошибок, то для этого необходимо указать в виде строки название класса, который расширяет стандартный класс ErrorHandler. Примеры имеются ниже.

****CacheDriver****
>В настоящее время разработан драйвер только для работы с MemCache. Перед тем как установить соединение с базой данных вам необходимо инициировать соединение и передать в качестве параметра его экземпляр.  Пример имеется ниже.

### Пример

```php
// Predefind options
$db_options = [
    'OnDemand'           => true,
    'RaiseError'         => true,
    'PrintError'         => true,
    'HTMLError'          => true,
    'ShowErrorStatement' => true,
    'ConvertNumeric'     => true,
    'UseDebug'           => true
];

// Create DSN
$dsn = (new DBD\Pg())->create("localhost", 5432, "my_database", "user", "secret_password", $db_options);
$db  = $dsn->connect();
```

* * *
# **connect**

**connect** — иницирует соединение с базой.

### Описание

```php
resource connect ()
```

**connect()** открывает соединение с базой и возвращает экземпляр класса.



* * *
# **query**

**query** — выполнение простого запросы

### Описание

```php
resource query ( string $statement [, mixed $params ] )
```

**query()** выполняет тоже самое что и метод [doit](#doit)() method, но возврящает свой собственный экземпляр. Как правило, удобно использовать если вым надо сделать выборку единственного значения или просто подсчитать количество строк.


### Параметры

***statement***
>Готовый SQL запрос. Может иметь псевдопеременные. Должен содержать только 1 запрос (множественные запросы, разделенные точкой с запятой не допустимы). Если передаются какие-то параметры, то они должны быть обозначены через неименованные псевдопеременные ?, ?, и т.д.

***params***
>Массив параметров, которые необходимо заполнить через псевдопеременные ?, ?, указанные в SQL запросе. Количество параметров должно совпадать с количеством псевдопеременных.


### Пример 1

```php
<?php

$dsn = (new Falseclock\DBD\Pg())->create("localhost", 5432, "my_database", "user", "secret_password", $db_options);
$db  = $dsn->connect();

$sth = $db->query("UPDATE invoices SET invoice_uuid = ? WHERE invoice_date = ?", '550e8400-e29b-41d4-a716-446655440000', '2018-01-01');

// Получить количество строк полученных или задействованных при последнем запросе
echo($sth->rows);
?>
```

### Пример 2

```php
<?php
$sth = $db->query("SELECT COUNT(*) FROM invoices WHERE invoice_uuid = ?", '550e8400-e29b-41d4-a716-446655440000');

// Выборка первой колонки (в данном примере единственной)
echo($sth->fetch());
?>
```

### Пример 3

```php
<?php
$sth = $db->query("SELECT * FROM invoices WHERE invoice_uuid = ?", '550e8400-e29b-41d4-a716-446655440000');

// Поочередная выборка из результата выборки
while ($row = $sth->fetchrow()) {
    print_r($row);
}
?>
```

>## **HTML Error Example**
>![Image of Yaktocat](https://cloud.githubusercontent.com/assets/3299139/24210184/77632df8-0f52-11e7-95fd-3514ed4ad60d.PNG)
>
>## **TEXT Error Example**
>![Image of Yaktocat](https://cloud.githubusercontent.com/assets/3299139/24210187/78dd0168-0f52-11e7-8294-ed0ed8b105af.PNG)
>
>## **ErrorHandler Example**
>Class **DBD\Base\ErrorHandler** has three public classes: **composeData()**, **composeTETXError**, **composeHTMLError**
>```php
>$db_options = array(
>	'ErrorHandler'		=> 'SQLErrorHandler'
>);
>
>// Create DSN 
>$dsn = (new DBD\Pg())->create("host=localhost;port=5432;dbname=falseclock", "username","password", $db_options);
>
>// make connection to the database
>$db = $dsn->connect();
>
>class SQLErrorHandler extends DBD\Base\ErrorHandler
>{
>	public function __construct($query, $errstr, $caller, $options=null) {
>		
>		$error = $this->composeData($query, $errstr, $caller);
>			
>		$filename = "sql.log";
>		$file_data = json_encode($error, JSON_UNESCAPED_UNICODE)."\n";
>			
>		if (! file_exists($filename)) { @file_put_contents($filename, ''); }
>		$file_data .= @file_get_contents($filename);
>		@file_put_contents($filename, $file_data);
>		@chmod($filename,0666);
>		
>		$html = $this->composeHTMLError($query, $errstr, $caller, $options);
>		
>		echo($html);
>		exit();
>	}
>}
>```
>
>## **CacheDriver Example**
>```php
>$cache = DBD\Cache\MemCache::me()->create(array(['host' => '127.0.0.1', 'port' => 11211]), false, 15)->open();
>
>$db_options = array(
>	'CacheDriver'    => $cache,
>	'ErrorHandler'   => 'SQLErrorHandler'
>);
>
>// Create DSN 
>$dsn = (new DBD\Pg())->create("host=localhost;port=5432;dbname=falseclock", "username","password", $db_options);
>
>// make connection to the database
>$db = $dsn->connect();
>
>$sth = $db->prepare("SELECT bank_id AS id, bank_name AS name FROM banks ORDER BY bank_name ASC");
>$sth->cache("AllBanks"); // <--- this will
>$sth->execute(); // Will try to fetch from catch if exist or will query database and store to cache
>
>```


* * *
# **du**

**du** — Returns number of affected records (tuples)

### Description

```php
int du ( string $statement [, mixed $params ] )
```

**du()** returns the number of tuples (instances/records/rows) affected by INSERT, UPDATE, and DELETE queries.

Since PostgreSQL 9.0 and above, the server returns the number of SELECTed rows. Older PostgreSQL return 0 for SELECT.


### Parameters

***statement***
>The SQL statement to be executed. Can have placeholders. Must contain only a single statement (multiple statements separated by semi-colons are not allowed). If any parameters are used, they are referred to as ?, ?, etc.

***params***
>An array of parameter values to substitute for the ?, ?, etc. placeholders in the original prepared SQL statement string. The number of elements in the array must match the number of placeholders.


### Example

```php
<?php

$db_options = array(
    'ConvertNumeric'    => true,
    'UseDebug'          => true
);
// Create DSN 
$dsn = (new Falseclock\DBD\Pg())->create("host=localhost;port=5432;dbname=falseclock", "username","password", $db_options);

// make connection to the database
$db = $dsn->connect();

// Bad example how SQL can be injected as every string parameter must be escaped 
// manually or with $db->quote('must be null');
$param = "'must be null'";
$result = $db->doit("UPDATE table SET column1 = NULL WHERE column2 = $param");

// more easiest, simple and safe for SQL injections way.
// Number of affected tuples will be stored in $result variable
$result = $db->doit("UPDATE table SET column1 = ? WHERE column2 = ?", NULL, 'must be null');
?>
```


* * *
# **prepare**

**prepare** — creates a prepared statement for later execution with [execute](#execute)() method. This feature allows commands that will be used repeatedly to be parsed and planned just once, rather than each time they are executed.

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
$db_options = array(
    'ConvertNumeric'    => true,
    'UseDebug'          => true
);
// Create DSN 
$dsn = (new Falseclock\DBD\Pg())->create("host=localhost;port=5432;dbname=falseclock", "username","password", $db_options);

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
# **fetchrow**

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
# **fetchrowset**

**fetchrowset** — fetch a full result as multidimensional array, where each element is an associative array that corresponds to the fetched row.

### Description

```php
array fetchrowset ([ string $key ])
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
>You can define which fields of the table you want return after succesfull insert


### Example 1

```php
<?php
$insert = array(
	'vatinvoice_uuid' => $doc['Ref'],
	'vatinvoice_date' => $doc['Date'],
	'vatinvoice_number' => $doc['Number'],
	'vatinvoice_amount' => $doc['Amount'],
	'waybill_uuid' => $doc['reference']['uuid']
);
$sth = $db->insert('vatinvoices',$insert);
echo ($sth->rows);
?>
```
### Example 2

```php
<?php
$insert = array(
	//'payment_id' => IS SERIAL, will be generated automatically
	'payment_uuid' => $payment['Ref'],
	'payment_date' => $payment['Date'],
	'payment_number' => $payment['Number'],
	'payment_amount' => $payment['Amount']
);
$sth = $db->insert('payments',$insert,'payment_id, payment_uuid');
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
$update = array(
	'vatinvoice_date' => $doc['Date'],
	'vatinvoice_number' => $doc['Number'],
	'vatinvoice_amount' => $doc['Amount']
);
// this will update all rows in a table
$sth = $db->update('vatinvoices',$update);
echo ($sth->rows);
?>
```
### Example 2

```php
<?php
$update = array(
	'vatinvoice_date' => $doc['Date'],
	'vatinvoice_number' => $doc['Number'],
	'vatinvoice_amount' => $doc['Amount']
);
// this will update all rows in a table where vatinvoice_uuid equals to some value
$sth = $db->update('vatinvoices', $update, "vatinvoice_uuid=?", $doc['UUID']);
echo ($sth->rows);
?>
```
### Example 3

```php
<?php
$update = array(
	'vatinvoice_date' => $doc['Date'],
	'vatinvoice_number' => $doc['Number'],
	'vatinvoice_amount' => $doc['Amount']
);
// this will update all rows in a table where vatinvoice_uuid is null
// query will return vatinvoice_id
$sth = $db->update('vatinvoices', $update, "vatinvoice_uuid IS NULL", "vatinvoice_id");
while ($row = $sth->fetchrow()) {
	printf("Updated vatinvoice with ID=%d\n", $row['vatinvoice_id']);
}
?>
```
### Example 4

```php
<?php
$update = array(
	'vatinvoice_date' => $doc['Date'],
	'vatinvoice_number' => $doc['Number'],
	'vatinvoice_amount' => $doc['Amount']
);
// this will update all rows in a table where vatinvoice_uuid equals to some value
// query will return vatinvoice_id
$sth = $db->update('vatinvoices',$update,"vatinvoice_uuid =? ", $doc['UUID'], "vatinvoice_id, vatinvoice_uuid");
while ($row = $sth->fetchrow()) {
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
$db_options = array(
    'ConvertNumeric'    => true,
    'UseDebug'          => true
);
// Create DSN 
$dsn = (new Falseclock\DBD\Pg())->create("host=localhost;port=5432;dbname=falseclock", "username","password", $db_options);

// make connection to the database
$db = $dsn->connect();
$db->begin();

// Common usage for repeatedly UPDATE queries
$sth = $db->prepare("SELECT col1, col2, col3 FROM table1");
$std = $db->prepare("UPDATE table2 SET col2 =? WHERE col1=? AND col2=?");

$sth->execute();

while ($row = $sth->fetchrow()) {
	if ($row['col1'] == 'banana') {
    	$std->execute(FALSE,NULL,$row[col2]);
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


