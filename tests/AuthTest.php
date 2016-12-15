<?php

namespace BootPress\Tests;

use BootPress\Page\Component as Page;
use BootPress\Auth\Component as Auth;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Yaml\Yaml;

class AuthTest extends \PHPUnit_Framework_TestCase
{
    protected static $page;

    public static function setUpBeforeClass()
    {
        self::$page = array('dir' => __DIR__.'/page', 'suffix' => '.html', 'testing' => true);
        $page = Page::html(self::$page, Request::create('http://website.com/', 'GET'), 'overthrow');
        $db = $page->file('Users.db');
        if (is_file($db)) {
            unlink($db);
        }
    }

    public static function tearDownAfterClass()
    {
        $dir = __DIR__.'/page/';
        foreach (array(
            $dir.'databases.yml',
            $dir.'Sitemap.db',
            $dir.'Users.db',
            $dir.'users.yml',
        ) as $target) {
            if (is_file($target)) {
                @unlink($target);
            }
        }
    }

    public function testRegisterAndInfoMethods()
    {
        $auth = new Auth();
        $this->assertEquals(array(true, 1), $auth->register('Joe Bloggs', 'joe@bloggs.com', 'supersekrit'));
        $this->assertEquals(array(false, 1), $auth->register('Joe Bloggs', 'joe@bloggs.com', 'supersekrit'));
        $info = $auth->info(1);
        $time = $info['registered'];
        $this->assertGreaterThan(time() - 100, $time);
        $this->assertEquals(array(
            'id' => 1,
            'name' => 'Joe Bloggs',
            'email' => 'joe@bloggs.com',
            'admin' => 0,
            'approved' => 'Y',
            'registered' => $time,
            'last_activity' => 0,
        ), $info);
    }

    public function testCheckMethod()
    {
        $auth = new Auth();
        $this->assertEquals(1, $auth->check('joe@bloggs.com'));
        $this->assertEquals(1, $auth->check('joe@bloggs.com', 'supersekrit'));
        $this->assertFalse($auth->check('joe@bloggs.com', 'sekretive'));

        // verify that password updates accordingly
        $auth = new Auth(array(
            'password' => array('options' => array('cost' => 5)),
        ));
        $this->assertEquals(1, $auth->check('joe@bloggs.com', 'supersekrit'));
        $this->assertFalse($auth->check('joe@bloggs.com', 'sekretive'));
    }

    public function testLoginAndLogoutMethods()
    {
        $page = Page::html();
        $auth = new Auth();
        $auth->login($auth->check('joe@bloggs.com', 'supersekrit'));
        $session = $page->session->get('bootpress');
        $this->assertNotEmpty($session);
        $this->assertEquals(1, $session['verified']);
        $this->assertEquals(1, $auth->isUser());
        $this->assertEquals(1, $auth->isUser(1));
        $this->assertFalse($auth->isUser(2));
        $this->assertEquals(1, $auth->isVerified());
        $this->assertFalse($auth->isAdmin());
        $session['verified'] = null;
        $page->session->set('bootpress', $session);
        $this->assertFalse($auth->isVerified());
        $auth->logout();
        $this->assertNull($page->session->get('bootpress'));
        $this->assertFalse($auth->isUser());
    }

    public function testConstructorLoggingInAndOut()
    {
        // Logout user of all sessions when we have a session and cookie mismatch
        $page = Page::html();
        $auth = new Auth();
        $auth->login(1);
        $session = $page->session->get('bootpress');
        $this->assertNotEmpty($session);
        list($id, $series, $token) = explode(' ', $session['cookie']);
        $this->assertEquals(1, $auth->db->value('SELECT user_id FROM user_sessions WHERE id = ?', $id));
        $this->assertEquals(sha1($series), $auth->db->value('SELECT series FROM user_sessions WHERE id = ?', $id));
        $this->assertEquals(sha1($token), $auth->db->value('SELECT token FROM user_sessions WHERE id = ?', $id));
        $this->assertNull($page->request->cookies->get('bootpress'));
        $this->assertEquals(1, $auth->isUser());
        $auth = new Auth(); // this will logout Joe from all of his sessions because of a session / cookie mismatch
        $this->assertFalse($auth->isUser());

        // Unset the cookie when it doesn't match a record in the database and only log them out of the current session
        $auth->login(1);
        $session = $page->session->get('bootpress');
        list($id, $series, $token) = explode(' ', $session['cookie']);
        $cookie = base64_encode(implode(' ', array($id, sha1($series), $token))); // a database (series) mismatch
        $page = Page::html(self::$page, Request::create(// set the cookie
            'http://website.com/', // uri
            'GET', // method
            array(), // parameters
            array('bootpress' => $cookie) // cookies
        ), 'overthrow');
        $this->assertEquals($cookie, $page->request->cookies->get('bootpress')); // make sure cookie is set
        $page->session->remove('bootpress'); // remove session so that we don't bypass our test
        $auth = new Auth(); // unsets the cookie and logs user out of current session
        $this->assertFalse($auth->isUser());

        // Logout user of all sessions when there is no session to compare to, and there is a token mismatch
        $auth->login(1);
        $session = $page->session->get('bootpress');
        list($id, $series, $token) = explode(' ', $session['cookie']);
        $cookie = base64_encode(implode(' ', array($id, $series, sha1($token)))); // a token mismatch
        $page = Page::html(self::$page, Request::create(// set the cookie
            'http://website.com/', // uri
            'GET', // method
            array(), // parameters
            array('bootpress' => $cookie) // cookies
        ), 'overthrow');
        $page->session->remove('bootpress'); // remove session so that we don't bypass our test
        $auth = new Auth(); // unsets the cookie and logs user out of all their sessions
        $this->assertFalse($auth->isUser());

        // Update user_sessions and token when user has been active for more than 5 minutes
        $auth->login(1);
        $session = $page->session->get('bootpress');
        list($id, $series, $token) = explode(' ', $session['cookie']);
        $cookie = base64_encode(implode(' ', array($id, $series, $token))); // everything matches
        $page = Page::html(self::$page, Request::create(
            'http://website.com/', // uri
            'GET', // method
            array(), // parameters
            array('bootpress' => $cookie) // cookies
        ), 'overthrow');
        $this->assertEquals(base64_encode($session['cookie']), $page->request->cookies->get('bootpress')); // session and cookie match
        $time = time();
        $auth->db->update('user_sessions', 'id', array($id => array('last_activity' => ($time - 310)))); // come back 6 minutes later
        $this->assertEquals(1, $auth->isUser());
        $auth = new Auth();
        $this->assertEquals(1, $auth->isUser());
        $this->assertGreaterThan($time - 10, $auth->db->value('SELECT last_activity FROM user_sessions WHERE id = ?', $id));
        $session = $page->session->get('bootpress');
        list($new_id, $new_series, $new_token) = explode(' ', $session['cookie']);
        $this->assertEquals($id, $new_id);
        $this->assertEquals($series, $new_series);
        $this->assertNotEquals($token, $new_token);
    }

    public function testUpdateAndUserMethods()
    {
        $auth = new Auth();
        $this->assertNull($auth->login(false));
        $this->assertEquals(array(), $auth->user());
        $auth->login(1);
        /*
        $this->assertEquals(array(
            'id' => 1,
            'name' => 'Joe Bloggs',
            'email' => 'joe@bloggs.com',
            'admin' => 0,
        ), $auth->user());
        */
        $this->assertEquals('Joe Bloggs', $auth->user('name'));
        $this->assertEquals('joe@bloggs.com', $auth->user('email'));
        $this->assertEquals(1, $auth->check('joe@bloggs.com', 'supersekrit'));
        $this->assertEquals(0, $auth->user('admin'));

        // test unapproved (not) working ie. they can't sign in
        $auth->update(1, array('approved' => 'N'));
        $this->assertEquals(array(), $auth->user()); // they are automaticall logged out if unapproved
        $auth->login(1);
        $this->assertFalse($auth->isUser());
        $auth->update(1, array('approved' => 'Y'));
        $auth->login(1);
        $this->assertEquals(1, $auth->isUser());

        $time = time();
        $auth->update(1, array(
            'name' => 'Joe Blogger',
            'email' => 'joe@blogger.com',
            'password' => 'sekretive',
            'admin' => 2,
            'approved' => true,
            'registered' => $time,
            'last_activity' => $time,
        ));
        $this->assertEquals(array(
            'id' => 1,
            'name' => 'Joe Blogger',
            'email' => 'joe@blogger.com',
            'admin' => 2,
            'approved' => 'Y',
            'registered' => $time,
            'last_activity' => $time,
        ), $auth->info(1));
        $this->assertEquals('Joe Blogger', $auth->user('name'));
        $this->assertEquals('joe@blogger.com', $auth->user('email'));
        $this->assertEquals(1, $auth->check('joe@blogger.com', 'sekretive'));
        $this->assertEquals(2, $auth->user('admin'));
        $this->assertEquals(2, $auth->isAdmin(3));
        $this->assertEquals(2, $auth->isAdmin(2));
        $this->assertFalse($auth->isAdmin(1));
        $auth->update(1, array('name' => 'Joe Bloggs', 'occupation' => 'blogger'));
        $this->assertNotEmpty($auth->db->log('errors')); // no occupation field
    }

    public function testRandomPasswordMethod()
    {
        $auth = new Auth();
        $this->assertEquals(6, strlen($auth->randomPassword(6)));
        $this->assertRegExp('/^[a-z0-9]{10}$/i', $auth->randomPassword(10));
    }

    public function testHttpBasicAuthentication()
    {
        // Use the database and authenticate via $_SERVER['HTTP_AUTHORIZATION']
        $page = Page::html(self::$page, Request::create(
            'http://website.com/',
            'GET', // method
            array(), // parameters
            array(), // cookies
            array(), // files
            array(
                'HTTP_AUTHORIZATION' => 'Basic '.base64_encode('joe@blogger.com:sekretive'),
            ) // server
        ), 'overthrow');
        $this->assertEquals('joe@blogger.com', $page->request->getUser());
        $this->assertEquals('sekretive', $page->request->getPassword());
        $auth = new Auth();
        $this->assertNull($auth->http());
        $this->assertEquals(1, $auth->user('id'));
        $this->assertEquals('Joe Blogger', $auth->user('name'));
        $this->assertEquals('joe@blogger.com', $auth->user('email'));
        $this->assertEquals(2, $auth->user('admin'));
        $this->assertEquals(0, $auth->user('login'));
        $this->assertEquals(1, $auth->isUser());
        $this->assertEquals(2, $auth->isAdmin(5));

        // Use an array and authenticate via $_SERVER['PHP_AUTH_USER'] and $_SERVER['PHP_AUTH_PW']
        $page = Page::html(self::$page, Request::create(
            'http://website.com/',
            'GET', // method
            array(), // parameters
            array(), // cookies
            array(), // files
            array(
                'PHP_AUTH_USER' => 'Joe Bloggs',
                'PHP_AUTH_PW' => 'supersekrit',
            ) // server
        ), 'overthrow');
        $this->assertEquals('Joe Bloggs', $page->request->getUser());
        $this->assertEquals('supersekrit', $page->request->getPassword());
        $auth = new Auth(array(
            'basic' => array('Joe Bloggs' => 'supersekrit'),
        ));
        $this->assertEquals('Joe Bloggs', $auth->http());
        $this->assertNull($auth->user('id'));
        $this->assertNull($auth->user('name'));
        $this->assertNull($auth->user('email'));
        $this->assertNull($auth->user('admin'));
        $this->assertNull($auth->user('login'));
        $this->assertFalse($auth->isUser());
        $this->assertEquals(1, $auth->isAdmin(5));

        // Use a YAML file and authenticate via url
        $page = Page::html(self::$page, Request::create(
            'http://Joe:Bloggs@website.com/'
        ), 'overthrow');
        $this->assertEquals('Joe', $page->request->getUser());
        $this->assertEquals('Bloggs', $page->request->getPassword());

        // We start out with a human readable password
        $file = self::$page['dir'].'/users.yml';
        file_put_contents($file, Yaml::dump(array('Joe' => 'Bloggs')));
        $this->assertEquals(array(
            'Joe' => 'Bloggs',
        ), Yaml::parse(file_get_contents($file)));

        // Unencrypted passwords are encrypted the first time we open the file
        $auth = new Auth(array(
            'basic' => $file,
        ));
        $first = Yaml::parse(file_get_contents($file));
        $this->assertStringStartsWith('$2y$', $first['Joe']);

        // Encrypted passwords stay the same
        $auth = new Auth(array(
            'basic' => $file,
        ));
        $second = Yaml::parse(file_get_contents($file));
        $this->assertEquals($first['Joe'], $second['Joe']);

        // Password updated when encryption settings change
        $auth = new Auth(array(
            'basic' => $file,
            'password' => array('options' => array('cost' => 5)),
        ));
        $third = Yaml::parse(file_get_contents($file));
        $this->assertNotEquals($second['Joe'], $third['Joe']);
        $this->assertStringStartsWith('$2y$', $third['Joe']);
        $this->assertEquals('Joe', $auth->http());
        $this->assertNull($auth->user('id'));
        $this->assertNull($auth->user('name'));
        $this->assertNull($auth->user('email'));
        $this->assertNull($auth->user('admin'));
        $this->assertNull($auth->user('login'));
        $this->assertFalse($auth->isUser());
        $this->assertEquals(1, $auth->isAdmin(5));

        // Call realm() for 100% coverage
        $auth->realm('Website');
    }
}
