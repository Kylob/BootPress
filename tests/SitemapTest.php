<?php

namespace BootPress\Tests;

use BootPress\Page\Component as Page;
use BootPress\Asset\Component as Asset;
use BootPress\Sitemap\Component as Sitemap;
use Symfony\Component\HttpFoundation\Request;

class SitemapTest extends \BootPress\HTMLUnit\Component
{
    protected static $page;

    public static function setUpBeforeClass()
    {
        self::$page = array('testing' => true, 'dir' => __DIR__.'/page', 'suffix' => '.html');
    }
    
    public static function tearDownAfterClass()
    {
        $db = self::$page['dir'].'/Sitemap.db';
        if (is_file($db)) {
            unlink($db);
        }
    }

    public function testConstructorAndDestructor()
    {
        $page = Page::html(self::$page, Request::create('http://website.com/sitemap.xml', 'GET'), 'overthrow');
        $db = $page->file('Sitemap.db');
        if (is_file($db)) {
            unlink($db);
        }
        $sitemap = new Sitemap();
        $this->assertFileExists($db);
        unset($sitemap);
        unlink($db);
        $this->assertFileNotExists($db);
    }

    public function testPageAndAddMethods()
    {
        // try to insert non-html page
        $page = Page::html(self::$page, Request::create('http://website.com/sitemap.xml', 'GET'), 'overthrow');
        $this->assertNull(Sitemap::add('xml', '<xml>'));
        
        // add a page to the sitemap
        $page = Page::html(self::$page, Request::create('http://website.com/nosey-pepper.html', 'GET'), 'overthrow');
        $page->title = 'What does a nosey pepper do?';
        $html = '<p>Gets jalapeno business!</p>';
        Sitemap::add('jokes', $html);
        $page->send(Asset::dispatch('html', $html));

        // get sitemap index page
        $page = Page::html(self::$page, Request::create('http://website.com/sitemap.xml', 'GET'), 'overthrow');
        $xml = Sitemap::page();
        $this->assertInstanceof('\Symfony\Component\HttpFoundation\Response', $xml);
        $this->assertEqualsRegExp(array(
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">',
                '<sitemap>',
                    '<loc>http://website.com/sitemap-jokes.xml</loc>',
                    '<lastmod>'.date('Y-m-d').'</lastmod>',
                '</sitemap>',
            '</sitemapindex>',
        ), $xml->getContent());

        // get sitemap category page
        $page = Page::html(self::$page, Request::create('http://website.com/sitemap-jokes.xml', 'GET'), 'overthrow');
        $xml = Sitemap::page();
        $this->assertInstanceof('\Symfony\Component\HttpFoundation\Response', $xml);
        $this->assertEqualsRegExp(array(
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">',
                '<url>',
                    '<loc>http://website.com/nosey-pepper.html</loc>',
                    '<lastmod>'.date('Y-m-d').'</lastmod>',
                '</url>',
            '</urlset>',
        ), $xml->getContent());

        // automatically remove page from sitemap
        $page = Page::html(self::$page, Request::create('http://website.com/nosey-pepper.html', 'GET'), 'overthrow');
        $xml = Sitemap::page();
        $page->send(404);

        // ensure 404 for sitemap category page with no links
        $page = Page::html(self::$page, Request::create('http://website.com/sitemap-jokes.xml', 'GET'), 'overthrow');
        $xml = Sitemap::page();
        $this->assertInstanceof('\Symfony\Component\HttpFoundation\Response', $xml);
        $this->assertEquals(404, $xml->getStatusCode());

        // ensure 404 for sitemap index page with no categories
        $page = Page::html(self::$page, Request::create('http://website.com/sitemap.xml', 'GET'), 'overthrow');
        $xml = Sitemap::page();
        $this->assertInstanceof('\Symfony\Component\HttpFoundation\Response', $xml);
        $this->assertEquals(404, $xml->getStatusCode());
    }

    public function testUpsertMethod()
    {
        //upsert some records
        $sitemap = new Sitemap();
        $sitemap->upsert('jokes', array(
            'path' => 'psychic',
            'title' => 'What do you call a fat psychic?',
            'content' => 'A four chin teller.',
            'updated' => strtotime('-1 day'),
        ));
        $sitemap->upsert('jokes', array(
            'path' => 'floats',
            'title' => 'What do you call a computer <u>floating</u><p>in</p> the ocean?',
            'content' => 'A Dell Rolling in the Deep.',
            'updated' => strtotime('-2 day'),
            'additional' => 'info',
        ));
        $sitemap->upsert('jokes', array(
            'path' => 'drowning',
            'title' => 'How do you drown a Hipster?',
            'content' => 'In the mainstream.',
            'updated' => strtotime('-3 day'),
        ));
        $sitemap->upsert('blog/advice', array(
            'path' => 'meat',
            'content' => 'Red meat is not bad for you.  Fuzzy green meat is bad for you.',
            'updated' => strtotime('-1 week'),
        ));
        $sitemap->upsert('blog/advice', array(
            'path' => 'cheese',
            'content' => 'The early bird gets the worm, but the second mouse gets the cheese.',
            'updated' => strtotime('-2 week'),
        ));
        $sitemap->upsert('blog/advice', array(
            'path' => 'success',
            'content' => 'If at first you don\'t succeed, then redefine success.',
            'updated' => strtotime('-3 week'),
        ));
        $sitemap->upsert('orphan', array(
            'path' => 'orphan',
            'content' => 'The Pirates of Penzance!  I have often heard of them.',
        ));
        $sitemap->delete('orphan'); // to make sure this cateogry does not end up in our sitemap
        unset($sitemap);
    }

    public function testUpdateFunctionality()
    {
        $sitemap = new Sitemap();
        $this->assertEquals(date('Y-m-d', strtotime('-3 week')), date('Y-m-d', $sitemap->db->value('SELECT updated FROM sitemap WHERE path = ?', 'success')));

        // make sure nothing happens on resubmit
        $sitemap->upsert('blog/advice', array(
            'path' => 'success',
            'content' => 'If at first you don\'t succeed, then redefine success.',
        ));
        $this->assertEquals(date('Y-m-d', strtotime('-3 week')), date('Y-m-d', $sitemap->db->value('SELECT updated FROM sitemap WHERE path = ?', 'success')));

        // make sure update takes and $updated = now
        $sitemap->upsert('blog/sage-advice', array(
            'path' => 'success',
            'content' => 'If at first you don\'t succeed, destroy all evidence that you even tried.',
        ));
        $updated = $sitemap->db->value('SELECT updated FROM sitemap WHERE path = ?', 'success');
        $this->assertEquals(date('Y-m-d'), date('Y-m-d', $updated));
        $this->assertEquals('blog/sage-advice', $sitemap->db->value('SELECT category FROM sitemap AS m INNER JOIN categories AS c ON m.category_id = c.id WHERE path = ?', 'success'));

        // only updating 'updated' changes nothing
        $sitemap->upsert('blog/sage-advice', array(
            'path' => 'success',
            'content' => 'If at first you don\'t succeed, destroy all evidence that you even tried.',
            'updated' => strtotime('-3 week'),
        ));
        
        $this->assertEquals($updated, $sitemap->db->value('SELECT updated FROM sitemap WHERE path = ?', 'success'));

        // you have to change anything else as well
        $sitemap->upsert('blog/advice', array(
            'path' => 'success',
            'content' => 'If at first you don\'t succeed, destroy all evidence that you even tried.',
            'updated' => strtotime('-3 week'),
        ));

        $this->assertEquals(date('Y-m-d', strtotime('-3 week')), date('Y-m-d', $sitemap->db->value('SELECT updated FROM sitemap WHERE path = ?', 'success')));
        unset($sitemap);
    }

    public function testVerifyUpdatedSitemaps()
    {
        // make sure they are accounted for in our sitemap index page
        $page = Page::html(self::$page, Request::create('http://website.com/sitemap.xml', 'GET'), 'overthrow');
        $this->assertEqualsRegExp(array(
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">',
                '<sitemap>',
                    '<loc>http://website.com/sitemap-blog.xml</loc>',
                    '<lastmod>'.date('Y-m-d', strtotime('-1 week')).'</lastmod>',
                '</sitemap>',
                '<sitemap>',
                    '<loc>http://website.com/sitemap-jokes.xml</loc>',
                    '<lastmod>'.date('Y-m-d', strtotime('-1 day')).'</lastmod>',
                '</sitemap>',
            '</sitemapindex>',
        ), Sitemap::page()->getContent());

        // check for blog links in the sitemap category page
        $page = Page::html(self::$page, Request::create('http://website.com/sitemap-blog.xml', 'GET'), 'overthrow');
        $this->assertEqualsRegExp(array(
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">',
                '<url>',
                    '<loc>http://website.com/cheese.html</loc>',
                    '<lastmod>'.date('Y-m-d', strtotime('-2 week')).'</lastmod>',
                '</url>',
                '<url>',
                    '<loc>http://website.com/meat.html</loc>',
                    '<lastmod>'.date('Y-m-d', strtotime('-1 week')).'</lastmod>',
                '</url>',
                '<url>',
                    '<loc>http://website.com/success.html</loc>',
                    '<lastmod>'.date('Y-m-d', strtotime('-3 week')).'</lastmod>',
                '</url>',
            '</urlset>',
        ), Sitemap::page()->getContent());

        // check for jokes links in the sitemap category page
        $page = Page::html(self::$page, Request::create('http://website.com/sitemap-jokes.xml', 'GET'), 'overthrow');
        $this->assertEqualsRegExp(array(
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">',
                '<url>',
                    '<loc>http://website.com/drowning.html</loc>',
                    '<lastmod>'.date('Y-m-d', strtotime('-3 day')).'</lastmod>',
                '</url>',
                '<url>',
                    '<loc>http://website.com/floats.html</loc>',
                    '<lastmod>'.date('Y-m-d', strtotime('-2 day')).'</lastmod>',
                '</url>',
                '<url>',
                    '<loc>http://website.com/psychic.html</loc>',
                    '<lastmod>'.date('Y-m-d', strtotime('-1 day')).'</lastmod>',
                '</url>',
            '</urlset>',
        ), Sitemap::page()->getContent());
    }

    public function testSearchCapabilities()
    {
        $sitemap = new Sitemap();
        $this->assertEquals(1, $sitemap->count('float'));
        $this->assertEquals(0, $sitemap->count('float', 'blog'));
        $this->assertEquals(1, $sitemap->count('float', 'jokes'));
        $search = $sitemap->search('float')[0];
        $this->assertEquals('jokes', $search['category']);
        $this->assertEquals('What do you call a computer <u>floating</u><p>in</p> the ocean?', $search['title']);
        $this->assertEquals('A Dell Rolling in the Deep.', $search['content']);
        $this->assertArrayHasKey('additional', $search);
        $this->assertEquals($search['words'], $sitemap->words('float', $search['docid']));
        unset($sitemap);
    }

    public function testResetAndDeleteMethods()
    {
        $sitemap = new Sitemap();
        $this->assertGreaterThan(0, $sitemap->db->value('SELECT COUNT(*) FROM sitemap'));
        $sitemap->reset(''); // everything
        $sitemap->delete(); // everything (else that we didn't just upsert)
        $this->assertEquals(0, $sitemap->db->value('SELECT COUNT(*) FROM sitemap'));
        unset($sitemap);
    }
}
