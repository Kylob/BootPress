<?php

namespace BootPress\Tests;

use BootPress\Page\Component as Page;
use BootPress\Asset\Component as Asset;
use BootPress\SQLite\Component as SQLite;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AssetTest extends \PHPUnit_Framework_TestCase
{
    protected static $dir;
    protected static $page;
    protected static $paths;

    public static function setUpBeforeClass()
    {
        self::$dir = str_replace('\\', '/', __DIR__.'/page/assets/');
        self::$page = array('dir' => __DIR__.'/page', 'suffix'=>'.html', 'testing'=>true);
    }

    public static function tearDownAfterClass()
    {
        $db = self::$dir.'Assets.db';
        if (is_file($db)) {
            unlink($db);
        }
    }

    public function testStaticDispatchMethod()
    {
        $request = Request::create(
            'http://website.com/', // uri
            'GET', // method
            array(), // parameters
            array(), // cookies
            array(), // files
            array(), // server
            null // content
        );
        $page = Page::html(self::$page, $request, 'overthrow');
        
        // output content directly
        $cached = time() - 3600; // an hour ago
        $html = Asset::dispatch('html', '<p>Paragraph</p>');
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $html);
        $request->headers->set('If-Modified-Since', gmdate('r', $cached));
        $html = Asset::dispatch('html', array($cached => '<p>Paragraph</p>'));
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $html);
        $this->assertNull($html->headers->get('Content-Type'));
        $request->headers->remove('If-Modified-Since');
        $html = Asset::dispatch('html', array(
            $cached => '<p>Paragraph</p>',
            'expires' => 600,
        ));
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $html);
        $this->assertStringStartsWith('text/html', $html->headers->get('Content-Type'));
        

        // a file type we do not support
        $not_implemented = Asset::dispatch(self::$dir.'index.php');
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $not_implemented);
        $this->assertEquals(501, $not_implemented->getStatusCode());

        // an image that doesn't exist
        $not_found = Asset::dispatch(self::$dir.'image.png');
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $not_found);
        $this->assertEquals(404, $not_found->getStatusCode());

        // a binary download
        $csv = Asset::dispatch(self::$dir.'data.csv', array('xsendfile'=>true));
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\BinaryFileResponse', $csv);

        // a streamed response that we have sent to verify the callback
        $request->headers->set('If-Modified-Since', gmdate('r', filemtime(self::$dir.'empty.txt')));
        $txt = Asset::dispatch(self::$dir.'empty.txt');
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\StreamedResponse', $txt);
        $this->assertNull($txt->headers->get('Content-Type'));
        $request->headers->remove('If-Modified-Since');
        $txt = Asset::dispatch(self::$dir.'empty.txt')->send(); // we send() to call the setCallback()
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\StreamedResponse', $txt);
        $this->assertEquals('text/plain', $txt->headers->get('Content-Type'));
        
    }

    public function testStaticCachedMethod()
    {
        // an HTML page
        $page = Page::html(self::$page, Request::create('http://website.com/file.html'), 'overthrow');
        $this->assertFalse(Asset::cached(self::$dir));

        // an uncached (not 5 chars) resource
        $page = Page::html(self::$page, Request::create('http://website.com/file.js'), 'overthrow');
        $this->assertFalse(Asset::cached(self::$dir));

        // a cached (5 chars) resource that has not been saved to the database
        $db = self::$dir.'Assets.db';
        if (is_file($db)) {
            unlink($db);
        }
        $this->assertFileNotExists($db);
        $page = Page::html(self::$page, Request::create('http://website.com/abcde/file.js'), 'overthrow');
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', Asset::cached(self::$dir));
        $this->assertFileExists($db);
    }

    public function testStaticUrlsMethod()
    {
        $page = Page::html(self::$page, Request::create('http://website.com/index.html', 'GET'), 'overthrow');
        
        // empty string and no links
        $this->assertEquals('', Asset::urls(''));
        $this->assertEquals('No Links', Asset::urls('No Links'));

        $html = array(
            'head' => '<!doctype html><html><head>',
                'ico' => $page->url('dir', 'assets/missing.ico'),
                'css' => $page->url('dir', 'assets/bom.css'),
            'body' => '</head><body>',
                'html' => '<p>Some <a href="'.$page->url('base', 'index').'">content</a>.</p>',
                'csv' => $page->url('dir', 'assets/data.csv'),
                'jpg' => $page->url('dir', 'assets/image.jpg?w=200#kayaks'),
                'js' => $page->url('dir', 'assets/vjustify.js#sparks.js'),
            'end' => '</body></html>',
        );

        // setup
        $this->assertFalse(Asset::cached(self::$dir));

        // array in, array out
        $compare = Asset::urls($html);

        $this->assertArrayHasKey('head', $compare);
        $this->assertArrayHasKey('css', $compare);
        $this->assertArrayHasKey('body', $compare);
        $this->assertArrayHasKey('html', $compare);
        $this->assertArrayHasKey('csv', $compare);
        $this->assertArrayHasKey('jpg', $compare);
        $this->assertArrayHasKey('js', $compare);
        $this->assertArrayHasKey('end', $compare);
        $this->assertEquals($html['html'], $compare['html']);
        $this->assertEquals($html['ico'], $compare['ico']);
        $this->assertNotEquals($html['css'], $compare['css']);
        $this->assertNotEquals($html['csv'], $compare['csv']);
        $this->assertNotEquals($html['jpg'], $compare['jpg']);
        $this->assertNotEquals($html['js'], $compare['js']);

        // string in, string out - cached paths remain untouched
        $this->assertEquals(implode("\n", $compare), Asset::urls(implode("\n", $compare)));

        // cached paths remain the same on subsequent loads
        $this->assertEquals(implode("\n", $compare), Asset::urls(implode("\n", $html)));

        // Page::display automatically filtered
        $page->link(array($html['css'], $html['js']));
        $display = $page->display(implode("\n", array($html['html'], $html['csv'], $html['jpg'])));
        $this->assertContains($compare['css'], $display);
        $this->assertContains($compare['js'], $display);
        $this->assertContains($compare['html'], $display);
        $this->assertContains($compare['csv'], $display);
        $this->assertContains($compare['jpg'], $display);

        // updating file modifies cached path
        touch(self::$dir.'bom.css');
        $modified = Asset::urls($html);
        $this->assertNotEquals($compare['css'], $modified['css']);
        $this->assertEquals($compare['csv'], $modified['csv']);
        $this->assertEquals($compare['jpg'], $modified['jpg']);
        $this->assertEquals($compare['js'], $modified['js']);

        // remove any minified contents
        $minify = self::$dir.'minify/'.md5($page->url['base']).'/';
        foreach (glob($minify.'*') as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        if (is_dir($minify)) {
            rmdir($minify);
        }

        // set paths for the remaining tests
        self::$paths = array(
            'minify' => $minify,
            'css' => $modified['css'],
            'csv' => $modified['csv'],
            'js' => $modified['js'],
            'jpg' => $modified['jpg'],
        );
    }
    
    public function testStaticMimeTypes()
    {
        foreach (array('html', 'txt', 'less', 'scss', 'json', 'xml', 'rdf', 'rss', 'atom', 'jpg', 'gif', 'png', 'ico', 'js', 'css', 'pdf', 'ttf', 'otf', 'svg', 'eot', 'woff', 'woff2', 'swf', 'tar', 'tgz', 'gzip', 'zip', 'csv', 'xl', 'xls', 'xlsx', 'word', 'doc', 'docx', 'ppt', 'pptx', 'psd', 'ogg', 'wav', 'mp3', 'mp4', 'mpg', 'qt') as $type) {
            $mimes = Asset::mime(array($type));
            $this->assertNotNull($mimes);
            $this->assertGreaterThan(0, count($mimes));
        }
        foreach (array('not', 'here') as $type) {
            $this->assertNull(Asset::mime($type));
        }
    }

    public function testCssMinifyFunctionality()
    {
        $css = self::$paths['css'];
        $page = Page::html(self::$page, Request::create($css, 'GET'), 'overthrow');
        $path = strstr(substr($css, strlen($page->url['base'])), '/', true);
        $file = self::$paths['minify'].$path.'.css';
        $this->assertFileNotExists($file);
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\StreamedResponse', Asset::cached(self::$dir));
        $this->assertFileExists($file);
        $this->assertContains($page->url['base'], file_get_contents($file));
    }

    public function testNormalFileFunctionality()
    {
        $csv = self::$paths['csv'];
        $page = Page::html(self::$page, Request::create($csv, 'GET'), 'overthrow');
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\BinaryFileResponse', Asset::cached(self::$dir));
    }

    public function testJsMinifyFunctionality()
    {
        $js = self::$paths['js'];
        $page = Page::html(self::$page, Request::create($js, 'GET'), 'overthrow');
        $path = strstr(substr($js, strlen($page->url['base'])), '/', true);
        $file = self::$paths['minify'].$path.'.js';
        $this->assertFileNotExists($file);
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\StreamedResponse', Asset::cached(self::$dir));
        $this->assertFileExists($file);
    }

    public function testGlideFunctionality()
    {
        $jpg = self::$paths['jpg'];
        $page = Page::html(self::$page, Request::create($jpg, 'GET'), 'overthrow');
        $images = self::$dir.'glide/*';
        foreach (glob($images) as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\StreamedResponse', Asset::cached(self::$dir, array(
            'group_cache_in_folders' => false,
            'watermarks' => self::$dir,
            'driver' => 'gd',
            'max_image_size' => 2000 * 2000,
        )));
        $this->assertCount(1, glob($images));
    }
}
