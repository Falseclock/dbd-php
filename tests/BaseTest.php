<?php
require_once('/var/www/crm.virtex.kz/lib/vendor/autoload.php');

/** @var array $TEST */
$TEST = [
    'memcache' => [
        [
            'host' => '127.0.0.1',
            'port' => 11211,
        ],
    ],
    'database' => [
        [
            'type'     => 'MySQL',
            'database' => 'test',
            'user'     => 'user',
            'password' => 'password',
            'host'     => '172.16.0.10',
            'port'     => '3306',
            'cache'    => true,
            'options'  => [
                'OnDemand'           => true,
                'RaiseError'         => true,
                'PrintError'         => true,
                'HTMLError'          => false,
                'ShowErrorStatement' => true,
                'ConvertNumeric'     => true,
                'UseDebug'           => true,
            ],
        ],/*
        [
            'type'     => 'MSSQL',
            'database' => 'virtex',
            'user'     => 'virtex',
            'password' => '123456',
            'host'     => '192.168.20.100',
            'port'     => '1433',
            'cache'    => true,
            'options'  => [
                'OnDemand'           => true,
                'RaiseError'         => true,
                'PrintError'         => true,
                'HTMLError'          => false,
                'ShowErrorStatement' => true,
                'ConvertNumeric'     => true,
                'UseDebug'           => true
            ]
        ],*/

        [
            'type'     => 'Pg',
            'database' => 'dar',
            'user'     => 'dar',
            'password' => 'dar2dar',
            'host'     => 'localhost',
            'port'     => '5432',
            'cache'    => true,
            'options'  => [
                'OnDemand'           => true,
                'RaiseError'         => true,
                'PrintError'         => true,
                'HTMLError'          => false,
                'ShowErrorStatement' => true,
                'ConvertNumeric'     => true,
                'UseDebug'           => true,
            ],
        ],
    ],
];

//$loader = new Psr4AutoloaderClass;
//$loader->register();
//$loader->addNamespace('DBD', '/var/www/crm.virtex.kz/lib/vendor/falseclock/dbd-php/src');

$cache = null;
if($TEST['memcache']) {
    $cache = DBD\Cache\MemCache::me()->setup($TEST['memcache'], false, "10 sec")->connect();
}

$db_options = [
    'OnDemand'           => true,
    'RaiseError'         => true,
    'PrintError'         => true,
    'HTMLError'          => false,
    'ShowErrorStatement' => true,
    'CacheDriver'        => $cache,
    'ConvertNumeric'     => true,
    'UseDebug'           => true,
];

foreach($TEST['database'] as $database) {
    $driver = "DBD\\" . $database['type'];

    if($database['cache'] == true) {
        $database['options']['CacheDriver'] = $cache;
    }

    /** @var \DBD\Pg $dbd */
    $dbd = new $driver;
    $dbh = $dbd->create($database['host'], $database['port'], $database['database'], $database['user'], $database['password'], $database['options']);
    $db = $dbh->connect();

    (new Tests($db))->Begin()
                    ->Commit()
                    ->Rollback()
                    ->TableCreation()
                    ->TableInsertion()
                    ->TableUpdate()
                    ->TableDelete()
                    ->TableInsert()
                    ->CheckPlaceHolder()
                    ->CheckFetch()
                    ->CheckCache()
                    ->TableDrop();

    $db->disconnect();
}

$cache->disconnect();

final class Tests
{
    private $db      = null;
    private $queries = [
        'table_create'         => "CREATE TABLE test_purposes (id INT NOT NULL, name VARCHAR(128))",
        'table_drop'           => "DROP TABLE test_purposes",
        'table_insert'         => [
            "INSERT INTO test_purposes (id,name) VALUES (1 ,'A')",
            "INSERT INTO test_purposes (id,name) VALUES (2 ,'B')",
            "INSERT INTO test_purposes (id,name) VALUES (3 ,'C')",
            "INSERT INTO test_purposes (id,name) VALUES (4 ,'D')",
            "INSERT INTO test_purposes (id,name) VALUES (5 ,'E')",
            "INSERT INTO test_purposes (id,name) VALUES (6 ,'F')",
            "INSERT INTO test_purposes (id,name) VALUES (7 ,'G')",
            "INSERT INTO test_purposes (id,name) VALUES (8 ,'H')",
            "INSERT INTO test_purposes (id,name) VALUES (9 ,'I')",
            "INSERT INTO test_purposes (id,name) VALUES (10,'J')",
            "INSERT INTO test_purposes (id,name) VALUES (11,'K')",
            "INSERT INTO test_purposes (id,name) VALUES (12,'L')",
            "INSERT INTO test_purposes (id,name) VALUES (13,'M')",
            "INSERT INTO test_purposes (id,name) VALUES (14,'N')",
            "INSERT INTO test_purposes (id,name) VALUES (15,'O')",
            "INSERT INTO test_purposes (id,name) VALUES (16,'P')",
            "INSERT INTO test_purposes (id,name) VALUES (17,'Q')",
            "INSERT INTO test_purposes (id,name) VALUES (18,'R')",
            "INSERT INTO test_purposes (id,name) VALUES (19,'S')",
            "INSERT INTO test_purposes (id,name) VALUES (20,'T')",
            "INSERT INTO test_purposes (id,name) VALUES (21,'U')",
            "INSERT INTO test_purposes (id,name) VALUES (22,'V')",
            "INSERT INTO test_purposes (id,name) VALUES (23,'W')",
            "INSERT INTO test_purposes (id,name) VALUES (24,'X')",
            "INSERT INTO test_purposes (id,name) VALUES (25,'Y')",
            "INSERT INTO test_purposes (id,name) VALUES (26,'Z')",
            "INSERT INTO test_purposes (id,name) VALUES (27,'a')",
            "INSERT INTO test_purposes (id,name) VALUES (28,'b')",
            "INSERT INTO test_purposes (id,name) VALUES (29,'c')",
            "INSERT INTO test_purposes (id,name) VALUES (30,'d')",
            "INSERT INTO test_purposes (id,name) VALUES (31,'e')",
            "INSERT INTO test_purposes (id,name) VALUES (32,'f')",
            "INSERT INTO test_purposes (id,name) VALUES (33,'g')",
            "INSERT INTO test_purposes (id,name) VALUES (34,'h')",
            "INSERT INTO test_purposes (id,name) VALUES (35,'i')",
            "INSERT INTO test_purposes (id,name) VALUES (36,'j')",
            "INSERT INTO test_purposes (id,name) VALUES (37,'k')",
            "INSERT INTO test_purposes (id,name) VALUES (38,'l')",
            "INSERT INTO test_purposes (id,name) VALUES (39,'m')",
            "INSERT INTO test_purposes (id,name) VALUES (40,'n')",
            "INSERT INTO test_purposes (id,name) VALUES (41,'o')",
            "INSERT INTO test_purposes (id,name) VALUES (42,'p')",
            "INSERT INTO test_purposes (id,name) VALUES (43,'q')",
            "INSERT INTO test_purposes (id,name) VALUES (44,'r')",
            "INSERT INTO test_purposes (id,name) VALUES (45,'s')",
            "INSERT INTO test_purposes (id,name) VALUES (46,'t')",
            "INSERT INTO test_purposes (id,name) VALUES (47,'u')",
            "INSERT INTO test_purposes (id,name) VALUES (48,'v')",
            "INSERT INTO test_purposes (id,name) VALUES (49,'w')",
            "INSERT INTO test_purposes (id,name) VALUES (50,'x')",
            "INSERT INTO test_purposes (id,name) VALUES (51,'y')",
            "INSERT INTO test_purposes (id,name) VALUES (52,'z')",
        ],
        'table_delete'         => 'DELETE FROM test_purposes',
        'table_updates'        => [
            "UPDATE test_purposes SET name = 'A' WHERE id = 1 ",
            "UPDATE test_purposes SET name = 'B' WHERE id = 2 ",
            "UPDATE test_purposes SET name = 'C' WHERE id = 3 ",
            "UPDATE test_purposes SET name = 'D' WHERE id = 4 ",
            "UPDATE test_purposes SET name = 'E' WHERE id = 5 ",
            "UPDATE test_purposes SET name = 'F' WHERE id = 6 ",
            "UPDATE test_purposes SET name = 'G' WHERE id = 7 ",
            "UPDATE test_purposes SET name = 'H' WHERE id = 8 ",
            "UPDATE test_purposes SET name = 'I' WHERE id = 9 ",
            "UPDATE test_purposes SET name = 'J' WHERE id = 10",
            "UPDATE test_purposes SET name = 'K' WHERE id = 11",
            "UPDATE test_purposes SET name = 'L' WHERE id = 12",
            "UPDATE test_purposes SET name = 'M' WHERE id = 13",
            "UPDATE test_purposes SET name = 'N' WHERE id = 14",
            "UPDATE test_purposes SET name = 'O' WHERE id = 15",
            "UPDATE test_purposes SET name = 'P' WHERE id = 16",
            "UPDATE test_purposes SET name = 'Q' WHERE id = 17",
            "UPDATE test_purposes SET name = 'R' WHERE id = 18",
            "UPDATE test_purposes SET name = 'S' WHERE id = 19",
            "UPDATE test_purposes SET name = 'T' WHERE id = 20",
            "UPDATE test_purposes SET name = 'U' WHERE id = 21",
            "UPDATE test_purposes SET name = 'V' WHERE id = 22",
            "UPDATE test_purposes SET name = 'W' WHERE id = 23",
            "UPDATE test_purposes SET name = 'X' WHERE id = 24",
            "UPDATE test_purposes SET name = 'Y' WHERE id = 25",
            "UPDATE test_purposes SET name = 'Z' WHERE id = 26",
            "UPDATE test_purposes SET name = 'a' WHERE id = 27",
            "UPDATE test_purposes SET name = 'b' WHERE id = 28",
            "UPDATE test_purposes SET name = 'c' WHERE id = 29",
            "UPDATE test_purposes SET name = 'd' WHERE id = 30",
            "UPDATE test_purposes SET name = 'e' WHERE id = 31",
            "UPDATE test_purposes SET name = 'f' WHERE id = 32",
            "UPDATE test_purposes SET name = 'g' WHERE id = 33",
            "UPDATE test_purposes SET name = 'h' WHERE id = 34",
            "UPDATE test_purposes SET name = 'i' WHERE id = 35",
            "UPDATE test_purposes SET name = 'j' WHERE id = 36",
            "UPDATE test_purposes SET name = 'k' WHERE id = 37",
            "UPDATE test_purposes SET name = 'l' WHERE id = 38",
            "UPDATE test_purposes SET name = 'm' WHERE id = 39",
            "UPDATE test_purposes SET name = 'n' WHERE id = 40",
            "UPDATE test_purposes SET name = 'o' WHERE id = 41",
            "UPDATE test_purposes SET name = 'p' WHERE id = 42",
            "UPDATE test_purposes SET name = 'q' WHERE id = 43",
            "UPDATE test_purposes SET name = 'r' WHERE id = 44",
            "UPDATE test_purposes SET name = 's' WHERE id = 45",
            "UPDATE test_purposes SET name = 't' WHERE id = 46",
            "UPDATE test_purposes SET name = 'u' WHERE id = 47",
            "UPDATE test_purposes SET name = 'v' WHERE id = 48",
            "UPDATE test_purposes SET name = 'w' WHERE id = 49",
            "UPDATE test_purposes SET name = 'x' WHERE id = 50",
            "UPDATE test_purposes SET name = 'y' WHERE id = 51",
            "UPDATE test_purposes SET name = 'z' WHERE id = 52",
        ],
        'table_delete1'        => "DELETE FROM test_purposes WHERE id <= 20",
        'table_delete2'        => "DELETE FROM test_purposes",
        'table_select_all'     => "SELECT * FROM test_purposes",
        'table_select_count'   => "SELECT count(*) FROM test_purposes",
        'table_select_ph'      => "SELECT * FROM test_purposes WHERE id=?",
        'table_select_ph_char' => "SELECT * FROM test_purposes WHERE lower(name) = lower(?)",
    ];

    /**
     * Tests constructor.
     *
     * @param DBD\Pg $db
     */
    public function __construct($db) {

        printf("--- Testing %s driver ---\n", (new Colors)->getColoredString(get_parent_class($db), "white"));

        $this->db = $db;
    }

    public function Begin() {

        $this->testHeader("Transaction BEGIN");

        $this->db->begin();

        $this->testPass();

        return $this;
    }

    private function testHeader($string) {
        printf("%-50s", $string);
    }

    private function testPass() {
        printf("%s\n", (new Colors)->getColoredString("PASS", "light_green"));
    }

    public function CheckCache() {
        $this->testHeader("Memcache tests");

        $db = $this->db;

        $sth = $db->prepare($this->queries['table_select_all']);
        $sth->cache('test_purposed', '5s');
        $sth->execute();
        if($sth->getResult() != "cached" && $sth->getStorage() != "database") {
            $this->testFail("cache test1 failed");
        }
        $result1 = $sth->fetchRowSet();

        $sth->execute();
        if($sth->getResult() != "cached" && $sth->getStorage() != "cache") {
            $this->testFail("cache test2 failed");
        }
        $result2 = $sth->fetchRowSet();

        $sta = $db->prepare($this->queries['table_select_all']);
        $sta->cache('test_purposed', '5s');
        $sta->execute();
        if($sth->getResult() != "cached" && $sth->getStorage() != "cache") {
            $this->testFail("cache test3 failed");
        }
        $result3 = $sta->fetchRowSet();

        $result4 = DBD\Cache\MemCache::me()::me()->get('test_purposed');

        if($result1 !== $result2) {
            $this->testFail("cache fetchrowset \$result1 != \$result2");
        }
        if($result1 !== $result3) {
            $this->testFail("cache fetchrowset \$result1 != \$result3");
        }
        if($result1 !== $result4) {
            $this->testFail("cache fetchrowset \$result1 != \$result4");
        }

        sleep(6);
        $result5 = DBD\Cache\MemCache::me()->get('test_purposed');

        if($result5 !== false) {
            $this->testFail("cache test5 failed");
        }

        $sth = $db->prepare($this->queries['table_select_all']);
        $sth->cache('test_purposed', '5s');
        $sth->execute();
        if($sth->getResult() != "cached" && $sth->getStorage() != "database") {
            $this->testFail("cache test6 failed");
        }
        $result1 = $sth->fetchRow();

        $sth->execute();
        if($sth->getResult() != "cached" && $sth->getStorage() != "cache") {
            $this->testFail("cache test7 failed");
        }
        $result2 = $sth->fetchRow();

        $sta = $db->prepare($this->queries['table_select_all']);
        $sta->cache('test_purposed', '5s');
        $sta->execute();
        if($sth->getResult() != "cached" && $sth->getStorage() != "cache") {
            $this->testFail("cache test8 failed");
        }
        $result3 = $sta->fetchRow();

        $result4 = DBD\Cache\MemCache::me()->get('test_purposed')[0];

        if($result1 !== $result2) {
            $this->testFail("cache fetchrow \$result1 != \$result2");
        }
        if($result1 !== $result3) {
            $this->testFail("cache fetchrow \$result1 != \$result3");
        }
        if($result1 !== $result4) {
            dump($result1);
            dump($result4);
            $this->testFail("cache fetchrow \$result1 != \$result4");
        }

        sleep(6);
        $result5 = DBD\Cache\MemCache::me()::me()->get('test_purposed');

        if($result5 !== false) {
            $this->testFail("cache test9 failed");
        }

        $this->testPass();

        return $this;
    }

    private function testFail($error = "") {
        $this->db->rollback();
        printf("%s\n", (new Colors)->getColoredString("FAIL: $error", "light_red"));
        exit();
    }

    public function CheckFetch() {
        $this->testHeader("Fetch operations");

        $db = $this->db;

        $sth = $db->prepare($this->queries['table_select_ph_char']);
        $sth->execute('A');

        $i = 0;
        $expect = [ 1, 'A' ];
        $real = [];
        while($row = $sth->fetch()) {
            $real[] = $row;
            $i++;
        }
        if(count($real) != 2) {
            $this->testFail("method returned " . count($real) . " instead of 2");
        }

        if($expect != $real) {
            $this->testFail("real not equal to expect");
        }

        $this->testPass();

        return $this;
    }

    public function CheckPlaceHolder() {

        $this->testHeader("Placholder operations");

        $db = $this->db;

        $db->doIt($this->queries['table_delete']);
        foreach($this->queries['table_insert'] as $query) {
            if($db->doIt($query) !== 1) {
                $this->testFail("doit method returned not equal 1");
            }
        }

        $sth = $db->prepare($this->queries['table_select_ph']);
        $sth->execute(1);

        if($sth->rows() != 1) {
            $this->testFail("prepare method returned " . $sth->rows() . " instead of 1");
        }

        $sth = $db->prepare($this->queries['table_select_ph_char']);
        $sth->execute('A');

        if($sth->rows() != 2) {
            $this->testFail("prepare method returned " . $sth->rows() . " instead of 2");
        }

        $sth = $db->prepare($this->queries['table_select_ph']);
        $sth->execute(1);

        if(count($sth->fetchRowSet()) != 1) {
            $this->testFail("fetchrowset-1.0 method returned " . $sth->rows() . " instead of 1");
        }

        $sth = $db->prepare($this->queries['table_select_ph_char']);
        $sth->execute('A');

        if(count($sth->fetchRowSet()) != 2) {
            $this->testFail("fetchrowset-1.1 method returned " . $sth->rows() . " instead of 2");
        }

        $sth = $db->prepare($this->queries['table_select_ph']);
        $sth->execute(1);

        if(count($sth->fetchRowSet('name')) != 1) {
            $this->testFail("fetchrowset-2.0 method returned " . $sth->rows() . " instead of 1");
        }

        $sth = $db->prepare($this->queries['table_select_ph_char']);
        $sth->execute('A');

        if(count($sth->fetchRowSet('name')) != 2) {
            $this->testFail("fetchrowset-2.1 method returned " . $sth->rows() . " instead of 2");
        }

        $this->testPass();

        return $this;
    }

    public function Commit() {

        $this->testHeader("Transaction COMMIT");

        $this->db->doIt($this->queries['table_create']);
        $this->db->doIt($this->queries['table_drop']);

        $this->db->commit();

        $this->testPass();

        return $this;
    }

    public function Rollback() {

        $this->testHeader("Transaction ROLLBACK");

        $this->db->begin();
        $this->db->doIt($this->queries['table_create']);
        $this->db->rollback();

        $this->testPass();

        return $this;
    }

    public function TableCreation() {

        $this->testHeader("Table creation");

        //--------------------------------
        // Table creation through prepare
        $sth = $this->db->prepare($this->queries['table_create']);
        $sth->execute();

        if($sth->rows() !== 0) {
            $this->testFail("prepare method returned " . $sth->rows());
        }
        $this->db->doIt($this->queries['table_drop']);

        //--------------------------------
        // Table creation through do
        $result = $this->db->doIt($this->queries['table_create']);
        if($result !== 0) {
            $this->testFail("do method returned {$result}");
        }
        $this->db->doIt($this->queries['table_drop']);

        //--------------------------------
        // Table creation through query
        $sth = $this->db->query($this->queries['table_create']);

        if($sth->rows() !== 0) {
            $this->testFail("query method returned " . $sth->rows());
        }
        $this->db->doIt($this->queries['table_drop']);

        //--------------------------------
        // Table creation through prepare without drop
        $sth = $this->db->prepare($this->queries['table_create']);
        $sth->execute();

        if($sth->rows() !== 0) {
            $this->testFail();
        }

        $this->testPass();

        return $this;
    }

    public function TableDelete() {
        $this->testHeader("Data deletion");

        $db = $this->db;

        $sth = $db->prepare($this->queries['table_delete1']);
        $std = $db->prepare($this->queries['table_delete2']);
        $sta = $db->prepare($this->queries['table_select_all']);

        $sta->execute();
        $total = $sta->rows();

        $sth->execute();
        $delete1 = $sth->rows();

        $std->execute();
        $delete2 = $std->rows();

        if($total != $delete1 + $delete2) {
            $this->testFail("delete count mismatch: total: $total, delete1: $delete1, delete2: $delete2");
        }

        $sta->execute();
        if($sta->rows() !== 0) {
            $this->testFail("delete remain count mismatch");
        }
        $this->testPass();

        return $this;
    }

    public function TableDrop() {
        $this->testHeader("Table drop");

        $db = $this->db;
        $db->doIt($this->queries['table_drop']);

        $this->testPass();

        return $this;
    }

    public function TableInsert() {
        /** @var \DBD\DBD $db */
        $db = $this->db;

        $this->testHeader("Insert method");

        $db->doIt($this->queries['table_delete']);

        $insert = [
            'id'   => 1,
            'name' => 'test name',
        ];
        for($i = 0; $i < 100; $i++) {
            $db->insert('test_purposes', $insert);
        }
        $this->testPass();

        $this->testHeader("Select method");

        $count = $db->select($this->queries['table_select_count']);

        if($count != 100) {
            $this->testFail(sprintf("total count mismatch: %s(%s)", gettype($count), $count));
        }
        $this->testPass();

        if($db->getOption('ConvertNumeric') == true) {
            $this->testHeader("String conversion");
            if(gettype($count) != 'integer') {
                $this->testWarn("expect integer, got " . gettype($count));
            }
            else {
                $this->testPass();
            }
        }

        return $this;
    }

    private function testWarn($warn) {
        printf("%s\n", (new Colors)->getColoredString("WARN: $warn", "yellow"));
    }

    public function TableInsertion() {
        $db = $this->db;

        $this->testHeader("Table insertions");

        // DO method ---------------------------------------------
        $i = 0;
        foreach($this->queries['table_insert'] as $query) {
            if($db->doIt($query) !== 1) {
                $this->testFail("do method returned not equal 1");
            }
            $i++;
        }
        if($db->doIt($this->queries['table_delete']) !== $i) {
            $this->testFail("DO insert not equal $i");
        }
        // PREPARE->EXECUTE ---------------------------------------------
        $i = 0;
        foreach($this->queries['table_insert'] as $query) {
            $sth = $db->prepare($query);
            $sth->execute();

            $rows = $sth->rows();

            if($rows !== 1) {
                $this->testFail("execute method returned $rows, which is not equal to 1");
            }
            $i++;
        }
        $sth = $db->prepare($this->queries['table_delete']);
        $sth->execute();
        if($sth->rows() != $i) {
            $this->testFail("EXECUTE insert not equal to $i");
        }

        // QUERY method ---------------------------------------------
        $i = 0;
        foreach($this->queries['table_insert'] as $query) {
            $sth = $db->query($query);
            $rows = $sth->rows();

            if($rows !== 1) {
                $this->testFail("execute method returned $rows, which is not equal to 1");
            }
            $i++;
        }
        $sth = $db->query($this->queries['table_delete']);

        if($sth->rows() != $i) {
            $this->testFail("EXECUTE insert not equal to $i");
        }

        $this->testPass();

        return $this;
    }

    public function TableUpdate() {
        $db = $this->db;

        $this->testHeader("Table updates");

        foreach($this->queries['table_insert'] as $query) {
            $db->doIt($query);
        }

        // DO method ---------------------------------------------
        $i = 0;
        foreach($this->queries['table_updates'] as $query) {
            if($db->doIt($query) !== 1) {
                $this->testFail("do method returned not equal 1");
            }
            $i++;
        }

        // PREPARE->EXECUTE ---------------------------------------------
        $i = 0;
        foreach($this->queries['table_updates'] as $query) {
            $sth = $db->prepare($query);
            $sth->execute();

            $rows = $sth->rows();

            if($rows !== 1) {
                $this->testFail("execute method returned $rows, which is not equal to 1");
            }
            $i++;
        }

        // QUERY method ---------------------------------------------
        $i = 0;
        foreach($this->queries['table_updates'] as $query) {
            $sth = $db->query($query);
            $rows = $sth->rows();

            if($rows !== 1) {
                $this->testFail("execute method returned $rows, which is not equal to 1");
            }
            $i++;
        }

        // UPDATE method ---------------------------
        $sth = $db->prepare($this->queries['table_select_all']);
        $sth->execute();
        while($row = $sth->fetchRow()) {
            $update = [
                'name' => $row['name'] . "_updated",
            ];
            $sta = $db->update('test_purposes', $update, "id=?", $row['id']);
            if($sta->rows() !== 1) {
                $this->testFail("update method returned " . $sta->rows() . ", which is not equal to 1");
            }
        }

        $this->testPass();

        return $this;
    }
}

/**
 * An example of a general-purpose implementation that includes the optional
 * functionality of allowing multiple base directories for a single namespace
 * prefix.
 *
 * Given a foo-bar package of classes in the file system at the following
 * paths ...
 *
 *     /path/to/packages/foo-bar/
 *         src/
 *             Baz.php             # Foo\Bar\Baz
 *             Qux/
 *                 Quux.php        # Foo\Bar\Qux\Quux
 *         tests/
 *             BazTest.php         # Foo\Bar\BazTest
 *             Qux/
 *                 QuuxTest.php    # Foo\Bar\Qux\QuuxTest
 *
 * ... add the path to the class files for the \Foo\Bar\ namespace prefix
 * as follows:
 *
 *      <?php
 *      // instantiate the loader
 *      $loader = new \Example\Psr4AutoloaderClass;
 *
 *      // register the autoloader
 *      $loader->register();
 *
 *      // register the base directories for the namespace prefix
 *      $loader->addNamespace('Foo\Bar', '/path/to/packages/foo-bar/src');
 *      $loader->addNamespace('Foo\Bar', '/path/to/packages/foo-bar/tests');
 *
 * The following line would cause the autoloader to attempt to load the
 * \Foo\Bar\Qux\Quux class from /path/to/packages/foo-bar/src/Qux/Quux.php:
 *
 *      <?php
 *      new \Foo\Bar\Qux\Quux;
 *
 * The following line would cause the autoloader to attempt to load the
 * \Foo\Bar\Qux\QuuxTest class from /path/to/packages/foo-bar/tests/Qux/QuuxTest.php:
 *
 *      <?php
 *      new \Foo\Bar\Qux\QuuxTest;
 */
class Psr4AutoloaderClass
{
    /**
     * An associative array where the key is a namespace prefix and the value
     * is an array of base directories for classes in that namespace.
     *
     * @var array
     */
    protected $prefixes = [];

    /**
     * Adds a base directory for a namespace prefix.
     *
     * @param string $prefix   The namespace prefix.
     * @param string $base_dir A base directory for class files in the
     *                         namespace.
     * @param bool   $prepend  If true, prepend the base directory to the stack
     *                         instead of appending it; this causes it to be searched first rather
     *                         than last.
     *
     * @return void
     */
    public function addNamespace($prefix, $base_dir, $prepend = false) {
        // normalize namespace prefix
        $prefix = trim($prefix, '\\') . '\\';

        // normalize the base directory with a trailing separator
        $base_dir = rtrim($base_dir, DIRECTORY_SEPARATOR) . '/';

        // initialize the namespace prefix array
        if(isset($this->prefixes[$prefix]) === false) {
            $this->prefixes[$prefix] = [];
        }

        // retain the base directory for the namespace prefix
        if($prepend) {
            array_unshift($this->prefixes[$prefix], $base_dir);
        }
        else {
            array_push($this->prefixes[$prefix], $base_dir);
        }
    }

    /**
     * Loads the class file for a given class name.
     *
     * @param string $class The fully-qualified class name.
     *
     * @return mixed The mapped file name on success, or boolean false on
     * failure.
     */
    public function loadClass($class) {
        // the current namespace prefix
        $prefix = $class;

        // work backwards through the namespace names of the fully-qualified
        // class name to find a mapped file name
        while(false !== $pos = strrpos($prefix, '\\')) {

            // retain the trailing namespace separator in the prefix
            $prefix = substr($class, 0, $pos + 1);

            // the rest is the relative class name
            $relative_class = substr($class, $pos + 1);

            // try to load a mapped file for the prefix and relative class
            $mapped_file = $this->loadMappedFile($prefix, $relative_class);
            if($mapped_file) {
                return $mapped_file;
            }

            // remove the trailing namespace separator for the next iteration
            // of strrpos()
            $prefix = rtrim($prefix, '\\');
        }

        // never found a mapped file
        return false;
    }

    /**
     * Load the mapped file for a namespace prefix and relative class.
     *
     * @param string $prefix         The namespace prefix.
     * @param string $relative_class The relative class name.
     *
     * @return mixed Boolean false if no mapped file can be loaded, or the
     * name of the mapped file that was loaded.
     */
    protected function loadMappedFile($prefix, $relative_class) {
        // are there any base directories for this namespace prefix?
        if(isset($this->prefixes[$prefix]) === false) {
            return false;
        }

        // look through base directories for this namespace prefix
        foreach($this->prefixes[$prefix] as $base_dir) {

            // replace the namespace prefix with the base directory,
            // replace namespace separators with directory separators
            // in the relative class name, append with .php
            $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

            // if the mapped file exists, require it
            if($this->requireFile($file)) {
                // yes, we're done
                return $file;
            }
        }

        // never found it
        return false;
    }

    /**
     * If a file exists, require it from the file system.
     *
     * @param string $file The file to require.
     *
     * @return bool True if the file exists, false if not.
     */
    protected function requireFile($file) {
        if(file_exists($file)) {
            require $file;

            return true;
        }

        return false;
    }

    /**
     * Register loader with SPL autoloader stack.
     *
     * @return void
     */
    public function register() {
        spl_autoload_register(
            [
                $this,
                'loadClass',
            ]);
    }
}

class Colors
{
    private $background_colors = [];
    private $foreground_colors = [];

    public function __construct() {
        // Set up shell colors
        $this->foreground_colors['black'] = '0;30';
        $this->foreground_colors['dark_gray'] = '1;30';
        $this->foreground_colors['blue'] = '0;34';
        $this->foreground_colors['light_blue'] = '1;34';
        $this->foreground_colors['green'] = '0;32';
        $this->foreground_colors['light_green'] = '1;32';
        $this->foreground_colors['cyan'] = '0;36';
        $this->foreground_colors['light_cyan'] = '1;36';
        $this->foreground_colors['red'] = '0;31';
        $this->foreground_colors['light_red'] = '1;31';
        $this->foreground_colors['purple'] = '0;35';
        $this->foreground_colors['light_purple'] = '1;35';
        $this->foreground_colors['brown'] = '0;33';
        $this->foreground_colors['yellow'] = '1;33';
        $this->foreground_colors['light_gray'] = '0;37';
        $this->foreground_colors['white'] = '1;37';

        $this->background_colors['black'] = '40';
        $this->background_colors['red'] = '41';
        $this->background_colors['green'] = '42';
        $this->background_colors['yellow'] = '43';
        $this->background_colors['blue'] = '44';
        $this->background_colors['magenta'] = '45';
        $this->background_colors['cyan'] = '46';
        $this->background_colors['light_gray'] = '47';
    }

    // Returns colored string

    public function getBackgroundColors() {
        return array_keys($this->background_colors);
    }

    // Returns all foreground color names

    public function getColoredString($string, $foreground_color = null, $background_color = null) {
        $colored_string = "";

        // Check if given foreground color found
        if(isset($this->foreground_colors[$foreground_color])) {
            $colored_string .= "\033[" . $this->foreground_colors[$foreground_color] . "m";
        }
        // Check if given background color found
        if(isset($this->background_colors[$background_color])) {
            $colored_string .= "\033[" . $this->background_colors[$background_color] . "m";
        }

        // Add string and end coloring
        $colored_string .= $string . "\033[0m";

        return $colored_string;
    }

    // Returns all background color names

    public function getForegroundColors() {
        return array_keys($this->foreground_colors);
    }
}