<?php

namespace BootPress\Bootstrap\v3;

class Component extends \BootPress\Bootstrap\Common
{
    public $version;
    public function __construct()
    {
        $this->version = 3;
        parent::__construct();
    }
}
