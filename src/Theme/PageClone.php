<?php

namespace BootPress\Theme;

use BootPress\Page\Component as Page;

class PageClone
{
    // http://www.smarty.net/forums/viewtopic.php?p=64771
    // http://www.garfieldtech.com/blog/magic-benchmarks

    public $additional = array();
    private $methods = array();

    public function __construct()
    {
        $this->methods = array('url', 'set', 'meta', 'link', 'style', 'script', 'jquery', 'filter');
    }

    public function __get($name)
    {
        return Page::html()->$name;
    }

    public function __call($name, $arguments)
    {
        if (in_array($name, $this->methods)) {
            if ($name == 'filter' && !in_array($arguments[1], array('prepend', 'append'))) {
                return;
            }
            $result = call_user_func_array(array(Page::html(), $name), $arguments);
        } elseif (isset($this->additional[$name])) {
            $result = call_user_func_array($this->additional[$name], $arguments);
        }

        return (isset($result) && !is_object($result)) ? $result : null;
    }
}
