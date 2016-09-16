<?php

namespace BootPress\Tests;

use BootPress\Page\Component as Page;
use Symfony\Component\HttpFoundation\Request;

class PageTest extends \BootPress\HTMLUnit\Component
{
    public function testHtmlStaticMethod()
    {
        // To avert a ps_files_cleanup_dir permission denied error.
        if (is_dir('/tmp')) {
            session_save_path('/tmp');
        }
        ini_set('session.gc_probability', 0);
        $request = Request::create('http://website.com/path/to/folder.html', 'GET', array('foo' => 'bar'));
        $page = Page::html(array('testing' => true, 'dir' => __DIR__.'/page', 'suffix' => '.html'), $request);
        $this->assertEquals(str_replace('\\', '/', __DIR__.'/page').'/', $page->dir['page']);
        $this->assertEquals('http://website.com/', $page->url['base']);
        $this->assertEquals('path/to/folder', $page->url['path']);
        $this->assertEquals('.html', $page->url['suffix']);
        $this->assertEquals('?foo=bar', $page->url['query']);
        $this->assertEquals('html', $page->url['format']);
        $this->assertEquals('GET', $page->url['method']);
        $this->assertEquals('/path/to/folder', $page->url['route']);
        $this->assertEquals('http://website.com/path/to/folder.html?foo=bar', $page->url['full']);
    }

    public function testIsolatedStaticMethod()
    {
        $request = Request::create('http://website.com/file.css', 'GET');
        $page = Page::isolated(array('testing'=>true, 'base'=>'https://www.website.com'), $request);
        $page = Page::isolated(array('base'=>'website.com'), $request);
        $this->assertEquals('http://website.com/', $page->url['base']);
        $this->assertEquals('css', $page->url['format']);
        $this->assertEquals('file.css', $page->url['path']);
        $page = Page::isolated(array('dir'=>'page/../page/test'));
        $dir = str_replace('\\', '/', realpath('').'/page/test/');
        $this->assertStringEndsWith($page->dir['page'], $dir);
        $this->assertFileNotExists($dir);
    }

    public function testSetMethod()
    {
        // setter
        $page = Page::html();
        $original = $page->html;
        $html = array(
            'language' => 'en',
            'charset' => 'UTF-8',
            'title' => 'An SEO Title',
            'description' => 'A compelling synopsis.',
            'keywords' => 'this, that',
            'robots' => true,
            'body' => '',
            'string' => 'Of <b>HTML</b>',
            'array' => array('of' => 'information'),
        );
        foreach ($html as $key => $value) {
            $page->set($key, $value);
            $this->assertEquals($value, $page->$key);
        }
        $page->set($original, 'reset');
        foreach ($original as $key => $value) {
            $this->assertEquals($value, $page->$key);
        }
        $this->assertEquals($original, $page->html);
    }

    public function testMagicMethods()
    {
        $page = Page::html();
        $this->assertArrayHasKey('base', $page->url); // __get
        $this->assertArrayHasKey('title', $page->html); // __get
        $page->title = 'New Title'; // __set
        $this->assertEquals('New Title', $page->title); // __get
        $this->assertNull($page->exists); // __get
        $this->assertFalse(isset($page->exists)); // __isset
        $this->assertTrue(empty($page->exists)); // __isset
        $page->exists = true; // __set
        $this->assertTrue($page->exists); // __get
        #-- Set a session --#
        $page->session = new \Symfony\Component\HttpFoundation\Session\Session();
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Session\Session', $page->session);
        #-- We are going to play now with a "product" multidimensional array --#
        $this->assertNull($page->product['price']); // __get
        $this->assertFalse(isset($page->product['price'])); // goes through __get - not __isset
        $this->assertTrue(empty($page->product['price'])); // goes through __get - not __isset
        #-- Now we are setting the "product" --#
        $page->product = array('title' => 'Product', 'price' => 3.50); // __set
        $this->assertEquals(3.5, $page->product['price']); // __get
        #-- Now we unset the "produce" --#
        $page->product = null;
        $this->assertArrayNotHasKey('product', $page->html);
        $this->assertNull($page->product);
        #-- Running the same tests again on an array key we have not established --#
        // $this->assertNull($page->product['quantity']); // __get - The 'quantity' is an "Undefined index" and will on longer assertNull() now that $page->product has been defined
        $this->assertFalse(isset($page->product['quantity'])); // goes through __get - not __isset
        $this->assertTrue(empty($page->product['quantity'])); // goes through __get - not __isset
        #-- Now we establish that array key separately --#
        $page->product['quantity'] = 1; // __set
        #-- And it works --#
        $this->assertEquals(1, $page->product['quantity']); // __get
    }
    
    public function testEjectMethod()
    {
        $page = Page::html();
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\RedirectResponse', $page->eject());
    }

    public function testEnforceMethod()
    {
        $page = Page::html();
        $this->assertNull($page->enforce('path/to/folder'));
        $this->assertNull($page->enforce('/path/to/folder.html'));
        $this->assertNull($page->enforce('/path/to/folder.php'));
        $response = $page->enforce('fancy/title');
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\RedirectResponse', $response);
        $this->assertEquals('http://website.com/fancy/title.html?foo=bar', $response->headers->get('Location'));
        $request = Request::create('http://website.com/image.jpg', 'GET');
        $page = Page::isolated(array('testing'=>true, 'dir' => dirname(__DIR__), 'suffix' => '.html'), $request);
        $this->assertNull($page->enforce('image.jpg'));
        $this->assertNull($page->enforce('http://google.com/')); // silently ignores
        $response =$page->enforce('seo.jpg');
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\RedirectResponse', $response);
        $this->assertEquals('http://website.com/seo.jpg', $response->headers->get('Location'));
    }

    public function testDirnameDirFileAndCommonDirMethods()
    {
        $page = Page::html();
        $this->assertEquals($page->dir['base'], $page->dir('page'));
        $class = $page->dirname(__CLASS__); // This will move 'base' up one dir
        
        $dir = str_replace('\\', '/', __DIR__.'/page').'/';
        $this->assertEquals($dir, $page->dir['page']);
        $this->assertEquals('bootpress-tests-pagetest', $class);
        $this->assertEquals(dirname($dir).'/', $page->dir['bootpress-tests-pagetest']);
        $this->assertNull($page->dirname('Non\\Existant/Class'));
        
        $this->assertEquals($page->dir['base'], dirname($page->dir('page')).'/');
        $this->assertEquals($page->dir('base'), $page->dir('page')); // base in this instance is an alias for page
        $this->assertEquals($page->dir['page'].'one/more/folder/', $page->dir('one/', '/more/folder'));
        $this->assertEquals($page->dir[$class].'folders/', $page->dir($class, 'folders'));
        $this->assertEquals($page->dir[$class], $page->dir($class));
        $this->assertEquals($page->dir['page'], $page->dir());
        $this->assertEquals($page->dir['page'].'folder/file.php', $page->file('folder', 'file.php'));
        $this->assertEquals($page->dir['base'].'one/more/time/', $page->dir($page->dir['base'].'one', 'more/time/'));
        $previous = $page->dir['base'];
        $class = $page->dirname('Symfony\Component\HttpFoundation\Request');
        $this->assertNotEquals($previous, $page->dir['base']);
        $this->assertEquals('C:\\Users\\Owner\\Desktop\\', $page->commonDir(array(
            'C:\\Users\\Owner\\Desktop\\UniServerZ\\www\\index.php',
            'C:\\Users\\Owner\\Desktop\\image.jpg',
        )));
    }
    
    public function testPathMethod()
    {
        $page = Page::html();
        $page->url('set', 'special', 'http://website.com/path/');
        $this->assertEquals($page->url['base'].'folder/', $page->path('folder'));
        $this->assertEquals($page->url['base'].'folder/', $page->path('base', 'folder'));
        $this->assertEquals($page->url['base'].'page/folder/', $page->path('page', 'folder'));
        $this->assertEquals($page->url['base'].'folder/', $page->path($page->url['base'], 'folder'));
        $this->assertEquals('http://website.com/path/folder/', $page->path('special', 'folder'));
        $this->assertEquals($page->url['base'], $page->path());
        
    }

    public function testUrlMethod()
    {
        $page = Page::html();
        $this->assertEquals('http://website.com/path/to/folder.html?foo=bar', $page->url());
        $this->assertEquals('http://website.com/', $page->url('index.html'));
        $this->assertEquals('http://website.com/page/index.html', $page->url('page', 'index.php'));
        $page->url('set', 'folder', 'http://website.com/path/to/folder');
        $this->assertEquals('http://website.com/path/to/folder.html', $page->url('folder'));
        $this->assertEquals('http://website.com/path/to/folder/hierarchy.html', $page->url('folder', '//hierarchy.php/'));
        $this->assertEquals(array('foo' => 'bar'), $page->url('params'));
        $url = $page->url('add', $page->url('base'), array('key' => 'value', 'test' => 'string'));
        $this->assertEquals('http://website.com/?key=value&amp;test=string', $url);
        $this->assertEquals('http://website.com/?key=value&amp;test=string&amp;one=more', $page->url('add', $url, 'one', 'more'));
        $this->assertEquals('http://website.com/?test=string', $page->url('delete', $url, 'key'));
        $this->assertEquals('http://website.com/', $page->url('delete', $url, '?'));
        $this->assertEquals('http://website.com/path/to/folder.html', $page->url('delete', '', '?'));
        $fragment = $page->url('add', $page->url('folder'), '#', 'fragment');
        $this->assertEquals('http://website.com/path/to/folder.html#fragment', $fragment);
        $this->assertEquals('http://website.com/path/to/folder.html', $page->url('delete', $fragment, '#'));
        $this->assertEquals('http://website.com/page/styles.css', $page->url('page', 'styles.css'));
    }
    
    public function testGetAndPostMethods()
    {
        $page = Page::html();
        $this->assertEquals('bar', $page->get('foo'));
        $this->assertNull($page->post('foo'));
    }

    public function testRoutesMethod()
    {
        $routes = array(
            'route.php',
            '///' => 'index.php',
            'listings' => 'listings.php',
            'details/[*:title]-[i:id]' => 'details.php',
        );
        $request = Request::create('http://website.com/', 'GET');
        $page = Page::isolated(array(), $request);
        $this->assertEquals(array(
            'target' => 'index.php',
            'params' => array(),
        ), $page->routes($routes));
        $request = Request::create('http://website.com/listings.html', 'GET');
        $page = Page::isolated(array('suffix' => '.html'), $request);
        $this->assertEquals(array(
            'target' => 'listings.php',
            'params' => array(),
        ), $page->routes($routes));
        $request = Request::create('http://website.com/details/fancy-title-123.html', 'GET');
        $page = Page::isolated(array('suffix' => '.php'), $request);
        $this->assertEquals(array(
            'target' => 'details.php',
            'params' => array(
                'title' => 'fancy-title',
                'id' => 123,
            ),
        ), $page->routes($routes));
    }

    public function testTagMethod()
    {
        $page = Page::html();
        $input = array(
            'type' => 'text',
            'name' => 'field',
            'class' => array(
                'form-control',
                '',
                'input-lg',
            ),
            'selected',
            'required',
            'required' => 'true',
        );
        $this->assertEquals('<input type="text" name="field" class="form-control input-lg" selected required="true">', $page->tag('input', $input));
        $this->assertEquals('<p>One Two Three</p>', $page->tag('p', array('class' => ''), 'One', 'Two', 'Three'));
    }

    public function testMetaMethod()
    {
        $page = Page::html();
        $page->description = 'Meta Description';
        $page->keywords = 'Meta Keywords';
        $page->robots = false;
        $viewport = 'name="viewport" content="width=device-width, initial-scale=1.0"';
        $page->meta($viewport);
        $this->assertAttributeEquals(array('meta' => array($viewport)), 'data', $page);
        $twitter = 'name="twitter:site" content="@bootpress"';
        $page->meta(array('name' => 'twitter:site', 'content' => '@bootpress'));
        $this->assertAttributeEquals(array('meta' => array($viewport, $twitter)), 'data', $page);
        
        $this->assertEqualsRegExp(array(
            '<!doctype html>',
            '<html lang="'.$page->language.'">',
            '<head>',
                '<meta charset="'.$page->charset.'">',
                '<title>'.$page->title.'</title>',
                '<meta name="description" content="'.$page->description.'">',
                '<meta name="keywords" content="'.$page->keywords.'">',
                '<meta name="robots" content="noindex, nofollow">',
                '<meta name="viewport" content="width=device-width, initial-scale=1.0">',
                '<meta name="twitter:site" content="@bootpress">',
            '</head>',
            '<body>',
                '<p>Content</p>',
            '</body>',
            '</html>',
        ), $page->display('<p>Content</p>'));
        
        $html = $page->display('<p>Content</p>');
        $this->assertContains('<meta name="description" content="Meta Description">', $html);
        $this->assertContains('<meta name="keywords" content="Meta Keywords">', $html);
        $this->assertContains('<meta name="robots" content="noindex, nofollow">', $html);
        $this->assertContains('<meta name="viewport" content="width=device-width, initial-scale=1.0">', $html);
        $this->assertContains('<meta name="twitter:site" content="@bootpress">', $html);
    }

    public function testLinkMethod()
    {
        $page = Page::html();
        $page->link(array('script.js#fancy', 'styles.css', 'favicon.ico', 'custom.js', 'icon.apple'), 'prepend');
        $this->assertAttributeContains(array('script.js#fancy', 'custom.js'), 'data', $page);
        $this->assertAttributeContains(array('styles.css'), 'data', $page);
        $this->assertAttributeContains('favicon.ico', 'data', $page);
        $this->assertAttributeContains('icon.png', 'data', $page);
        $page->link('jquery.js', 'prepend');
        $this->assertAttributeContains(array('jquery.js', 'script.js#fancy', 'custom.js'), 'data', $page);
        $page->link('<style>body{background-color:#999;}</style>');
        $this->assertAttributeContains(array('<style>body{background-color:#999;}</style>'), 'data', $page);
        $page->link('<script>alert("Howdy Partner");</script>');
        $this->assertAttributeContains(array('<script>alert("Howdy Partner");</script>'), 'data', $page);
        $page->link('<!--[if IE6]>Special instructions for IE 6 here<![endif]-->');
        $this->assertAttributeContains(array('<!--[if IE6]>Special instructions for IE 6 here<![endif]-->'), 'data', $page);
        
        $this->assertEqualsRegExp(array(
            '<!doctype html>',
            '<html lang="'.$page->language.'">',
            '<head>',
                '<meta charset="'.$page->charset.'">',
                '<title>'.$page->title.'</title>',
                '<meta name="description" content="'.$page->description.'">',
                '<meta name="keywords" content="'.$page->keywords.'">',
                '<meta name="robots" content="noindex, nofollow">',
                '<meta name="viewport" content="width=device-width, initial-scale=1.0">',
                '<meta name="twitter:site" content="@bootpress">',
                '<link rel="shortcut icon" href="favicon.ico">',
                '<link rel="apple-touch-icon" href="icon.png">',
                '<link rel="stylesheet" href="styles.css">',
                '<style>body{background-color:#999;}</style>',
                '<!--[if IE6]>Special instructions for IE 6 here<![endif]-->',
            '</head>',
            '<body>',
                '<p>Content</p>',
                '<script src="jquery.js"></script>',
                '<script src="script.js#fancy"></script>',
                '<script src="custom.js"></script>',
                '<script>alert("Howdy Partner");</script>',
            '</body>',
            '</html>',
        ), $page->display('<p>Content</p>'));
    }

    public function testStyleMethod()
    {
        $page = Page::html();
        $page->style('body { color: #325050; background: #fff; }');
        $page->style(array(
            'a { color: #325050; text-decoration: none; }',
            'a:visited' => 'color: #1a5952;',
            'a:hover, a:focus' => array(
                'color: #0599c2;',
                'text-decoration: underline;',
            ),
        ));
        $html = $page->display('<p>Content</p>');
        $this->assertContains('<style>body { color: #325050; background: #fff; }</style>', $html);
        $this->assertContains('a { color: #325050; text-decoration: none; }', $html);
        $this->assertContains('a:visited { color: #1a5952; }', $html);
        $this->assertContains('a:hover, a:focus { color: #0599c2; text-decoration: underline; }', $html);
    }

    public function testScriptMethod()
    {
        $page = Page::html();
        $page->script(array('alert("Welcome Home");'));
        $this->assertContains('<script>alert("Welcome Home");</script>', $page->display('<p>Content</p>'));
    }

    public function testJqueryMethod()
    {
        $page = Page::html();
        $page->jquery('$("p.neat").show();');
        $html = $page->display('<p>Content</p>');
        $this->assertContains('$("p.neat").show();', $html);
    }

    public function testIdMethod()
    {
        $page = Page::html();
        $this->assertEquals('uniqueI', $page->id('unique'));
        $this->assertEquals('II', $page->id());
        $this->assertEquals('IEIII', $page->id('IE'));
    }

    public function testFolderMethod()
    {
        $page = Page::html();
        $this->assertEquals(array(
            'file' => $page->dir['page'].'assets/index.php',
            'dir' => $page->dir['page'].'assets/',
            'assets' => $page->url['base'].'page/assets/',
            'url' => $page->url['base'].'assets/',
            'folder' => 'assets/',
            'route' => '/route/path.css',
        ), $page->folder($page->dir(), 'assets/route/path.css', 'index.php'));
        $this->assertEquals(array(
            'file' => $page->dir['page'].'assets/index.php',
            'dir' => $page->dir['page'].'assets/',
            'assets' => $page->url['base'].'page/assets/',
            'url' => $page->url['base'].'assets/',
            'folder' => '',
            'route' => '/route/path.css',
        ), $page->folder($page->dir('assets'), 'route/path.css', 'index.php'));
        $this->assertNull($page->folder($page->dir['page'], '', 'PageTest.php'));
    }

    public function testLoadMethod()
    {
        $page = Page::html();
        $this->assertNull($page->load($page->dir['page'].'non-existant.file'));
        $file = $page->file('load-file-test.php');
        $this->assertEmpty($page->load($file));
        $this->assertEquals('<p>A string of <b>HTML</b></p>', $page->load($file, array(
            'echo' => '<p>A string of <b>HTML</b></p>',
        )));
        $this->assertNull($page->load($file, array('null')));
        $this->assertTrue($page->load($file, array('bool' => true)));
        $this->assertFalse($page->load($file, array('bool' => false)));
        $this->assertEquals(0, $page->load($file, array('numeric' => 0)));
        $this->assertEquals(-10.5, $page->load($file, array('numeric' => -10.5)));
        $this->assertEquals(25, $page->load($file, array('numeric' => 5 * 5)));
        $this->assertEquals(array('check' => 'me', 'out'), $page->load($file, array('array' => array('check' => 'me', 'out'))));
        $this->assertInstanceOf('BootPress\Page\Component', $page->load($file, array('class')));
    }

    public static function javascriptFilter(array $js)
    {
        $js[] = 'filter.js';

        return $js;
    }

    public function testFilterAndSendMethods()
    {
        $page = Page::html();
        
        // Test filters
        $page->filter('javascript', __NAMESPACE__.'\PageTest::javascriptFilter', array('this'), 5);
        $this->assertAttributeEquals(array(
            'javascript' => array(
                array('function' => __NAMESPACE__.'\PageTest::javascriptFilter', 'params' => array('this'), 'order' => 5, 'key' => 0),
            ),
        ), 'filters', $page);
        $html = $page->display('<p>Content</p>');
        $this->assertContains('<script src="filter.js"></script>', $html);
        $page->filter('response', function ($page, $response, $type) {
            return $response->setContent($type); // 'html'
        }, array('html', 200));
        $page->filter('response', function ($page, $response) {
            return $response->setContent('json');
        }, array('json'));
        $page->filter('response', function ($page, $response) {
            return $response->setContent(404);
        }, array(404));
        $page->filter('response', function ($page, $response) {
            return $response; // removes 'this' from the default $params we're not including
        });
        
        // Send a 200 (default - not specified) response
        $response = $page->send('content');
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $response);
        $this->assertEquals('html', $response->getContent());
        
        // Send a 404 response
        $response = $page->send(404);
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $response);
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals('404', $response->getContent());
        
        // Send an HTML response
        $response = $page->send('Content', 200, array('Content-Type'=>'text/html'));
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Content', $response->getContent());
    }

    public function testSendJsonMethod()
    {
        $page = Page::html();
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\JsonResponse', $page->sendJson(array('json' => 'data')));
    }

    public function testFilterMethodSectionException()
    {
        $page = Page::html();
        $this->setExpectedException('\LogicException');
        $page->filter('section', function(){}, array('bogus'));
    }

    public function testFilterMethodThisException()
    {
        $page = Page::html();
        $this->setExpectedException('\LogicException');
        $page->filter('javascript', function(){}, array('that', 'other'), 5);
    }

    public function testDisplayMethod()
    {
        $request = Request::create('http://website.com/listings.html', 'GET');
        $page = Page::isolated(array('suffix' => '.html'), $request);
        $this->assertEqualsRegExp(array(
            '<!doctype html>',
            '<html lang="'.$page->language.'">',
            '<head>',
                '<meta charset="'.$page->charset.'">',
                '<title>'.$page->title.'</title>',
            '</head>',
            '<body>',
                '<p>Content</p>',
            '</body>',
            '</html>',
        ), $page->display('<p>Content</p>'));
        $content = <<<'EOT'
	<  !doctype html>
<html   >
<HEad>< title>Broken</tit
	<meta attr="blah" charset =foo NAME = bar />
<META name=  bar sdf content= foo > <meta content="name" name=" author ">
<meta name  = "KEYwords" content  =  " php documentation ">
<meta name=  "DESCRIPTION" content="  a php manual  ">
	</ head>  <body style="color:#333;">
	
	I'm in the body!</body>
< /html>
EOT;
        $this->assertEqualsRegExp(array(
            '<  !doctype html>',
            '<html   >',
            '<HEad>',
                '< title>Broken</tit',
                '<meta attr="blah" charset =foo NAME = bar />',
                '<META name=  bar sdf content= foo >',
                '<meta content="name" name=" author ">',
                '<meta name  = "KEYwords" content  =  " php documentation ">',
                '<meta name=  "DESCRIPTION" content="  a php manual  ">',
            '</head>',
            '<body style="color:#333;">',
                'I\'m in the body!',
            '</body>',
            '</html>',
        ), $page->display($content));
    }
}
