<?php

namespace BootPress\Bootstrap;

class Pagination extends \BootPress\Pagination\Component
{
    /**
     * Returns a BootPress\Pagination\Component with the '**bootstrap**' defaults.
     *
     * @return object
     *
     * ```php
     * $records = range(1, 100);
     *
     * if (!$bp->pagination->set('page', 10, 'http://example.com')) {
     *     $bp->pagination->total(count($records));
     * }
     *
     * echo $pagination->links();
     *
     * echo $pagination->pager();
     * ```
     */
    public function __construct()
    {
        parent::__construct('bootstrap');
    }
}
