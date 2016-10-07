<?php

namespace BootPress\Blog;

use BootPress\Page\Component as PageClone;

class Page
{
    public $additional = array();
    private $methods = array();

    public function __construct()
    {
        $this->methods = array('set', 'url', 'get', 'post', 'tag', 'meta', 'link', 'style', 'script', 'jquery', 'id');
    }

    public function __get($name)
    {
        return PageClone::html()->$name;
    }

    public function __isset($name)
    {
        return (in_array($name, $this->methods) || isset($this->additional[$name])) ? false : true;
    }

    public function __call($name, $arguments)
    {
        if (in_array($name, $this->methods)) {
            $result = call_user_func_array(array(PageClone::html(), $name), $arguments);
        } elseif (isset($this->additional[$name])) {
            $result = call_user_func_array($this->additional[$name], $arguments);
        }

        return (isset($result) && !is_object($result)) ? $result : null;
    }
}
