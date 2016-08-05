<?php

namespace BootPress\Tests;

use BootPress\Page\Component as Page;
use BootPress\Theme\Component as Theme;
use Symfony\Component\HttpFoundation\Request;


class ThemeTest extends HTMLUnit_Framework_TestCase
{
    
    private static $theme;
    private static $folder;
    private static $files;
    
    public static function setUpBeforeClass()
    {
        $request = Request::create('http://website.com/');
        $page = Page::html(array('dir' => __DIR__.'/page', 'suffix'=>'.html'), $request, 'overthrow');
        self::$folder = $page->dir('temp');
        self::tearDownAfterClass();
    }
    
    public static function tearDownAfterClass()
    {
        $dir = str_replace(array('/', '\\'), '/', __DIR__).'/page/temp/';
        if (is_dir($dir)) {
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($files as $fileinfo) {
                $remove = ($fileinfo->isDir()) ? 'rmdir' : 'unlink';
                $remove($fileinfo->getRealPath());
            }
            rmdir($dir);
        }
    }
    
    public function testConstructor()
    {
        $request = Request::create('http://website.com/');
        $page = Page::html(array('dir' => __DIR__.'/page', 'suffix'=>'.html'), $request, 'overthrow');
        self::$folder = $page->dir('temp');
        if (is_dir(self::$folder)) {
            rmdir(self::$folder);
        }
        $this->assertFileNotExists(self::$folder);
        self::$theme = new Theme(self::$folder);
        $this->assertFileExists(self::$folder);
        $this->assertAttributeEquals(self::$folder, 'folder', self::$theme);
        $this->assertEquals(self::$folder, self::$theme->folder);
        $this->assertNull(self::$theme->missing);
    }
    
    /**
     * @expectedException LogicException
     */
    public function testAddPageMethodMethod()
    {
        self::$theme->addPageMethod('hello', function(){return 'World';});
        self::$theme->addPageMethod('amigo', 'Hello');
    }
    
    public function testGlobalVarsMethod()
    {
        self::$theme->globalVars('foo', array('bar'));
        self::$theme->globalVars(array(
            'foo' => array('baz', 'qux'),
            'hodge' => 'podge',
        ));
        $this->assertAttributeEquals(array(
            'foo' => array('bar', 'baz', 'qux'),
            'hodge' => 'podge',
        ), 'vars', self::$theme);
        self::$theme->globalVars('foo', 'bar');
        $this->assertAttributeEquals(array(
            'foo' => 'bar',
            'hodge' => 'podge',
        ), 'vars', self::$theme);
    }
    
    public function testLayoutMethod()
    {
        $page = Page::html();
        $this->assertEquals('<p>Content</p>', self::$theme->layout('<p>Content</p>'));
        file_put_contents(self::$folder.'index.tpl', implode("\n", array(
            '{$page->amigo} {$page->hello()} War {$bp->version}',
            '{$bp->icon("thumbs-up")} {$content}',
            '{$page->filter("content", "invalid", "error")}',
            '{$page->filter("content", "prepend", "SPECIAL MESSAGE")}',
        )));
        $this->assertEqualsRegExp(array(
            'World War 3.3.6',
            '<span class="glyphicon glyphicon-thumbs-up"></span>',
            '<p>Content</p>',
        ), self::$theme->layout('<p>Content</p>'));
        
        // test default theme selection
        file_put_contents(self::$folder.'config.yml', 'default: theme'."\n".'name: Website');
        self::$theme = new Theme(self::$folder);
        mkdir(self::$folder.'theme', 0755, true);
        file_put_contents(self::$folder.'theme/config.yml', 'name: Another');
        file_put_contents(self::$folder.'theme/index.tpl', '{$config.name} {$bp->framework} {$config.default}');
        $this->assertEquals('Website bootstrap theme', self::$theme->layout(''));
        $this->assertEquals('http://website.com/page/temp/theme/asset.css', $page->url('theme', 'asset.css'));
        
        // No Theme
        $page->theme = false;
        $this->assertEquals('HTML', self::$theme->layout('HTML'));
        
        // Callable Theme
        $page->theme = function($html, $config) {
            return 'Callable '.$html;
        };
        $this->assertEquals('Callable HTML', self::$theme->layout('HTML'));
        
        // File Theme
        $page->theme = self::$folder.'theme.php';
        file_put_contents($page->theme, implode("\n", array(
            '<?php',
            'extract($params);',
            'echo "File {$content}";',
        )));
        $this->assertEquals('File HTML', self::$theme->layout('HTML'));
        
        // Folder Theme
        $page->theme = 'theme';
        $this->assertEquals('Website bootstrap theme', self::$theme->layout(''));
    }
    
    public function testFetchSmartyMethod()
    {
        $page = Page::html();
        
        // test non-existant file
        $this->assertEquals('<p>The "" file does not exist.</p>', self::$theme->fetchSmarty(''));
        
        // test non-existant file with submitted default folder where it does exist
        $default = $page->file('default.tpl');
        file_put_contents($default, 'Default {template}');
        $this->assertEquals('<p>Syntax error in template "file:default.tpl"  on line 1 "Default {template}" unknown tag "template"</p>', self::$theme->fetchSmarty(array(
            'default' => $page->dir(),
            'vars' => array('syntax'=>'error'),
            'file' => 'default.tpl',
        )));
        
        // test now-existing default file in "testing" mode
        $this->assertFileExists(self::$folder.'default.tpl');
        $this->assertEquals('Syntax error in template "file:default.tpl"  on line 1 "Default {template}" unknown tag "template"', self::$theme->fetchSmarty($default, array('syntax'=>'error'), 'testing'));
        unlink($default);
        
    }
    
}
