<?php

namespace BootPress\Tests;

use BootPress\Page\Component as Page;
use BootPress\Database\Component as Database;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Yaml\Yaml;


class DatabaseTest extends \PHPUnit_Framework_TestCase
{
    protected static $file;
    protected static $db;

    public static function setUpBeforeClass()
    {
        $request = Request::create('http://website.com/');
        $page = Page::html(array('dir' => __DIR__.'/page', 'suffix'=>'.html'), $request, 'overthrow');
        self::$file = $page->file('databases.yml');
        if (is_file(self::$file)) {
            unlink(self::$file);
        }
        self::$db = new Database('sqlite::memory:');
        self::$db->connection()->exec(implode("\n", array(
            'CREATE TABLE employees (',
            '  id INTEGER PRIMARY KEY,',
            '  name TEXT NOT NULL DEFAULT "",',
            '  title TEXT NOT NULL DEFAULT ""',
            ')',
        )));
    }

    public static function tearDownAfterClass()
    {
        self::$db = null;
        if (is_file(self::$file)) {
            unlink(self::$file);
        }
    }

    public function testConstructor()
    {
        $db = new Database(new \PDO('sqlite::memory:'));
        $this->assertEquals('PDO', $db->driver());
        $this->assertAttributeInstanceOf('PDO', 'connection', $db);
        $db = new Database('sqlite::memory:', null, null, array(), array(
            'PRAGMA foreign_keys = ON'
        ));
        $this->assertEquals('sqlite', $db->driver());
        $this->assertAttributeEquals(null, 'connection', $db);
        $this->assertInstanceOf('PDO', $db->connection());
        $this->assertAttributeInstanceOf('PDO', 'connection', $db);
    }

    public function testConstructorMissingDriver()
    {
        $this->setExpectedException('\LogicException');
        $db = new Database('bogus');
    }
    
    public function testConstructorException()
    {
        $db = new Database('missing:dbname=bogus;host=localhost;port=80;', 'username', 'password', array('options' => null));
        $this->setExpectedException('\Exception');
        $db->connection();
    }
    
    public function testConstructorFileDriver()
    {
        $this->assertEquals(array(
            'bogus' => array(
                'dsn' => 'missing:dbname=bogus;host=localhost;port=80;',
                'username' => 'username',
                'password' => 'password',
                'options' => array('options'=>null),
                'exec' => array(),
            ),
        ), Yaml::parse(file_get_contents(self::$file)));
        $db = new Database('bogus');
    }

    public function testExecAndCloseAndLogMethods()
    {
        $this->assertNotFalse(self::$db->exec('PRAGMA foreign_keys = ON'));
        $this->assertGreaterThan(0, self::$db->log('prepared'));
        $this->assertFalse(self::$db->exec('INSERT INTO employees SET value = ?', true));
        $this->assertArrayHasKey('sql', self::$db->log());
        $this->assertArrayHasKey('count', self::$db->log());
        $this->assertNotEmpty(self::$db->log('errors'));
        $this->assertEquals(0, self::$db->log('count'));
    }

    public function testInsertMethod()
    {
        // test multiple inserts
        $stmt = self::$db->insert('employees', array('id', 'name', 'title'));
        $this->assertGreaterThan(0, $stmt);
        $this->assertEquals(101, self::$db->insert($stmt, array(101, 'John Smith', 'CEO')));
        $this->assertEquals(102, self::$db->insert($stmt, array(102, 'Raj Reddy', 'Sysadmin')));
        $this->assertEquals(103, self::$db->insert($stmt, array(103, 'Jason Bourne', 'Developer')));
        $this->assertEquals(104, self::$db->insert($stmt, array(104, 'Jane Smith', 'Sales Manager')));
        $this->assertEquals(105, self::$db->insert($stmt, array(105, 'Rita Patel', 'DBA')));
        self::$db->close($stmt);
        $this->assertEquals(5, self::$db->log('count'));

        // test single insert
        $this->assertEquals(106, self::$db->insert('OR IGNORE INTO employees', array(
            'id' => 106,
            'name' => "Little Bobby'); DROP TABLE employees;--",
            'title' => 'Intern',
        )));
        $this->assertEquals(1, self::$db->log('count'));

        // a single insert, prepared statment error - no salary field
        $this->assertFalse(self::$db->insert('INTO employees', array(
            'id' => 106,
            'name' => "Little Bobby'); DROP TABLE employees;--",
            'title' => 'Intern',
            'salary' => '$5000',
        )));
        $this->assertNotEmpty(self::$db->log('errors')); // verifying a $db->prepare() $error
    }

    public function testUpdateMethod()
    {
        // test multiple updates
        $stmt = self::$db->update('employees', 'id', array('title'));
        $this->assertGreaterThan(0, $stmt);
        $this->assertEquals(1, self::$db->update($stmt, 103, array('Janitor')));
        $this->assertEquals(0, self::$db->update($stmt, 99, array('Quality Control')));
        self::$db->close($stmt);
        $this->assertEquals(2, self::$db->log('count'));

        // test single update
        $this->assertEquals(1, self::$db->update('employees', 'id', array(
            104 => array(
                'title' => 'Sales Woman',
            ),
        )));

        // a single update, unique id constraint
        $this->assertFalse(self::$db->update('employees SET id = 101,', 'id', array(
            106 => array(
                'name' => 'Roberto Cratchit',
                'title' => 'CEO',
            ),
        )));
        $this->assertNotEmpty(self::$db->log('errors')); // verifying a $db->execute() $error
        $this->assertEquals(0, self::$db->log('count'));

        // a single update, prepared statment error - no salary field
        $this->assertFalse(self::$db->update('employees', 'id', array(106 => array('salary' => '$1000000'))));
        $this->assertNotEmpty(self::$db->log('errors')); // verifying a $db->prepare() $error
    }

    public function testUpsertMethod()
    {
        // test multiple upserts
        $stmt = self::$db->upsert('employees', 'id', array('name', 'title'));
        $this->assertGreaterThan(0, $stmt);
        $this->assertEquals(101, self::$db->upsert($stmt, 101, array('Roberto Cratchit', 'CEO')));
        $this->assertEquals(106, self::$db->upsert($stmt, 106, array('John Smith', 'Developer')));
        self::$db->close($stmt);
        $this->assertEquals(0, self::$db->log('count'));

        // test single upsert
        $this->assertEquals(107, self::$db->upsert('employees', 'id', array(
            107 => array(
                'name' => 'Ella Minnow Pea',
                'title' => 'Executive Assistant',
            ),
        )));
        $this->assertEquals(1, self::$db->log('count'));

        // a single upsert, prepared statment error - no salary field
        $this->assertFalse(self::$db->upsert('employees', 'id', array(107 => array('salary' => '$1000'))));
        $this->assertNotEmpty(self::$db->log('errors')); // verifying a $db->prepare() $error
    }

    public function testQueryMethod()
    {
        $this->assertFalse(self::$db->query('INSERT INTO employees (id, name, title) VALUES (?, ?, ?)', array(99, 'Lady', 'Bug')));
        $this->assertGreaterThan(0, $result = self::$db->query('SELECT * FROM employees WHERE id < ?', 100));
        $this->assertFalse(self::$db->fetch($result));
        self::$db->close($result);
        $this->assertGreaterThan(0, $result = self::$db->query('SELECT * FROM employees'));
        $rows = array();
        while ($row = self::$db->fetch($result)) {
            $rows[] = $row;
        }
        $this->assertCount(7, $rows);
        $this->assertEquals(1, self::$db->log('count'));
        self::$db->close($result);
    }

    public function testAllMethod()
    {
        $this->assertEquals(array(
            array(101, 'Roberto Cratchit', 'CEO'),
            array(102, 'Raj Reddy', 'Sysadmin'),
            array(103, 'Jason Bourne', 'Janitor'),
            array(104, 'Jane Smith', 'Sales Woman'),
            array(105, 'Rita Patel', 'DBA'),
            array(106, 'John Smith', 'Developer'),
            array(107, 'Ella Minnow Pea', 'Executive Assistant'),
        ), self::$db->all('SELECT id, name, title FROM employees', '', 'row'));
        $this->assertEquals(array(), self::$db->all('SELECT id, name, title FROM employees WHERE id < 100'));
    }

    public function testIdsMethod()
    {
        $this->assertEquals(array(101, 102, 103, 104, 105, 106, 107), self::$db->ids('SELECT id FROM employees', '', 'assoc'));
        $this->assertFalse(self::$db->ids('SELECT id FROM employees WHERE id < 100'));
    }

    public function testRowMethod()
    {
        $this->assertFalse(self::$db->row('SELECT name, title FROM employees WHERE id = ?', 100));
        $row = self::$db->row('SELECT name, title FROM employees WHERE id = ?', array(101), 'assoc');
        $this->assertArrayHasKey('name', $row);
        $this->assertArrayHasKey('title', $row);
    }

    public function testValueMethod()
    {
        $this->assertFalse(self::$db->value('SELECT field FROM employees'));
        $this->assertArrayHasKey('errors', self::$db->log());
        $this->assertEquals(0, self::$db->value('SELECT COUNT(*) FROM employees WHERE id < ?', array(100)));
        $this->assertEquals(7, self::$db->value('SELECT COUNT(*) FROM employees WHERE id > ?', 100));
    }

    public function testFetchMethod()
    {
        $statement = self::$db->prepare('SELECT * FROM employees WHERE id < ?', 'assoc');
        $this->assertGreaterThan(0, $statement);
        $this->assertTrue(self::$db->execute($statement, 100));
        $this->assertFalse(self::$db->fetch($statement));
        $this->assertTrue(self::$db->execute($statement, 110));
        $this->assertCount(3, $row = self::$db->fetch($statement));
        $this->assertArrayHasKey('id', $row);
        $this->assertArrayHasKey('name', $row);
        $this->assertArrayHasKey('title', $row);
        $this->assertFalse(self::$db->fetch('statement'));
        $this->assertNotEmpty(self::$db->all('SELECT * FROM employees', '', 'obj'));
        $this->assertNotEmpty(self::$db->all('SELECT * FROM employees', '', 'named'));
        $this->assertNotEmpty(self::$db->all('SELECT * FROM employees', '', 'both'));
    }

    public function testDebugMethod()
    {
        $debug = self::$db->debug('INSERT INTO users (id, name, title) VALUES (?, ?, ?)', array(106, "Little Bobby'); DROP TABLE employees;--", 'Intern'));
        $this->assertStringStartsWith('INSERT INTO users (id, name, title) VALUES (', $debug);
        $this->assertContains("'106'", $debug);
        $this->assertContains('Little Bobby', $debug);
        $this->assertContains('DROP TABLE employees;--', $debug);
        $this->assertContains("'Intern'", $debug);
        $this->assertStringEndsWith(')', $debug);
        $this->assertEquals(0, self::$db->connection()->exec($debug));
        $this->assertEquals(7, self::$db->value('SELECT COUNT(*) FROM employees'));
        $this->assertEquals("SELECT * FROM table WHERE row = 'value'", self::$db->debug('SELECT * FROM table WHERE row = ?', 'value'));
    }

    public function testIdAndStaticLogsMethods()
    {
        $logs = Database::logs(self::$db->id());
        $this->assertEquals('sqlite', $logs['driver']);
        $this->assertGreaterThan(0, $logs['duration']);
        $this->assertArrayHasKey('queries', $logs);
        $log = array_shift($logs['queries']);
        $this->assertArrayHasKey('sql', $log);
        $this->assertArrayHasKey('count', $log);
        $this->assertArrayHasKey('duration', $log);
        $errors = Database::errors();
        $this->assertCount(7, $errors[self::$db->id()]['queries']);
    }
}
