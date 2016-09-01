<?php

namespace BootPress\Tests;

use BootPress\Page\Component as Page;
use BootPress\Database\Component as Database;
use BootPress\Hierarchy\Component as Hierarchy;
use BootPress\Bootstrap3\Component as Bootstrap;
use Symfony\Component\HttpFoundation\Request;

class HierarchyTest extends \PHPUnit_Framework_TestCase
{
    protected static $db;
    protected static $hier;

    public static function setUpBeforeClass()
    {
        $request = Request::create('http://website.com/');
        Page::html(array('dir' => __DIR__.'/page', 'suffix'=>'.html'), $request, 'overthrow');
    }

    public function testConstructMethod()
    {
        self::$db = new Database('sqlite::memory:');
        self::$db->connection()->exec(implode("\n", array(
            'CREATE TABLE category (',
            '  id INTEGER PRIMARY KEY,',
            '  name TEXT NOT NULL DEFAULT "",',
            '  parent INTEGER NOT NULL DEFAULT 0,',
            '  level INTEGER NOT NULL DEFAULT 0,',
            '  lft INTEGER NOT NULL DEFAULT 0,',
            '  rgt INTEGER NOT NULL DEFAULT 0',
            ')',
        )));
        if ($stmt = self::$db->insert('category', array('id', 'name', 'parent'))) {
            self::$db->insert($stmt, array(1, 'Electronics', 0));
            self::$db->insert($stmt, array(2, 'Televisions', 1));
            self::$db->insert($stmt, array(3, 'Tube', 2));
            self::$db->insert($stmt, array(4, 'LCD', 2));
            self::$db->insert($stmt, array(5, 'Plasma', 2));
            self::$db->insert($stmt, array(6, 'Portable Electronics', 1));
            self::$db->insert($stmt, array(7, 'MP3 Players', 6));
            self::$db->insert($stmt, array(8, 'Flash', 7));
            self::$db->insert($stmt, array(9, 'CD Players', 6));
            self::$db->insert($stmt, array(10, '2 Way Radios', 6));
            self::$db->insert($stmt, array(11, 'Apple in California', 1));
            self::$db->insert($stmt, array(12, 'Made in USA', 11));
            self::$db->insert($stmt, array(13, 'Assembled in China', 11));
            self::$db->insert($stmt, array(14, 'iPad', 13));
            self::$db->insert($stmt, array(15, 'iPhone', 13));
            self::$db->close($stmt);
        }
        self::$hier = new Hierarchy(self::$db, 'category', 'id');
        self::$hier->refresh();
    }

    public function testDeleteMethod()
    {
        $this->assertEquals(array(14), self::$hier->delete(14));
        $this->assertEquals(array(11, 12, 13, 15), self::$hier->delete(11));
        $this->assertFalse(self::$hier->delete(15));
        self::$hier->refresh();
    }

    public function testIdMethod()
    {
        $this->assertEquals(1, self::$hier->id('name', array('Electronics')));
        $this->assertEquals(9, self::$hier->id('name', array('Electronics', 'Portable Electronics', 'CD Players')));
        $this->assertFalse(self::$hier->id('name', array('Electronics', 'Apple in California')));
    }

    public function testPathMethod()
    {
        $this->assertEquals(array(), self::$hier->path(array('level', 'name', 'parent'), array()));
        $this->assertEquals(array(
            1 => array('level' => 0, 'name' => 'Electronics', 'parent' => 0),
            6 => array('level' => 1, 'name' => 'Portable Electronics', 'parent' => 1),
            9 => array('level' => 2, 'name' => 'CD Players', 'parent' => 6),
        ), self::$hier->path(array('level', 'name', 'parent'), array('where' => 'id = 9')));
    }

    public function testChildrenMethod()
    {
        $this->assertEquals(array(
            7 => 'MP3 Players',
            9 => 'CD Players',
            10 => '2 Way Radios',
        ), self::$hier->children(6, 'name')); // 6 = Portable Electronics
        $this->assertEquals(array(
            7 => array('level' => 2, 'name' => 'MP3 Players'),
            9 => array('level' => 2, 'name' => 'CD Players'),
            10 => array('level' => 2, 'name' => '2 Way Radios'),
        ), self::$hier->children(6, array('level', 'name')));
    }

    public function testLevelMethod()
    {
        $this->assertEquals(array(
            3 => 'Tube',
            4 => 'LCD',
            5 => 'Plasma',
            7 => 'MP3 Players',
            9 => 'CD Players',
            10 => '2 Way Radios',
        ), self::$hier->level(2, 'name'));
        $this->assertEquals(array(
            3 => array('parent' => 2, 'name' => 'Tube'),
            4 => array('parent' => 2, 'name' => 'LCD'),
            5 => array('parent' => 2, 'name' => 'Plasma'),
            7 => array('parent' => 6, 'name' => 'MP3 Players'),
            9 => array('parent' => 6, 'name' => 'CD Players'),
            10 => array('parent' => 6, 'name' => '2 Way Radios'),
        ), self::$hier->level(2, array('parent', 'name')));
    }

    public function testCountsMethod()
    {
        self::$db->connection()->exec(implode("\n", array(
            'CREATE TABLE product (',
            '  id INTEGER PRIMARY KEY,',
            '  category_id INTEGER NOT NULL DEFAULT 0,',
            '  name TEXT NOT NULL DEFAULT ""',
            ')',
        )));
        if ($stmt = self::$db->insert('product', array('category_id', 'name'))) {
            self::$db->insert($stmt, array(3, '20" TV'));
            self::$db->insert($stmt, array(3, '36" TV'));
            self::$db->insert($stmt, array(4, 'Super-LCD 42"'));
            self::$db->insert($stmt, array(5, 'Ultra-Plasma 62"'));
            self::$db->insert($stmt, array(5, 'Value Plasma 38"'));
            self::$db->insert($stmt, array(7, 'Power-MP3 128mb'));
            self::$db->insert($stmt, array(8, 'Super-Shuffle 1gb'));
            self::$db->insert($stmt, array(9, 'Porta CD'));
            self::$db->insert($stmt, array(9, 'CD To go!'));
            self::$db->insert($stmt, array(10, 'Family Talk 360'));
            self::$db->close($stmt);
        }
        $this->assertEquals(array( // id => count
            1 => 10, // Electronics
            2 => 5, // Televisions
            3 => 2, // Tube
            4 => 1, // LCD
            5 => 2, // Plasma
            6 => 5, // Portable Electronics
            7 => 2, // MP3 Players
            8 => 1, // Flash
            9 => 2, // CD Players
            10 => 1 // 2 Way Radios
        ), self::$hier->counts('product', 'category_id'));
        $this->assertEquals(5, self::$hier->counts('product', 'category_id', 2));
        $this->assertEquals(2, self::$hier->counts('product', 'category_id', 7));
        $this->assertEquals(0, self::$hier->counts('product', 'category_id', 27));
    }

    public function testTreeMethod()
    {
        $this->assertEquals(array(
            1 => array('name' => 'Electronics', 'parent' => 0, 'depth' => 0),
            2 => array('name' => 'Televisions', 'parent' => 1, 'depth' => 1),
            3 => array('name' => 'Tube', 'parent' => 2, 'depth' => 2),
            4 => array('name' => 'LCD', 'parent' => 2, 'depth' => 2),
            5 => array('name' => 'Plasma', 'parent' => 2, 'depth' => 2),
            6 => array('name' => 'Portable Electronics', 'parent' => 1, 'depth' => 1),
            7 => array('name' => 'MP3 Players', 'parent' => 6, 'depth' => 2),
            8 => array('name' => 'Flash', 'parent' => 7, 'depth' => 3),
            9 => array('name' => 'CD Players', 'parent' => 6, 'depth' => 2),
            10 => array('name' => '2 Way Radios', 'parent' => 6, 'depth' => 2),
        ), self::$hier->tree(array('name')));
        $this->assertEquals(array(
            6 => array('name' => 'Portable Electronics', 'parent' => 1, 'depth' => 0),
            7 => array('name' => 'MP3 Players', 'parent' => 6, 'depth' => 1),
            8 => array('name' => 'Flash', 'parent' => 7, 'depth' => 2),
            9 => array('name' => 'CD Players', 'parent' => 6, 'depth' => 1),
            10 => array('name' => '2 Way Radios', 'parent' => 6, 'depth' => 1),
        ), self::$hier->tree(array('name'), array('where' => 'id=6')));
        $this->assertEquals(array(
            8 => array('name' => 'Flash', 'parent' => 7, 'depth' => 2),
        ), self::$hier->tree(array('name'), array('having' => 'depth > 1', 'where' => 'id = 6')));
    }

    public function testNestifyFlattenAndListerMethods()
    {
        $tree = self::$hier->tree(array('name'), array('where' => 'id=6'));
        $nest = self::$hier->nestify($tree);
        $flat = self::$hier->flatten($nest);
        $array = self::$hier->lister($tree);
        $this->assertEquals(array( // nestify($tree)
            6 => array(
                7 => array(
                    8 => array(),
                ),
                9 => array(),
                10 => array(),
            ),
        ), $nest);
        $this->assertEquals(array( // flatten($nest)
            array(6, 7, 8),
            array(6, 9),
            array(6, 10),
        ), $flat);
        $this->assertEquals(array( // lister($tree)
            'Portable Electronics' => array(
                'MP3 Players' => array(
                    'Flash',
                ),
                'CD Players',
                '2 Way Radios',
            ),
        ), $array);
        $bp = new Bootstrap();
        $html = '<ol>';
        $html .= '<li>Portable Electronics<ol>';
        $html .= '<li>MP3 Players<ol>';
        $html .= '<li>Flash</li>';
        $html .= '</ol></li>';
        $html .= '<li>CD Players</li>';
        $html .= '<li>2 Way Radios</li>';
        $html .= '</ol></li>';
        $html .= '</ol>';
        $this->assertEquals($html, $bp->lister('ol', $array));
    }
}
