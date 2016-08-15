<?php

namespace BootPress\Blog;

class BPClone
{
    private $class;
    private $clone = array();

    public function __construct($class = null)
    {
        $this->class = $class;
    }

    public function __get($name)
    {
        $property = ($this->class) ? $this->class->$name : null;
        if (is_object($property) || is_null($property)) {
            if (!isset($this->clone[$name])) {
                $this->clone[$name] = new self($property);
            }

            return $this->clone[$name];
        }

        return $property;
    }

    public function __call($name, $arguments)
    {
        return ($this->class && is_callable(array($this->class, $name))) ? call_user_func_array(array($this->class, $name), $arguments) : null;
    }
}
