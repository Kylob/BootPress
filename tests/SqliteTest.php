<?php

namespace BootPress\Tests;

use BootPress\Page\Component as Page;
use BootPress\SQLite\Component as SQLite;
use BootPress\Fts\Component as Fts;
use Symfony\Component\HttpFoundation\Request;

class SqliteTest extends \PHPUnit_Framework_TestCase
{
    protected static $db;

    public static function setUpBeforeClass()
    {
        $request = Request::create('http://website.com/');
        Page::html(array('dir' => __DIR__.'/page', 'suffix'=>'.html'), $request, 'overthrow');
        self::$db = new Sqlite();
    }

    public static function tearDownAfterClass()
    {
        $page = Page::html();
        if (is_file($page->file('databases.yml'))) {
            unlink($page->file('databases.yml'));
        }
    }

    public function testConstructor()
    {
        $db = new Sqlite();
        $this->assertAttributeInstanceOf('SQLite3', 'connection', $db);
        $this->assertTrue($db->created);
        $file = __DIR__.'/temp/sqlite-test.db';
        if (is_file($file)) {
            unlink($file);
        }
        if (is_dir(dirname($file))) {
            rmdir(dirname($file));
        }
        $db = new Sqlite($file);
        $this->assertFileExists($file);
        $this->assertEquals($file, $db->driver());
        $this->assertInstanceOf('SQLite3', $db->connection());
        $this->assertTrue($db->created);
        $db->connection()->close();
        unlink($file);
        rmdir(dirname($file));
        $this->assertFileNotExists($file);
    }
    
    public function testCreateMethod()
    {
        $this->assertTrue(self::$db->created);
        $fields = array(
            'id' => 'INTEGER PRIMARY KEY',
            'name' => 'TEXT COLLATE NOCASE',
            'position' => 'TEXT NOT NULL DEFAULT ""',
        );
        $this->assertTrue(self::$db->create('employees', $fields, array('unique'=>'position')));
        $fields['position'] = 'TEXT DEFAULT ""';
        $this->assertTrue(self::$db->create('employees', $fields, 'position'));
        $stmt = self::$db->insert('employees', array('id', 'name', 'position'));
        $this->assertGreaterThan(0, $stmt);
        $this->assertEquals(101, self::$db->insert($stmt, array(101, 'John Smith', 'CEO')));
        $this->assertEquals(102, self::$db->insert($stmt, array(102, 'Raj Reddy', 'Developer')));
        $this->assertEquals(103, self::$db->insert($stmt, array(103, 'Jason Bourne', 'Developer')));
        $this->assertEquals(104, self::$db->insert($stmt, array(104, 'Jane Smith', 0.53)));
        $this->assertEquals(105, self::$db->insert($stmt, array(105, 'Rita Patel', null)));
        self::$db->close($stmt);
        $this->assertEquals(5, self::$db->log('count'));
        
        // dbExecute() will catch a \LogicException and return false for a datatype mismatch eg. setting a primary key to null
        $this->assertFalse(self::$db->exec('UPDATE employees SET id = NULL WHERE id = 105'));
        
        $this->assertEquals(6, count(self::$db->row('SELECT * FROM employees WHERE id = ?', 105, 'both')));
        $this->assertFalse(self::$db->create('employees', $fields, 'position'));
        $this->assertFalse(self::$db->create('employees', $fields)); // the index has changed, but not the employees table
        $fields = array(
            'id' => 'INTEGER PRIMARY KEY',
            'name' => 'TEXT UNIQUE COLLATE NOCASE',
            'title' => 'TEXT DEFAULT ""',
        );
        
        $this->assertTrue(self::$db->create('employees', $fields, 'title', array('position' => 'title')));
        $this->assertEquals(1, self::$db->update('employees', 'id', array(104 => array('title'=>'Sales Manager'))));
        $this->assertEquals(1, self::$db->update('employees', 'id', array(105 => array('title'=>'DBA'))));
        
        
    }

    public function testSettingsMethod()
    {
        $this->assertEquals(array(), self::$db->settings());
        $this->assertNull(self::$db->settings('version'));
        $this->assertNull(self::$db->settings('version', false));
        $this->assertFalse(self::$db->settings('version'));
        $this->assertNull(self::$db->settings('version', null));
        $this->assertNull(self::$db->settings('version'));
        $this->assertNull(self::$db->settings('version', '1.3.1'));
        $this->assertEquals('1.3.1', self::$db->settings('version'));
        $this->assertGreaterThan(1, self::$db->settings('version'));
        $this->assertLessThan(2, self::$db->settings('version'));
        $this->assertArrayHasKey('version', self::$db->settings());
        $this->assertNull(self::$db->settings('version', false));
    }

    public function testInOrderMethod()
    {
        $this->assertEquals('id IN(102,103) ORDER BY CASE id WHEN 102 THEN 0 WHEN 103 THEN 1 ELSE NULL END ASC', self::$db->inOrder('id', array(102, 103)));
    }

    public function testFtsCreateMethod()
    {
        $this->assertTrue(self::$db->fts->create('fts', array('find')));
        $this->assertFalse(self::$db->fts->create('fts', array('find')));
        $this->assertTrue(self::$db->fts->create('fts', array('search')));
        // $this->assertFalse(self::$db->fts->create('fts', array('search')));
        $rows = array(
            100 => 'Fisherman never die, they just get reel tired.',
            101 => 'If wishes were fishes, we\'d have a fish fry.',
            102 => 'Women want me, fish fear me.',
            103 => 'Good things come to those who bait.',
            104 => 'A reel expert can tackle anything.',
        );
        $stmt = self::$db->upsert('fts', 'docid', array('search'));
        foreach ($rows as $docid => $search) {
            $this->assertEquals($docid, self::$db->upsert($stmt, $docid, array($search))); // inserting
        }
        self::$db->close($stmt);
        $this->assertEquals(5, self::$db->log('count'));
        $this->assertEquals(5, self::$db->value('SELECT COUNT(*) FROM fts'));
    }

    public function testFtsCountMethod()
    {
        $this->assertEquals(2, self::$db->fts->count('fts', 'fish'));
        $this->assertEquals(2, self::$db->fts->count('fts', 'fish', 'docid > 99'));
    }

    public function testFtsSearchMethod()
    {
        $this->assertCount(2, $search = self::$db->fts->search('fts', 'fish', '0, 10', '', array(), array(2)));
        $this->assertEquals(101, $search[0]['docid']);
        $this->assertEquals(102, $search[1]['docid']);
    }

    public function testFtsWordsMethod()
    {
        $this->assertEquals('fishes, fish', implode(', ', self::$db->fts->words('fts', 'fish', 101)));
        $this->assertEquals('reel expert', implode(', ', self::$db->fts->words('fts', 'reel experts', 104)));
        $this->assertEquals(array(), self::$db->fts->words('fts', 'not here', 104));
    }

    public function testDbPrepareErrorMethod()
    {
        $this->assertFalse(self::$db->query('SELECT * FROM fts WHERE column MATCH ?', 'search'));
        $this->assertNotEmpty(self::$db->log('errors'));
    }

    public function testDbExecuteErrorMethod()
    {
        // The SQLite3Stmt::execute() method does not return false for anything it seems, so ...
        $class = new \ReflectionClass('BootPress\SQLite\Component');
        $method = $class->getMethod('dbExecuteError');
        $method->setAccessible(true);
        $method->invoke(new Sqlite(), 'stmt');
    }

    public function testRecreateMethod()
    {
        $file = __DIR__.'/temp/sqlite-recreate.db';
        if (is_file($file)) {
            unlink($file);
        }
        if (is_dir(dirname($file))) {
            rmdir(dirname($file));
        }
        $this->assertNull(self::$db->recreate($file));
        $this->assertFileExists($file);
        $this->assertNull(self::$db->recreate($file)); // does nothing
        // The main DB connection must be closed before the recreated file can be moved, renamed, deleted, etc.
        self::$db->connection()->close();
        unlink($file);
        rmdir(dirname($file));
        $this->assertFileNotExists($file);
    }
}
