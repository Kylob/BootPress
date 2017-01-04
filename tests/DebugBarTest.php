<?php

namespace BootPress\Tests;

use BootPress\Page\Component as Page;
use Symfony\Component\HttpFoundation\Request;

class DebugBarTest extends \BootPress\HTMLUnit\Component
{
    
    public static function setUpBeforeClass()
    {
        // To avert a ps_files_cleanup_dir permission denied error.
        if (is_dir('/tmp')) {
            session_save_path('/tmp');
        }
        ini_set('session.gc_probability', 0);
    }

    public function testFunctionExists()
    {
        $this->assertTrue(function_exists('debugbar'));
    }

    public function testDebugbarFunction()
    {
        ob_start();
        debugbar('breakpoint', 'Starting Test', 'From the Beginning');
        $page = Page::html(array(
            'dir' => __DIR__.'/page',
            'suffix' => '.html',
            'testing' => true,
        ), Request::create(
            'http://website.com/',      // The URI
            'GET',                      // The HTTP method
            array(),                    // The query (GET) or request (POST) parameters
            array(),                    // The request cookies ($_COOKIE)
            array()                     // The request files ($_FILES)
        ), 'overthrow');
        debugbar('enable');
        $page->send($page->display('Content'));
        debugbar('breakpoint', 'Ending Test');
        $output = ob_get_clean();
    }
    
}
