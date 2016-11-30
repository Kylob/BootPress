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
            unlink($db);
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
        $analytics = new Analytics();
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
        $analytics = new Analytics();
        $this->assertFalse($analytics->log());
        $this->assertFileExists($csv);

        // Mobile - iPhone
        $page->session->invalidate(); // so it doesn't think we are a repeat visitor
        $page = $this->page(array('server' => array(
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (iPhone; U; ru; CPU iPhone OS 4_2_1 like Mac OS X; ru) AppleWebKit/533.17.9 (KHTML, like Gecko) Version/5.0.2 Mobile/8C148a Safari/6533.18.5',
        )));
        $this->assertFalse($analytics->log(null));

        // 404
        $page->send(404);

        // Desktop - OS X 10_6_8
        $page->session->invalidate(); // so it doesn't think we are a repeat visitor
        $page = $this->page(array('server' => array(
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_6_8) AppleWebKit/537.13+ (KHTML, like Gecko) Version/5.1.7 Safari/534.57.2',
            'HTTP_REFERER' => 'http://somewhere-else.com/linked.html?query=string',
        )));
        $this->assertFalse($analytics->log());

        // check that everything was logged properly
        $file = file($csv);
        $this->assertCount(7, $file);
        foreach (array('sessions', 'hits', 'sessions', 'hits', '404', 'sessions', 'hits') as $table) {
            $row = array_shift($file);
            $this->assertEquals($table, strstr($row, ',', true));
        }

        // process the first round of hits
        $analytics->processHits();
        $this->assertFileNotExists($csv);

        // pass in a user_id
        $analytics = new Analytics(1);

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
        ), $analytics->log());
        $info = $page->session->get('analytics');
        unset($info['tracker']);
        $this->assertEquals(array(
            'users' => array(1),
            'offset' => 28800,
            'timezone' => 'UM9',
            'javascript' => true,
            'agent' => array(
                'robot' => '',
                'browser' => 'Safari',
                'version' => '5.1.7',
                'mobile' => '',
                'desktop' => 'OS X 10_6_8',
            ),
        ), $info);
        
        // don't log an post request without the right parameters
        $page = $this->page(array(
            'method' => 'POST',
            'params' => array('something'=>'else'),
            'server' => array('HTTP_X-Requested-With' => 'XMLHttpRequest'),
        ));
        $this->assertFalse($analytics->log());

        // create new session and log an initial hit
        $page->session->invalidate();
        $page = $this->page(array('server' => array(
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_6_8) AppleWebKit/537.13+ (KHTML, like Gecko) Version/5.1.7 Safari/534.57.2',
            'HTTP_REFERER' => 'http://somewhere-else.com/linked.html?query=string',
        )));
        $this->assertFalse($analytics->log());

        // check that everything was logged properly
        $file = file($csv);
        $this->assertCount(6, $file);
        foreach (array('analytics', 'users', 'server', 'hits', 'sessions', 'hits') as $table) {
            $row = array_shift($file);
            $this->assertEquals($table, strstr($row, ',', true));
        }

        // These values will be updated to their former glory
        $analytics->db->update('analytic_agents', 'id', array(3 => array('robot' => 'kylob')));
        $analytics->db->update('analytic_paths', 'id', array(1 => array('format' => 'pdf')));
        $this->assertEquals('kylob', $analytics->db->value('SELECT robot FROM analytic_agents WHERE id = ?', 3));
        $this->assertEquals('pdf', $analytics->db->value('SELECT format FROM analytic_paths WHERE id = ?', 1));

        // every 3 minutes we update the database using the csv file
        clearstatcache();
        $temp = $page->file('analytics-temp.csv');
        if (is_file($temp)) {
            unlink($temp);
        }
        rename($csv, $temp);
        touch($csv, (time() - 200)); // an empty file created more than 3 minutes ago
        touch($temp, (time() - 180)); // "started to processHits()" 3 minutes ago
        $this->assertFalse($analytics->processHits()); // we are already on it
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
            'views' => 0,
        ), $analytics->log());
        $this->assertFileNotExists($temp);
        $this->assertFileNotExists($csv);
        $this->assertFalse($analytics->processHits()); // there is no $csv file

        // check database entries
        $this->assertEquals('', $analytics->db->value('SELECT robot FROM analytic_agents WHERE id = ?', 3));
        $this->assertEquals('html', $analytics->db->value('SELECT format FROM analytic_paths WHERE id = ?', 1));
        $this->assertEquals(4, $analytics->db->value('SELECT COUNT(*) FROM analytics'));
        $this->assertEquals(3, $analytics->db->value('SELECT COUNT(*) FROM analytic_agents'));
        $this->assertEquals(5, $analytics->db->value('SELECT COUNT(*) FROM analytic_hits'));
        $this->assertEquals(1, $analytics->db->value('SELECT COUNT(*) FROM analytic_not_found'));
        $this->assertEquals(array(
            array('id' => 1, 'path' => '', 'format' => 'html'),
        ), $analytics->db->all('SELECT * FROM analytic_paths', '', 'assoc'));
        $this->assertEquals(2, $analytics->db->value('SELECT COUNT(*) FROM analytic_server'));
        $this->assertEquals(array(
            array('analytic_id' => 3, 'user_id' => 1),
            array('analytic_id' => 4, 'user_id' => 1),
        ), $analytics->db->all('SELECT * FROM analytic_users WHERE user_id = ?', 1, 'assoc'));
    }

    public function testPostAndJsonMethods()
    {
        $analytics = new Analytics();
        $analytics->post('#id', '<p>Content</p>');
        $analytics->post('javascript', 'alert("hello");');
        $analytics->post('css', array(
            'body {',
            '    color: #333;',
            '}',
        ));
        $json = $analytics->json();
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $json);
        $this->assertEquals(array(
            'css' => "\n\t".'body {'."\n\t    ".'color: #333;'."\n\t".'}',
            '#id' => "\n\t".'<p>Content</p>',
            'javascript' => "\n\t".'alert("hello");',
        ), json_decode($json->getContent(), true));
    }

    public function testCountMethods()
    {
        // no particular user to exclude
        $analytics = new Analytics();
        $this->assertEquals(3, $analytics->pageViews(''));
        $this->assertEquals(array(2, 3), $analytics->userHits());
        $this->assertEquals(array(2, 2), $analytics->robotHits());
        $this->assertEquals(array('-', '-'), $analytics->userHits(time() - 86400, time() - 43200, '-'));
        $this->assertEquals(array('-', '-'), $analytics->robotHits(time() - 86400, time() - 43200, '-'));
        $this->assertEquals('2.20', $analytics->avgLoadTimes());
        $analytics->db->exec('UPDATE analytics SET duration = 1000');
        $this->assertEquals('0.02', $analytics->avgSessionDuration());

        // excluding a user from stats
        $analytics = new Analytics(1);
        $this->assertEquals(0, $analytics->pageViews(''));
        $this->assertEquals(array(0, 0), $analytics->userHits());
        $this->assertEquals(0, $analytics->avgLoadTimes());
        $this->assertEquals(0, $analytics->avgSessionDuration());
    }

    public function testProcessHitsMonthlyAndDailyPurge()
    {
        $analytics = new Analytics();
        $this->assertEquals(4, $analytics->db->value('SELECT COUNT(*) FROM analytic_sessions'));
        $this->assertEquals(4, $analytics->db->value('SELECT COUNT(*) FROM analytics'));
        $analytics->db->exec("UPDATE analytics SET started = strftime('%s', 'now', '-31 days')");
        touch(Page::html()->dir['page'].'analytics.csv'); // create the file as it no longer exists
        $analytics->processHits();
        $this->assertEquals(2, $analytics->db->value('SELECT COUNT(*) FROM analytics'));
        $this->assertEquals(0, $analytics->db->value('SELECT COUNT(*) FROM analytic_sessions'));
    }

    public function testStartStopMethod()
    {
        $analytics = new Analytics();
        $this->assertCount(24, $analytics->startStop(24, 'hour', 'g:00a', array('This Hour', 'Last Hour')));
        $this->assertCount(7, $analytics->startStop(7, 'day', 'l', array('Today', 'Yesterday')));
        $this->assertGreaterThan(3, count($analytics->startStop(5, 'week', '', array('This Week', 'Last Week', '2 weeks ago', '3 weeks ago', '4 weeks ago'))));
        $this->assertLessThan(3, count($analytics->startStop(12, 'month', 'M Y', array('This Month', 'Last Month'))));
        $this->assertLessThan(3, count($analytics->startStop(10, 'year', 'Y')));
    }

    public function testTimeRangeMethod()
    {
        Page::html()->session->invalidate(); // so that $analytics->offset has no effect
        $analytics = new Analytics();
        $time = mktime(17, 30, 45, 9, 23, 2017);
        // $time = strtotime('Sep 23, 2017 17:30:45');
        $this->assertEquals(array(
            1506182400,
            1506185999,
            '5:00pm',
            '2017-09-23 17:00:00',
            '2017-09-23 17:59:59',
        ), $analytics->timeRange($time, 'hour', 'g:00a'));
        $this->assertEquals(array(
            1506121200,
            1506207599,
            'Saturday',
            '2017-09-23 00:00:00',
            '2017-09-23 23:59:59',
        ), $analytics->timeRange($time, 'day', 'l'));
        $this->assertEquals(array(
            1505689200,
            1506293999,
            '',
            '2017-09-18 00:00:00',
            '2017-09-24 23:59:59',
        ), $analytics->timeRange($time, 'week'));
        $this->assertEquals(array(
            1504220400,
            1506812399,
            'Sep 2017',
            '2017-09-01 00:00:00',
            '2017-09-30 23:59:59',
        ), $analytics->timeRange($time, 'month', 'M Y'));
        $this->assertEquals(array(
            1483228800,
            1514764799,
            2017,
            '2017-01-01 00:00:00',
            '2017-12-31 23:59:59',
        ), $analytics->timeRange($time, 'year', 'Y'));
        $this->setExpectedException('\LogicException');
        $analytics->timeRange($time, 'decade');
    }

    public function testLocationMethod()
    {
        $analytics = new Analytics();
        $this->assertNull($analytics->location('UPS'));
        $this->assertEquals('Line Islands', $analytics->location('UP14'));
        $this->assertEquals('Alaska', $analytics->location('UM9', 'N'));
        $this->assertEquals('Hawaii, Cook Islands', $analytics->location('UM10'));
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
