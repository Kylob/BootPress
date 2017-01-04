<?php

namespace BootPress\Tests;

use BootPress\Page\Component as Page;
use BootPress\Analytics\Component as Analytics;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AnalyticsTest extends \BootPress\HTMLUnit\Component
{
    protected static $page;

    public static function setUpBeforeClass()
    {
        self::$page = array('testing' => true, 'dir' => __DIR__.'/page', 'suffix' => '.html');
    }

    public static function tearDownAfterClass()
    {
        $db = self::$page['dir'].'/Analytics.db';
        if (is_file($db)) {
            @unlink($db);
        }
    }

    public function testConstructAndSetupDatabaseTablesMethods()
    {
        $page = $this->page();

        $db = $page->file('Analytics.db');
        if (is_file($db)) {
            unlink($db);
        }
        $this->assertFileNotExists($db);
        Analytics::database();
        $this->assertFileExists($db);
    }

    public function testLogProcessHitsIdAndExecMethods()
    {
        // Robot - Google
        $page = $this->page(array('server' => array(
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
        )));
        $csv = $page->file('analytics.csv');
        if (is_file($csv)) {
            unlink($csv);
        }
        $this->assertFileNotExists($csv);
        $this->assertFalse(Analytics::log());
        $this->assertFileExists($csv);
        $page->send('Content');

        // Mobile - iPhone
        $_SESSION = array();
        session_create_id(); // so it doesn't think we are a repeat visitor
        $page = $this->page(array('server' => array(
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (iPhone; U; ru; CPU iPhone OS 4_2_1 like Mac OS X; ru) AppleWebKit/533.17.9 (KHTML, like Gecko) Version/5.0.2 Mobile/8C148a Safari/6533.18.5',
        )));
        $this->assertFalse(Analytics::log());
        $page->send('Content');

        // Desktop - OS X 10_6_8
        $_SESSION = array();
        session_create_id(); // so it doesn't think we are a repeat visitor
        $page = $this->page(array('server' => array(
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_6_8) AppleWebKit/537.13+ (KHTML, like Gecko) Version/5.1.7 Safari/534.57.2',
            'HTTP_REFERER' => 'http://somewhere-else.com/linked.html?query=string',
        )));
        $this->assertFalse(Analytics::log());
        $page->send('Content');

        // check that everything was logged properly
        $file = file($csv);
        $this->assertCount(6, $file);
        foreach (array('analytics', 'bots', 'analytics', 'bots', 'analytics', 'bots') as $table) {
            $row = array_shift($file);
            $this->assertEquals($table, strstr($row, ',', true));
        }

        // process the first round of hits
        Analytics::process();
        $this->assertFileNotExists($csv);

        // pass in a user_id
        $page->session->set('bootpress', array('id' => 1));

        // Signed in user with Javascript
        $params = array(
            'width' => 1200,
            'height' => 800,
            'hemisphere' => 'N',
            'timezone' => 'UM9',
            'dst' => 1,
            'offset' => 28800,
            'timer' => array(
                'loaded' => 2204,
                'server' => 520,
                'dns' => 0,
                'tcp' => 0,
                'request' => 323,
                'response' => 143,
            ),
        );
        $page = $this->page(array(
            'method' => 'POST',
            'params' => $params,
            'server' => array('HTTP_X-Requested-With' => 'XMLHttpRequest'),
        ));
        $this->assertEquals(array(
            'loaded' => 2204,
            'server' => 520,
            'dns' => 0,
            'tcp' => 0,
            'request' => 323,
            'response' => 143,
            'views' => 0,
        ), Analytics::log());
        $info = $page->session->get('analytics');
        $this->assertArrayHasKey('last', $info);
        $this->assertArrayHasKey('started', $info);
        unset($info['last'], $info['started']);
        $this->assertEquals(array(
            'hits' => 1,
            'session' => $page->session->id(),
            'users' => array(1),
            'offset' => 28800,
            'timezone' => 'UM9',
            'javascript' => true,
            'agent' => array(
                'robot' => null,
                'browser' => 'Safari',
                'version' => '5',
                'mobile' => '',
                'desktop' => 'OS X',
            ),
        ), $info);

        // check that everything was logged properly
        $file = file($csv);
        $this->assertCount(3, $file);
        foreach (array('sessions', 'users', 'hits') as $table) {
            $row = array_shift($file);
            $this->assertEquals($table, strstr($row, ',', true));
        }

        // lookup the user's session id on the next go-around
        Analytics::process();
        Analytics::log();

        // don't log an post request without the right parameters
        $page = $this->page(array(
            'method' => 'POST',
            'params' => array('something' => 'else'),
            'server' => array('HTTP_X-Requested-With' => 'XMLHttpRequest'),
        ));
        $this->assertFalse(Analytics::log());
        $page->send('Content');

        // create new session and log an initial hit
        $_SESSION = array();
        session_create_id();
        $page = $this->page(array('server' => array(
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_6_8) AppleWebKit/537.13+ (KHTML, like Gecko) Version/5.1.7 Safari/534.57.2',
            'HTTP_REFERER' => 'http://somewhere-else.com/linked.html?query=string',
        )));
        $this->assertFalse(Analytics::log());
        $page->send($page->display('Content')); // includes analytics.js at end of <body>

        // check that everything was logged properly
        $file = file($csv);
        $this->assertCount(3, $file);
        foreach (array('hits', 'analytics', 'bots') as $table) {
            $row = array_shift($file);
            $this->assertEquals($table, strstr($row, ',', true));
        }

        // work with the database
        $db = Analytics::database();

        // This value will be updated to it's former glory
        $db->update('analytic_agents', 'id', array(3 => array('robot' => 'kylob')));
        $this->assertEquals('kylob', $db->value('SELECT robot FROM analytic_agents WHERE id = ?', 3));

        // every 3 minutes we update the database using the csv file
        clearstatcache();
        $temp = $page->file('analytics-temp.csv');
        if (is_file($temp)) {
            unlink($temp);
        }
        rename($csv, $temp);
        touch($csv, (time() - 200)); // an empty file created more than 3 minutes ago
        touch($temp, (time() - 180)); // "started to processHits()" 3 minutes ago
        $this->assertFalse(Analytics::process()); // we are already on it
        clearstatcache();
        touch($temp, (time() - 400)); // a failed attempt that happened more than 6 minutes ago
        $page = $this->page(array(
            'method' => 'POST',
            'params' => $params,
            'server' => array('HTTP_X-Requested-With' => 'XMLHttpRequest'),
        ));
        $this->assertEquals(array(
            'loaded' => 2204,
            'server' => 520,
            'dns' => 0,
            'tcp' => 0,
            'request' => 323,
            'response' => 143,
            'views' => 3,
        ), Analytics::log());
        $this->assertFileNotExists($temp);
        $this->assertFileNotExists($csv);
        $this->assertFalse(Analytics::process()); // there is no $csv file

        // check database entries
        $this->assertEquals('', $db->value('SELECT robot FROM analytic_agents WHERE id = ?', 3));
        $this->assertEquals('', $db->value('SELECT path FROM analytic_paths WHERE id = ?', 1));
        $this->assertEquals(4, $db->value('SELECT COUNT(*) FROM analytics'));
        $this->assertEquals(3, $db->value('SELECT COUNT(*) FROM analytic_agents'));
        $this->assertEquals(2, $db->value('SELECT COUNT(*) FROM analytic_bots'));
        $this->assertEquals(3, $db->value('SELECT COUNT(*) FROM analytic_hits'));
        $this->assertEquals(array(
            array('session_id' => 1, 'user_id' => 1),
        ), $db->all('SELECT * FROM analytic_users WHERE user_id = ?', 1, 'assoc'));
    }

    public function testPostAndJsonMethods()
    {
        Analytics::post('#id', '<p>Content</p>');
        Analytics::post('javascript', 'alert("hello");');
        Analytics::post('css', array(
            'body {',
            '    color: #333;',
            '}',
        ));
        $this->assertEquals(array(
            'css' => "\n\t".'body {'."\n\t    ".'color: #333;'."\n\t".'}',
            '#id' => "\n\t".'<p>Content</p>',
            'javascript' => "\n\t".'alert("hello");',
        ), Analytics::data());
    }

    public function testProcessHitsMonthlyAndDailyPurge()
    {
        $db = Analytics::database();
        $this->assertEquals(2, $db->value('SELECT COUNT(*) FROM analytic_sessions'));
        $this->assertEquals(4, $db->value('SELECT COUNT(*) FROM analytics'));
        $db->exec("UPDATE analytics SET started = strftime('%s', 'now', '-24 hours')");
        touch(Page::html()->dir['page'].'analytics.csv'); // create the file as it no longer exists
        Analytics::process();
        $this->assertEquals(2, $db->value('SELECT COUNT(*) FROM analytic_sessions'));
        $this->assertEquals(0, $db->value('SELECT COUNT(*) FROM analytics'));
    }

    public function testLocationMethod()
    {
        $this->assertNull(Analytics::location('UPS'));
        $this->assertEquals('Line Islands', Analytics::location('UP14'));
        $this->assertEquals('Alaska', Analytics::location('UM9', 'N'));
        $this->assertEquals('Hawaii, Cook Islands', Analytics::location('UM10'));
    }

    private function page(array $request = array())
    {
        $request = array_merge(array(
            'uri' => 'http://website.com/?log=hits',
            'method' => 'GET',
            'params' => array(),
            'cookies' => array(),
            'files' => array(),
            'server' => array(),
            'content' => array(),
        ), $request);
        extract($request);

        return Page::html(self::$page, Request::create($uri, $method, $params, $cookies, $files, $server, $content), 'overthrow');
    }
}
