<?php

namespace BootPress\Bootstrap;

use BootPress\Pagination\Component as Pagination;

class Bootstrap3Pagination extends Pagination
{
    public function __construct()
    {
        parent::__construct('bootstrap');
    }
}
