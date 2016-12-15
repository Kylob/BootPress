<?php

namespace BootPress\Tests;

use BootPress\Page\Component as Page;
use Symfony\Component\HttpFoundation\Request;

class DebugBarTest extends \BootPress\HTMLUnit\Component
{
    
    public function testFunctionExists()
    {
        $this->assertTrue(function_exists('debugbar'));
    }

    public function testDebugbarFunction()
    {
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
    }
    
}